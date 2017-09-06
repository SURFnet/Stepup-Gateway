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

use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;

final class PolicyDecision implements PolicyDecisionInterface
{
    const DECISION_DENY = 'Deny';
    const DECISION_INDETERMINATE = 'Indeterminate';
    const DECISION_NOT_APPLICABLE = 'NotApplicable';
    const DECISION_PERMIT = 'Permit';

    /**
     * @var string
     */
    private $decision;

    /**
     * @var string|null
     */
    private $statusMessage;

    /**
     * @var string
     */
    private $statusCode;

    /**
     * @var string[]
     */
    public $loaObligations = [];

    /**
     * @param Response $response
     * @return PolicyDecision
     */
    public static function fromResponse(Response $response)
    {
        $policyDecision = new self;
        $policyDecision->decision = $response->decision;

        $policyDecision->statusCode = $response->status->statusCode->value;

        if (isset($response->status->statusMessage)) {
            $policyDecision->statusMessage = $response->status->statusMessage;
        }

        if (isset($response->obligations)) {
            foreach ($response->obligations as $obligation) {
                foreach ($obligation->attributeAssignments as $assignment) {
                    if ($assignment->attributeId === 'urn:loa:level') {
                        $policyDecision->loaObligations[] = $assignment->value;
                    }
                }
            }
        }

        return $policyDecision;
    }

    /**
     * @return bool
     */
    public function permitsAccess()
    {
        return $this->decision === self::DECISION_PERMIT || $this->decision === self::DECISION_NOT_APPLICABLE;
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        if (!$this->hasStatusMessage()) {
            throw new RuntimeException('No status message found');
        }

        return $this->statusMessage;
    }

    /**
     * Get the status message or status code of the decision.
     *
     * If no status message was present in the response this method will
     * return the status code instead.
     *
     * @return string
     */
    public function getFormattedStatus()
    {
        if ($this->hasStatusMessage()) {
            return $this->statusMessage;
        }

        return $this->statusCode;
    }

    /**
     * @return bool
     */
    public function hasStatusMessage()
    {
        return isset($this->statusMessage);
    }

    /**
     * @return bool
     */
    public function hasLoaObligations()
    {
        return (bool) count($this->loaObligations);
    }

    /**
     * @return string[]
     */
    public function getLoaObligations()
    {
        return $this->loaObligations;
    }
}
