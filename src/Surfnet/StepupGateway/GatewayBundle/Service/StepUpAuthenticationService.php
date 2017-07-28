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

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupBundle\Command\SendSmsChallengeCommand as StepupSendSmsChallengeCommand;
use Surfnet\StepupBundle\Command\VerifyPossessionOfPhoneCommand;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Service\SmsSecondFactor\OtpVerification;
use Surfnet\StepupBundle\Service\SmsSecondFactorService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupBundle\Value\PhoneNumber\InternationalPhoneNumber;
use Surfnet\StepupBundle\Value\YubikeyOtp;
use Surfnet\StepupBundle\Value\YubikeyPublicId;
use Surfnet\StepupGateway\ApiBundle\Dto\Otp as ApiOtp;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Service\YubikeyService;
use Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Service\StepUp\YubikeyOtpVerificationResult;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StepUpAuthenticationService
{
    /**
     * @var \Surfnet\StepupBundle\Service\LoaResolutionService
     */
    private $loaResolutionService;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository
     */
    private $secondFactorRepository;

    /**
     * @var \Surfnet\StepupGateway\ApiBundle\Service\YubikeyService
     */
    private $yubikeyService;

    /**
     * @var \Surfnet\StepupBundle\Service\SmsSecondFactorService
     */
    private $smsService;

    /** @var InstitutionMatchingHelper */
    private $institutionMatchingHelper;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var SecondFactorTypeService
     */
    private $secondFactorTypeService;

    /**
     * @param LoaResolutionService   $loaResolutionService
     * @param SecondFactorRepository $secondFactorRepository
     * @param YubikeyService         $yubikeyService
     * @param SmsSecondFactorService $smsService
     * @param InstitutionMatchingHelper $institutionMatchingHelper
     * @param TranslatorInterface    $translator
     * @param LoggerInterface        $logger
     * @param SecondFactorTypeService $secondFactorTypeService
     */
    public function __construct(
        LoaResolutionService $loaResolutionService,
        SecondFactorRepository $secondFactorRepository,
        YubikeyService $yubikeyService,
        SmsSecondFactorService $smsService,
        InstitutionMatchingHelper $institutionMatchingHelper,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        SecondFactorTypeService $secondFactorTypeService
    ) {
        $this->loaResolutionService = $loaResolutionService;
        $this->secondFactorRepository = $secondFactorRepository;
        $this->yubikeyService = $yubikeyService;
        $this->smsService = $smsService;
        $this->institutionMatchingHelper = $institutionMatchingHelper;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->secondFactorTypeService = $secondFactorTypeService;
    }

    /**
     * @param string          $identityNameId
     * @param Loa             $requiredLoa
     * @return \Doctrine\Common\Collections\Collection
     */
    public function determineViableSecondFactors(
        $identityNameId,
        Loa $requiredLoa
    ) {

        $candidateSecondFactors = $this->secondFactorRepository->getAllMatchingFor(
            $requiredLoa,
            $identityNameId,
            $this->secondFactorTypeService
        );
        $this->logger->info(
            sprintf('Loaded %d matching candidate second factors', count($candidateSecondFactors))
        );

        return $candidateSecondFactors;
    }

    /**
     * @param string           $requestedLoa
     * @param string           $identityNameId
     * @param ServiceProvider  $serviceProvider
     * @return null|Loa
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) see https://www.pivotaltracker.com/story/show/96065350
     * @SuppressWarnings(PHPMD.NPathComplexity)      see https://www.pivotaltracker.com/story/show/96065350
     */
    public function resolveHighestRequiredLoa(
        $requestedLoa,
        $identityNameId,
        ServiceProvider $serviceProvider
    ) {
        $loaCandidates = new ArrayCollection();

        if ($requestedLoa) {
            $loaCandidates->add($requestedLoa);
            $this->logger->info(sprintf('Added requested Loa "%s" as candidate', $requestedLoa));
        }

        $spConfiguredLoas = $serviceProvider->get('configuredLoas');
        $loaCandidates->add($spConfiguredLoas['__default__']);
        $this->logger->info(sprintf('Added SP\'s default Loa "%s" as candidate', $spConfiguredLoas['__default__']));

        $institutions = $this->determineInstitutionsByIdentityNameId($identityNameId);
        $this->logger->info(sprintf('Loaded institution(s) for "%s"', $identityNameId));

        $matchingInstitutions = $this->institutionMatchingHelper->findMatches(
            array_keys($spConfiguredLoas),
            $institutions
        );

        if (count($matchingInstitutions) > 0) {
            $this->logger->info('Found matching SP configured LoA\'s');
            foreach ($matchingInstitutions as $matchingInstitution) {
                $loaCandidates->add($spConfiguredLoas[$matchingInstitution]);
                $this->logger->info(sprintf(
                    'Added SP\'s Loa "%s" as candidate',
                    $spConfiguredLoas[$matchingInstitution]
                ));
            }
        }

        if (!count($loaCandidates)) {
            throw new RuntimeException('No Loa can be found, at least one Loa (SP default) should be found');
        }

        $actualLoas = new ArrayCollection();
        foreach ($loaCandidates as $loaDefinition) {
            $loa = $this->loaResolutionService->getLoa($loaDefinition);
            if ($loa) {
                $actualLoas->add($loa);
            }
        }

        if (!count($actualLoas)) {
            $this->logger->info(sprintf(
                'Out of "%d" candidates, no existing Loa could be found, no authentication is possible.',
                count($loaCandidates)
            ));

            return null;
        }

        /** @var \Surfnet\StepupBundle\Value\Loa $highestLoa */
        $highestLoa = $actualLoas->first();
        foreach ($actualLoas as $loa) {
            // if the current highest Loa cannot satisfy the next Loa, that must be of a higher level...
            if (!$highestLoa->canSatisfyLoa($loa)) {
                $highestLoa = $loa;
            }
        }

        $this->logger->info(
            sprintf('Out of %d candidate Loa\'s, Loa "%s" is the highest', count($loaCandidates), $highestLoa)
        );

        return $highestLoa;
    }

    /**
     * Returns whether the given Loa identifier identifies the minimum Loa, intrinsic to being authenticated via an IdP.
     *
     * @param Loa $loa
     * @return bool
     */
    public function isIntrinsicLoa(Loa $loa)
    {
        return $loa->levelIsLowerOrEqualTo(Loa::LOA_1);
    }

    /**
     * @param VerifyYubikeyOtpCommand $command
     * @return YubikeyOtpVerificationResult
     */
    public function verifyYubikeyOtp(VerifyYubikeyOtpCommand $command)
    {
        /** @var SecondFactor $secondFactor */
        $secondFactor = $this->secondFactorRepository->findOneBySecondFactorId($command->secondFactorId);

        $requester = new Requester();
        $requester->identity = $secondFactor->identityId;
        $requester->institution = $secondFactor->institution;

        $otp = new ApiOtp();
        $otp->value = $command->otp;

        $result = $this->yubikeyService->verify($otp, $requester);

        if (!$result->isSuccessful()) {
            return new YubikeyOtpVerificationResult(YubikeyOtpVerificationResult::RESULT_OTP_VERIFICATION_FAILED, null);
        }

        $otp = YubikeyOtp::fromString($command->otp);
        $publicId = YubikeyPublicId::fromOtp($otp);

        if (!$publicId->equals(new YubikeyPublicId($secondFactor->secondFactorIdentifier))) {
            return new YubikeyOtpVerificationResult(
                YubikeyOtpVerificationResult::RESULT_PUBLIC_ID_DID_NOT_MATCH,
                $publicId
            );
        }

        return new YubikeyOtpVerificationResult(YubikeyOtpVerificationResult::RESULT_PUBLIC_ID_MATCHED, $publicId);
    }

    /**
     * @param string $secondFactorId
     * @return string
     */
    public function getSecondFactorIdentifier($secondFactorId)
    {
        /** @var SecondFactor $secondFactor */
        $secondFactor = $this->secondFactorRepository->findOneBySecondFactorId($secondFactorId);

        return $secondFactor->secondFactorIdentifier;
    }

    /**
     * @return int
     */
    public function getSmsOtpRequestsRemainingCount()
    {
        return $this->smsService->getOtpRequestsRemainingCount();
    }

    /**
     * @return int
     */
    public function getSmsMaximumOtpRequestsCount()
    {
        return $this->smsService->getMaximumOtpRequestsCount();
    }

    /**
     * @param SendSmsChallengeCommand $command
     * @return bool
     */
    public function sendSmsChallenge(SendSmsChallengeCommand $command)
    {
        /** @var SecondFactor $secondFactor */
        $secondFactor = $this->secondFactorRepository->findOneBySecondFactorId($command->secondFactorId);

        $phoneNumber = InternationalPhoneNumber::fromStringFormat($secondFactor->secondFactorIdentifier);

        $stepupCommand = new StepupSendSmsChallengeCommand();
        $stepupCommand->phoneNumber = $phoneNumber;
        $stepupCommand->body = $this->translator->trans('gateway.second_factor.sms.challenge_body');
        $stepupCommand->identity = $secondFactor->identityId;
        $stepupCommand->institution = $secondFactor->institution;

        return $this->smsService->sendChallenge($stepupCommand);
    }

    /**
     * @param VerifyPossessionOfPhoneCommand $command
     * @return OtpVerification
     */
    public function verifySmsChallenge(VerifyPossessionOfPhoneCommand $command)
    {
        return $this->smsService->verifyPossession($command);
    }

    public function clearSmsVerificationState()
    {
        $this->smsService->clearSmsVerificationState();
    }

    private function determineInstitutionsByIdentityNameId($identityNameId)
    {
        return $this->secondFactorRepository->getAllInstitutions($identityNameId);
    }
}
