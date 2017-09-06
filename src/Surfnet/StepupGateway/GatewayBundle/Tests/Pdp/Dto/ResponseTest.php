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
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Attribute;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response\AssociatedAdvice;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response\AttributeAssignment;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response\Category;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response\Obligation;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response\PolicyIdReference;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response\PolicyIdentifier;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response\PolicySetIdReference;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response\Status;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response\StatusCode;
use Surfnet\StepupGateway\GatewayBundle\Pdp\PolicyDecision;

class ResponseTest extends TestCase
{
    /**
     * @test
     * @group Pdp
     */
    public function a_pdp_response_without_a_response_key_is_invalid()
    {
        $this->setExpectedException(
          '\Surfnet\StepupGateway\GatewayBundle\Pdp\Exception\InvalidPdpResponseException',
          'Key "Response" was not found in the PDP response'
        );

        $responseJson = file_get_contents(__DIR__ . '/../fixture/invalid/response_without_response_key.json');

        Response::fromData(json_decode($responseJson, true));
    }

    /**
     * @test
     * @group Pdp
     */
    public function a_pdp_response_without_a_response_key_as_an_array_is_invalid()
    {
        $this->setExpectedException(
            '\Surfnet\StepupGateway\GatewayBundle\Pdp\Exception\InvalidPdpResponseException',
            '"Response" is not an array'
        );

        $responseJson = file_get_contents(__DIR__ . '/../fixture/invalid/response_without_response_array.json');

        Response::fromData(json_decode($responseJson, true));
    }

    /**
     * @test
     * @group Pdp
     */
    public function a_pdp_response_with_an_empty_response_is_invalid()
    {
        $this->setExpectedException(
            '\Surfnet\StepupGateway\GatewayBundle\Pdp\Exception\InvalidPdpResponseException',
            'No response data found'
        );

        $responseJson = file_get_contents(__DIR__ . '/../fixture/invalid/response_with_empty_response.json');

        Response::fromData(json_decode($responseJson, true));
    }

    /**
     * @test
     * @group Pdp
     */
    public function a_pdp_response_without_a_status_is_invalid()
    {
        $this->setExpectedException(
            '\Surfnet\StepupGateway\GatewayBundle\Pdp\Exception\InvalidPdpResponseException',
            'Key "Status" was not found in the PDP response'
        );

        $responseJson = file_get_contents(__DIR__ . '/../fixture/invalid/response_without_status_key.json');

        Response::fromData(json_decode($responseJson, true));
    }

    /**
     * @test
     * @group Pdp
     */
    public function a_pdp_response_without_a_decision_is_invalid()
    {
        $this->setExpectedException(
            '\Surfnet\StepupGateway\GatewayBundle\Pdp\Exception\InvalidPdpResponseException',
            'Key "Decision" was not found in the PDP response'
        );

        $responseJson = file_get_contents(__DIR__ . '/../fixture/invalid/response_without_decision_key.json');

        Response::fromData(json_decode($responseJson, true));
    }

    /**
     * @test
     * @group Pdp
     *
     * @dataProvider pdpResponseProvider
     * @param string $fixtureName
     * @param Response $expectedResponse
     */
    public function pdp_responses_are_deserialized_correctly($fixtureName, $expectedResponse)
    {
        $responseString = file_get_contents(__DIR__.'/../fixture/response_'. $fixtureName . '.json');

        $actualResponse = Response::fromData(json_decode($responseString, true));

        $this->assertEquals(
            $expectedResponse,
            $actualResponse,
            'The contents of the actual deserialized PDP response do not match the contents of the expected PDP response'
        );
    }

    public function pdpResponseProvider()
    {
        return [
            'Decision: Deny'                           => ['deny', $this->buildDenyResponse()],
            'Decision: Permit'                         => ['permit', $this->buildPermitResponse()],
            'Decision: NotApplicable'                  => ['not_applicable', $this->buildNotApplicableResponse()],
            'Decision: Indeterminate'                  => ['indeterminate', $this->buildIndeterminateResponse()],
            'Decision: Permit w. one obligation'       => ['cbac_permit_obligation', $this->buildPermitWithOneObligationResponse()],
            'Decision: Permit w. multiple obligations' => ['cbac_permit_multiple_obligations', $this->buildPermitWithMultipleObligationsResponse()],
            'Decision: Permit w/o. obligation'         => ['cbac_permit_without_obligation', $this->buildPermitWithoutObligationResponse()],
        ];
    }

