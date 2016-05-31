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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Service;

use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Service\LoaResolutionService;

final class AuthnContextClassRefValidationService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LoaAliasLookupService
     */
    private $loaAliasLookup;

    /**
     * @var LoaResolutionService
     */
    private $loaResolution;

    public function __construct(
        LoggerInterface $logger,
        LoaAliasLookupService $loaAliasLookup,
        LoaResolutionService $loaResolution
    ) {
        $this->logger = $logger;
        $this->loaAliasLookup = $loaAliasLookup;
        $this->loaResolution = $loaResolution;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function with(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Validate that a given ACCR was provided and has a valid LOA alias.
     *
     * Returns the LOA id.
     *
     * @param string $authnContextClassRef
     *   AuthnContextClassRef provided in AuthnRequest.
     *
     * @return string
     *   LOA Id
     */
    public function validate($authnContextClassRef) {
        if (empty($authnContextClassRef)) {
            $this->logger->info( 'No LOA requested, sending response with status Requester Error');
            return '';
        }

        $loaId = $this->loaAliasLookup->findLoaIdByAlias($authnContextClassRef);

        if (!$loaId) {
            $this->logger->info(sprintf(
                'Requested required Loa "%s" does not have a second factor alias',
                $authnContextClassRef
            ));
            return '';
        }

        if (!$this->loaResolution->hasLoa($loaId)) {
            $this->logger->info(sprintf(
                'Requested required Loa "%s" does not exist',
                $authnContextClassRef
            ));
            return '';
        }

        return $loaId;
    }
}
