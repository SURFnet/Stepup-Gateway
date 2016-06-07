<?php

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Service;

use Psr\Log\LoggerInterface;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;

final class SecondFactorOnlyNameIdValidationService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SamlEntityService
     */
    private $entityService;

    public function __construct(LoggerInterface $logger, SamlEntityService $entityService)
    {
        $this->logger = $logger;
        $this->entityService = $entityService;
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
     * Is the given SP allowed to authenticate via Second Factor Only for the given NameID?
     *
     * @param string $spEntityId
     * @param string $nameId
     * @return bool
     */
    public function validate($spEntityId, $nameId)
    {
        if (!$nameId) {
            $this->logger->notice(
                'No NameID provided, sending response with status Requester Error'
            );
            return false;
        }

        $serviceProvider = $this->entityService->getServiceProvider($spEntityId);

        if (!$serviceProvider->isAllowedToUseSecondFactorOnlyFor($nameId)) {
            $this->logger->notice(
                sprintf(
                    'SP "%s" may not use SecondFactorOnly mode for nameid "%s", sending response with status Requester Error',
                    $spEntityId,
                    $nameId
                )
            );
            return false;
        }

        $this->logger->notice(
            sprintf(
                'SP "%s" is allowed to use SecondFactorOnly mode for nameid "%s"',
                $spEntityId,
                $nameId
            )
        );

        return true;
    }
}