    private function buildDenyResponse()
    {
        $response = new Response;

        $response->status                    = new Status;
        $response->status->statusCode        = new StatusCode;
        $response->status->statusCode->value = 'urn:oasis:names:tc:xacml:1.0:status:ok';

        $category                       = new Category;
        $category->categoryId           = 'urn:mace:dir:attribute-def:eduPersonAffiliation';
        $categoryAttribute              = new Attribute;
        $categoryAttribute->attributeId = 'urn:mace:dir:attribute-def:eduPersonAffiliation';
        $categoryAttribute->value       = 'student';
        $categoryAttribute->dataType    = 'http://www.w3.org/2001/XMLSchema#string';
        $category->attributes           = [$categoryAttribute];
        $response->categories             = [$category];

        $associatedAdvice                   = new AssociatedAdvice;
        $attributeAssignmentEn              = new AttributeAssignment();
        $attributeAssignmentEn->category    = 'urn:oasis:names:tc:xacml:3.0:attribute-category:resource';
        $attributeAssignmentEn->attributeId = 'DenyMessage:en';
        $attributeAssignmentEn->value       = 'Students do not have access to this resource';
        $attributeAssignmentEn->dataType    = 'http://www.w3.org/2001/XMLSchema#string';
        $attributeAssignmentNl              = new AttributeAssignment();
        $attributeAssignmentNl->category    = 'urn:oasis:names:tc:xacml:3.0:attribute-category:resource';
        $attributeAssignmentNl->attributeId = 'DenyMessage:nl';
        $attributeAssignmentNl->value       = 'Studenten hebben geen toegang tot deze dienst';
        $attributeAssignmentNl->dataType    = 'http://www.w3.org/2001/XMLSchema#string';
        $associatedAdvice->attributeAssignments = [$attributeAssignmentEn, $attributeAssignmentNl];
        $associatedAdvice->id = 'urn:surfconext:xacml:policy:id:openconext_pdp_test_deny_policy_xml';
        $response->associatedAdvices = [$associatedAdvice];

        $response->policyIdentifier = new PolicyIdentifier();
        $policySetIdReference = new PolicySetIdReference();
        $policySetIdReference->version = '1.0';
        $policySetIdReference->id = 'urn:openconext:pdp:root:policyset';
        $response->policyIdentifier->policySetIdReference = [$policySetIdReference];
        $policyIdReference = new PolicyIdReference();
        $policyIdReference->version = '1';
        $policyIdReference->id = 'urn:surfconext:xacml:policy:id:openconext_pdp_test_deny_policy_xml';
        $response->policyIdentifier->policyIdReference = [$policyIdReference];

        $response->decision = PolicyDecision::DECISION_DENY;

        return $response;
    }

    private function buildPermitResponse()
    {
        $response = new Response;

        $response->status                    = new Status;
        $response->status->statusCode        = new StatusCode;
        $response->status->statusCode->value = 'urn:oasis:names:tc:xacml:1.0:status:ok';

        $category = new Category();
        $category->categoryId = 'urn:mace:terena.org:attribute-def:edu';
        $categoryAttribute = new Attribute;
        $categoryAttribute->attributeId = 'urn:mace:terena.org:attribute-def:edu';
        $categoryAttribute->value = 'what';
        $categoryAttribute->dataType = 'http://www.w3.org/2001/XMLSchema#string';
        $category->attributes = [$categoryAttribute];
        $response->categories = [$category];

        $response->policyIdentifier = new PolicyIdentifier();
        $policySetIdReference = new PolicySetIdReference();
        $policySetIdReference->version = '1.0';
        $policySetIdReference->id = 'urn:openconext:pdp:root:policyset';
        $response->policyIdentifier->policySetIdReference = [$policySetIdReference];
        $policyIdReference = new PolicyIdReference();
        $policyIdReference->version = '1';
        $policyIdReference->id = 'urn:surfconext:xacml:policy:id:openconext_pdp_test_multiple_or_policy_xml';
        $response->policyIdentifier->policyIdReference = [$policyIdReference];

        $response->decision = PolicyDecision::DECISION_PERMIT;

        return $response;
    }

    private function buildNotApplicableResponse()
    {
        $response = new Response;

        $response->status                    = new Status;
        $response->status->statusCode        = new StatusCode;
        $response->status->statusCode->value = 'urn:oasis:names:tc:xacml:1.0:status:ok';

        $response->policyIdentifier = new PolicyIdentifier();
        $policySetIdReference = new PolicySetIdReference();
        $policySetIdReference->version = '1.0';
        $policySetIdReference->id = '5554cfff-2aa9-4bf0-a9dd-507239939d05';
        $response->policyIdentifier->policySetIdReference = [$policySetIdReference];

        $response->decision = PolicyDecision::DECISION_NOT_APPLICABLE;

        return $response;
    }

