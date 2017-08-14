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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Pdp;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Request;
use Surfnet\StepupGateway\GatewayBundle\Pdp\PdpClient;
use Surfnet\StepupGateway\GatewayBundle\Pdp\PolicyDecision;

class PdpClientTest extends TestCase
{
    /**
     * @test
     * @group Pdp
     *
     * @dataProvider pdpResponseNameProvider
     */
    public function a_pdp_client_gives_policy_decisions_based_on_pdp_responses_to_pdp_requests($responseName)
    {
        $pdpRequest = Request::from('subject', 'idp', 'sp', []);
        $denyResponseJson = file_get_contents(__DIR__ . '/fixture/response_' . $responseName . '.json');

        $mockHandler = new MockHandler([
            new Response(200, [], $denyResponseJson)
        ]);

        $guzzle = new Client(['handler' => $mockHandler]);

        $pdpClient = new PdpClient($guzzle, '/pdp/api/decide/policy');
        $policyDecision = $pdpClient->requestDecisionFor($pdpRequest);

        $this->assertInstanceOf(PolicyDecision::class, $policyDecision);
    }

    public function pdpResponseNameProvider()
    {
        return [
            ['deny'],
            ['indeterminate'],
            ['not_applicable'],
            ['permit']
        ];
    }
}
