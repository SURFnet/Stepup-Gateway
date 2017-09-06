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

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response;
use Surfnet\StepupGateway\GatewayBundle\Pdp\PolicyDecision;

class PolicyDecisionTest extends TestCase
{
    /**
     * @test
     * @group Pdp
     *
     * @dataProvider pdpResponseAndExpectedPermissionProvider
     * @param $responseName
     * @param $expectedPermission
     */
    public function the_correct_policy_decision_should_be_made_based_on_a_pdp_response(
        $responseName,
        $expectedPermission
    ) {
        $responseJson = json_decode(file_get_contents(__DIR__ . '/fixture/response_' . $responseName . '.json'), true);
        $response = Response::fromData($responseJson);

        $decision = PolicyDecision::fromResponse($response);

        $this->assertEquals($expectedPermission, $decision->permitsAccess());
    }

    /**
     * @test
     * @group Pdp
     */
    public function an_indeterminate_policys_status_message_is_acquired_correctly()
    {
        $responseJson = json_decode(file_get_contents(__DIR__ . '/fixture/response_indeterminate.json'), true);
        $response = Response::fromData($responseJson);

        $decision = PolicyDecision::fromResponse($response);

        $expectedStatusMessage = 'Missing required attribute';

        $statusMessage = $decision->getStatusMessage();

        $this->assertEquals($expectedStatusMessage, $statusMessage);
    }

    /**
     * @test
     * @group Pdp
     */
    public function a_status_message_cannot_be_acquired_from_a_policy_that_has_none()
    {
        $this->setExpectedException(
            '\Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException',
            'No status message found'
        );

        $responseJson = json_decode(file_get_contents(__DIR__ . '/fixture/response_deny.json'), true);
        $response = Response::fromData($responseJson);

        $decision = PolicyDecision::fromResponse($response);
        $decision->getStatusMessage();
    }

    /**
     * @test
     * @group Pdp
     */
    public function status_message_contains_status_code()
    {
        $responseJson = json_decode(file_get_contents(__DIR__ . '/fixture/response_deny.json'), true);
        $response = Response::fromData($responseJson);

        $decision = PolicyDecision::fromResponse($response);
        $message = $decision->getFormattedStatus();

        $this->assertEquals('urn:oasis:names:tc:xacml:1.0:status:ok', $message);
    }

    /**
     * @test
     * @group Pdp
     */
    public function formatted_status_message_contains_status_code_and_optional_message()
    {
        $responseJson = json_decode(file_get_contents(__DIR__ . '/fixture/response_indeterminate.json'), true);
        $response = Response::fromData($responseJson);

        $decision = PolicyDecision::fromResponse($response);
        $message = $decision->getFormattedStatus();

        $this->assertEquals('Missing required attribute', $message);
    }

    /**
     * @test
     * @group Pdp
     */
    public function permit_with_obligations_can_be_read()
    {
        $responseJson = json_decode(file_get_contents(__DIR__ . '/fixture/response_cbac_permit_multiple_obligations.json'), true);
        $response = Response::fromData($responseJson);

        $decision = PolicyDecision::fromResponse($response);

        $this->assertTrue($decision->hasLoaObligations());
        $this->assertCount(2, $decision->getLoaObligations());
    }

    public function pdpResponseAndExpectedPermissionProvider()
    {
        return [
            'Deny response does not permit access' => ['deny', false],
            'Indeterminate response does not permit access' => ['indeterminate', false],
            'Not applicable response permits access' => ['not_applicable', true],
            'Permit response permits access' => ['permit', true],
        ];
    }
}
