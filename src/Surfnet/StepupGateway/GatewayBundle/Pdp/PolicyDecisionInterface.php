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
namespace Surfnet\StepupGateway\GatewayBundle\Pdp;

interface PolicyDecisionInterface
{
    /**
     * @return bool
     */
    public function permitsAccess();

    /**
     * @return string
     */
    public function getStatusMessage();

    /**
     * Get the status message or status code of the decision.
     *
     * If no status message was present in the response this method will
     * return the status code instead.
     *
     * @return string
     */
    public function getFormattedStatus();

    /**
     * @return bool
     */
    public function hasStatusMessage();

    /**
     * @return bool
     */
    public function hasLoaObligations();

    /**
     * @return string[]
     */
    public function getLoaObligations();
}
