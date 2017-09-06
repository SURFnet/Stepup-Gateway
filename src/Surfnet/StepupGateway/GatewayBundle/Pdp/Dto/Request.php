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

namespace Surfnet\StepupGateway\GatewayBundle\Pdp\Dto;

use JsonSerializable;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Request\AccessSubject;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Request\Resource;
use Webmozart\Assert\Assert;

final class Request implements JsonSerializable
{
    const NAMEIDFORMAT_UNSPECIFIED = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
    const XACML_ATTRIBUTE_IP_ADDRESS = 'urn:mace:surfnet.nl:collab:xacml-attribute:ip-address';

    /**
     * @var AccessSubject
     */
    public $accessSubject;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Request\Resource
     */
    public $resource;

    /**
     * @param string $clientId
     * @param string $subjectId
     * @param string $idpEntityId
     * @param string $spEntityId
     * @param array $responseAttributes
     * @param string $requestIpAddress
     * @return Request $request
     */
    public static function from($clientId, $subjectId, $idpEntityId, $spEntityId, array $responseAttributes, $requestIpAddress)
    {
        Assert::string($subjectId, 'The SubjectId must be a string, received "%s"');
        Assert::string($idpEntityId, 'The IDPentityID must be a string, received "%s"');
        Assert::string($spEntityId, 'The SPentityID must be a string, received "%s"');
        Assert::allString(
            array_keys($responseAttributes),
            'The keys of the Response attributes must be strings'
        );
        Assert::allIsArray($responseAttributes, 'The values of the Response attributes must be arrays');
        Assert::string($clientId, 'The client ID must be a string, received "%s"');
        Assert::string($requestIpAddress, 'The request IP address must be a string, received "%s"');

        $request = new self;

        $subjectIdAttribute = new Attribute;
        $subjectIdAttribute->attributeId = self::NAMEIDFORMAT_UNSPECIFIED;
        $subjectIdAttribute->value = $subjectId;

        $request->accessSubject = new AccessSubject;
        $request->accessSubject->attributes = [$subjectIdAttribute];

        $spEntityIdAttribute  = new Attribute;
        $spEntityIdAttribute->attributeId = 'SPentityID';
        $spEntityIdAttribute->value = $spEntityId;

        $idpEntityIdAttribute = new Attribute;
        $idpEntityIdAttribute->attributeId = 'IDPentityID';
        $idpEntityIdAttribute->value = $idpEntityId;

        $clientIdAttribute = new Attribute;
        $clientIdAttribute->attributeId = 'ClientID';
        $clientIdAttribute->value = $clientId;

        $request->resource = new Resource;
        $request->resource->attributes = [$clientIdAttribute, $spEntityIdAttribute, $idpEntityIdAttribute];

        foreach ($responseAttributes as $id => $values) {
            foreach ($values as $value) {
                $attribute = new Attribute;
                $attribute->attributeId = $id;
                $attribute->value = $value;

                $request->accessSubject->attributes[] = $attribute;
            }
        }

        $ipAddressAttribute = new Attribute;
        $ipAddressAttribute->attributeId = self::XACML_ATTRIBUTE_IP_ADDRESS;
        $ipAddressAttribute->value = $requestIpAddress;

        $request->accessSubject->attributes[] = $ipAddressAttribute;

        return $request;
    }

    public function jsonSerialize()
    {
        return [
            'Request' => [
                'AccessSubject'      => $this->accessSubject,
                'Resource'           => $this->resource,
            ]
        ];
    }
}
