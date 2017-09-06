<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupGateway\GatewayBundle\Pdp;

use GuzzleHttp\ClientInterface;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Request;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response;
use Webmozart\Assert\Assert;

final class PdpClient implements PdpClientInterface
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $policyDecisionPointUrl;

    public function __construct(
        ClientInterface $httpClient,
        $policyDecisionPointUrl
    ) {
        Assert::string($policyDecisionPointUrl, 'Path to PolicyDecisionPoint must be a string');

        $this->httpClient             = $httpClient;
        $this->policyDecisionPointUrl = $policyDecisionPointUrl;
    }

    /**
     * @param Request $request
     * @return PolicyDecisionInterface
     */
    public function requestDecisionFor(Request $request)
    {
        $response = $this->httpClient->request(
            'POST',
            $this->policyDecisionPointUrl,
            [
                'json' => $request,
                'headers' => [
                    'Accept'       => 'application/json',
                ],
            ]
        );

        return PolicyDecision::fromResponse(
            Response::fromData(
                json_decode($response->getBody(), true)
            )
        );
    }
}
