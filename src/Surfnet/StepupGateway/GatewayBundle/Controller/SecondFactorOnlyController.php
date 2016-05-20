<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupGateway\GatewayBundle\Controller;

use Exception;
use Surfnet\SamlBundle\Http\XMLResponse;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SecondFactorOnlyController extends Controller
{
    public function metadataAction()
    {
        return new XMLResponse(
          $this->get('surfnet_saml.metadata_factory')->generate()
        );
    }

    public function ssoAction(Request $httpRequest)
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');
        $logger->notice('Received AuthnRequest, started processing');

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');

        try {
            $originalRequest = $redirectBinding->processRequest($httpRequest);
        } catch (Exception $e) {
            $logger->critical(sprintf('Could not process Request, error: "%s"', $e->getMessage()));

            return $this->render(
              'SurfnetStepupGatewayGatewayBundle:Gateway:unrecoverableError.html.twig'
            );
        }

        $originalRequestId = $originalRequest->getRequestId();
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice(sprintf(
          'AuthnRequest processing complete, received AuthnRequest from "%s", request ID: "%s"',
          $originalRequest->getServiceProvider(),
          $originalRequest->getRequestId()
        ));

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler $stateHandler */
        $stateHandler = $this->get('gateway.proxy.state_handler');
        $stateHandler
          ->setRequestId($originalRequestId)
          ->setRequestServiceProvider($originalRequest->getServiceProvider())
          ->setRelayState($httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''))
          ->setResponseAction('SurfnetStepupGatewayGatewayBundle:SecondFactorOnly:respond');

        if (!$originalRequest->getNameId()) {
            $logger->info(
              'No NameID provided, sending response with status Requester Error'
            );
            return $this->get('gateway.service.saml_response')->renderRequesterFailureResponse();
        }

        $stateHandler->saveIdentityNameId($originalRequest->getNameId());

        // check if the requested Loa is supported
        $requiredLoa = $originalRequest->getAuthenticationContextClassRef();

        if (!$requiredLoa) {
            $logger->info(
              'No LOA requested, sending response with status Requester Error'
            );
            return $this->get('gateway.service.saml_response')->renderRequesterFailureResponse();
        }

        if ($requiredLoa && !$this->get('surfnet_stepup.service.loa_resolution')->hasLoa($requiredLoa)) {
            $logger->info(sprintf(
              'Requested required Loa "%s" does not exist, sending response with status Requester Error',
              $requiredLoa
            ));
            return $this->get('gateway.service.saml_response')->renderRequesterFailureResponse();
        }

        $stateHandler->setRequestAuthnContextClassRef(
          $originalRequest->getAuthenticationContextClassRef()
        );

        $logger->notice(
          'Forwarding to second factor controller for loa determination and handling'
        );

        return $this->forward(
          'SurfnetStepupGatewayGatewayBundle:Selection:selectSecondFactorForVerification'
        );
    }

    public function respondAction()
    {
        $responseContext = $this->get('gateway.proxy.response_context');
        $originalRequestId = $responseContext->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Creating Response');

        $grantedLoa = null;
        if (!$responseContext->isSecondFactorVerified()) {

        }

        $secondFactor = $this->get('gateway.service.second_factor_service')->findByUuid(
          $responseContext->getSelectedSecondFactor()
        );

        $grantedLoa = $this->get('surfnet_stepup.service.loa_resolution')->getLoaByLevel(
          $secondFactor->getLoaLevel()
        );

        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService $proxyResponseService */
        $proxyResponseService = $this->get('gateway.service.response_proxy');
        $response             = $proxyResponseService->create2ndFactorOnlyResponse(
          $responseContext->getIdentityNameId(),
          $responseContext->getServiceProvider(),
          (string) $grantedLoa
        );

        $responseContext->responseSent();

        $logger->notice(sprintf(
          'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
          $responseContext->getInResponseTo(),
          $response->getId()
        ));

        return $this->get('gateway.service.saml_response')->renderResponse($response);
    }
}