    private function buildIndeterminateResponse()
    {
        $response = new Response;

        $response->status                    = new Status;
        $response->status->statusDetail      = '<MissingAttributeDetail Category=\"urn:oasis:names:tc:xacml:1.0:subject-category:access-subject\" AttributeId=\"urn:mace:dir:attribute-def:eduPersonAffiliation\" DataType=\"http://www.w3.org/2001/XMLSchema#string\"></MissingAttributeDetail>';
        $response->status->statusCode        = new StatusCode;
        $response->status->statusCode->value = 'urn:oasis:names:tc:xacml:1.0:status:missing-attribute';
        $response->status->statusMessage     = 'Missing required attribute';

        $response->policyIdentifier = new PolicyIdentifier();
        $policySetIdReference = new PolicySetIdReference();
        $policySetIdReference->version = '1.0';
        $policySetIdReference->id = '5ea058ea-002c-4d52-a93c-4008df7d84b8';
        $response->policyIdentifier->policySetIdReference = [$policySetIdReference];
        $policyIdReference = new PolicyIdReference();
        $policyIdReference->version = '1';
        $policyIdReference->id = 'urn:surfconext:xacml:policy:id:openconext.pdp.test.deny.policy.xml';
        $response->policyIdentifier->policyIdReference = [$policyIdReference];

        $response->decision = PolicyDecision::DECISION_INDETERMINATE;

        return $response;
    }

    private function buildPermitWithOneObligationResponse()
    {
        $response = new Response;

        $response->status                    = new Status;
        $response->status->statusCode        = new StatusCode;
        $response->status->statusCode->value = 'urn:oasis:names:tc:xacml:1.0:status:ok';

        $attributeAssignment              = new AttributeAssignment();
        $attributeAssignment->category    = 'urn:oasis:names:tc:xacml:1.0:subject-category:access-subject';
        $attributeAssignment->attributeId = 'urn:loa:level';
        $attributeAssignment->value       = 'http://test2.surfconext.nl/assurance/loa2';
        $attributeAssignment->dataType    = 'http://www.w3.org/2001/XMLSchema#string';

        $obligation                         = new Obligation;
        $obligation->attributeAssignments[] = $attributeAssignment;
        $obligation->id = 'urn:openconext:ssa:loa';
        $response->obligations[] = $obligation;

        $response->decision = PolicyDecision::DECISION_PERMIT;

        return $response;
    }

    private function buildPermitWithMultipleObligationsResponse()
    {
        $response = new Response;

        $response->status                    = new Status;
        $response->status->statusCode        = new StatusCode;
        $response->status->statusCode->value = 'urn:oasis:names:tc:xacml:1.0:status:ok';

        $attributeAssignment1              = new AttributeAssignment();
        $attributeAssignment1->category    = 'urn:oasis:names:tc:xacml:1.0:subject-category:access-subject';
        $attributeAssignment1->attributeId = 'urn:loa:level';
        $attributeAssignment1->value       = 'http://test2.surfconext.nl/assurance/loa2';
        $attributeAssignment1->dataType    = 'http://www.w3.org/2001/XMLSchema#string';
        $attributeAssignment2              = new AttributeAssignment();
        $attributeAssignment2->category    = 'urn:oasis:names:tc:xacml:1.0:subject-category:access-subject';
        $attributeAssignment2->attributeId = 'urn:loa:level';
        $attributeAssignment2->value       = 'http://test2.surfconext.nl/assurance/loa3';
        $attributeAssignment2->dataType    = 'http://www.w3.org/2001/XMLSchema#string';

        $obligation                         = new Obligation;
        $obligation->attributeAssignments[] = $attributeAssignment1;
        $obligation->id = 'urn:openconext:ssa:loa';
        $response->obligations[] = $obligation;

        $obligation                         = new Obligation;
        $obligation->attributeAssignments[] = $attributeAssignment2;
        $obligation->id = 'urn:openconext:ssa:loa';
        $response->obligations[] = $obligation;

        $response->decision = PolicyDecision::DECISION_PERMIT;

        return $response;
    }

    private function buildPermitWithoutObligationResponse()
    {
        $response = new Response;

        $response->status                    = new Status;
        $response->status->statusCode        = new StatusCode;
        $response->status->statusCode->value = 'urn:oasis:names:tc:xacml:1.0:status:ok';

        $response->decision = PolicyDecision::DECISION_PERMIT;

        return $response;
    }
}
