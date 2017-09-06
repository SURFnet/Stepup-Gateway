<?php

/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Request;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response;
use Surfnet\StepupGateway\GatewayBundle\Pdp\PdpClientInterface;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;

/**
 * Call the Policy Decision Point API.
 */
final class PdpService
{
    /**
     * @var PdpClientInterface
     */
    private $client;

    /**
     * @var LoaResolutionService
     */
    private $loaResolutionService;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Identifier with which to identify stepup to the PDP endpoint.
     *
     * @var string
     */
    private $clientId;

    public function __construct(PdpClientInterface $client, LoaResolutionService $loaResolutionService, LoggerInterface $logger, $clientId)
    {
        $this->client = $client;
        $this->loaResolutionService = $loaResolutionService;
        $this->logger = $logger;
        $this->clientId = $clientId;
    }

    /**
     * Check if PDP is enabled for given the SP or IdP in current context.
     *
     * @param ResponseContext $context
     * @return bool
     */
    public function isEnabledForSpOrIdp(ResponseContext $context)
    {
        $idp = $context->getAuthenticatingIdp();
        $sp = $context->getServiceProvider();

        if ($idp && $idp->pdpEnabled()) {
            return true;
        }

        return $sp->pdpEnabled();
    }

    /**
     * Call the PDP endpoint and determine the LoA obligated by the policy decision.
     *
     * This method takes the original LoA required by the SP or IdP and
     * returns either the same LoA, or a higher LoA of a higher LoA is
     * obligated by the PDP endpoint.
     *
     * A policy decision can result in one of four situations:
     *
     *  - access was denied (denied, indeterminate)
     *  - permit, without obligatory LoA
     *  - permit, with obligatory LoA lower than or equal to original required LoA -> original required LoA unaffectd
     *  - permit, with obligatory LoA higher than or original required LoA -> required LoA increased
     *
     * @param Loa $originalRequiredLoa
     * @param string $subjectId
     * @param string $idpEntityId
     * @param string $spEntityId
     * @param array $attributes
     * @param string $clientIp
     * @return Loa
     */
    public function enforceObligatoryLoa(Loa $originalRequiredLoa, $subjectId, $idpEntityId, $spEntityId, array $attributes, $clientIp)
    {
        $policyDecision = $this->client->requestDecisionFor(
            Request::from($this->clientId, $subjectId, $idpEntityId, $spEntityId, $attributes, $clientIp)
        );

        if (!$policyDecision->permitsAccess()) {
            throw new RuntimeException(
                sprintf(
                    'The policy decision point (PDP) denied access (%s)',
                    $policyDecision->getFormattedStatus()
                )
            );
        }

        $newRequiredLoa = $originalRequiredLoa;

        if ($policyDecision->hasLoaObligations()) {
            $loaRequiredByPolicyDecision = $this->findHighestObligatoryLoa(
                $policyDecision->getLoaObligations()
            );

            if ($loaRequiredByPolicyDecision->equals($originalRequiredLoa)) {
                $this->logger->info(
                    sprintf(
                        'The policy decision point (PDP) sent an obligation for LoA %s, which matches the LoA already required.',
                        $loaRequiredByPolicyDecision
                    )
                );
            } elseif ($loaRequiredByPolicyDecision->canSatisfyLoa($originalRequiredLoa)) {
                $newRequiredLoa = $loaRequiredByPolicyDecision;

                $this->logger->info(
                    sprintf(
                        'The policy decision point (PDP) sent an obligation for LoA %s, updating required LoA from %s to %s.',
                        $loaRequiredByPolicyDecision,
                        $originalRequiredLoa,
                        $loaRequiredByPolicyDecision
                    )
                );
            } else {
                $this->logger->info(
                    sprintf(
                        'The policy decision point (PDP) sent an obligation for LoA %s, but required LoA %s is higher - PDP has no effect.',
                        $loaRequiredByPolicyDecision,
                        $originalRequiredLoa
                    )
                );
            }
        }

        return $newRequiredLoa;
    }

    /**
     * @param string[] $uris List of LoA URIs
     * @return Loa           The highest LoA found in the policy decision obligations
     */
    private function findHighestObligatoryLoa(array $uris)
    {
        $highestObligatedLoa = $this->loaResolutionService->getLoaByLevel(Loa::LOA_1);

        foreach ($uris as $uri) {
            if (!$this->loaResolutionService->hasLoa($uri)) {
                throw new RuntimeException(
                    sprintf(
                        'The policy decision point (PDP) obligates LoA %s - but that LoA is not supported in the StepUp configuration',
                        $uri
                    )
                );
            }

            $loa = $this->loaResolutionService->getLoa($uri);
            if ($loa->canSatisfyLoa($highestObligatedLoa)) {
                $highestObligatedLoa = $loa;
            }
        }

        return $highestObligatedLoa;
    }
}
