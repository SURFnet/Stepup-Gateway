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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Service;

use Mockery;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Pdp\PdpClientInterface;
use Surfnet\StepupGateway\GatewayBundle\Pdp\PolicyDecision;
use Surfnet\StepupGateway\GatewayBundle\Pdp\PolicyDecisionInterface;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\PdpService;

/**
 * @group Pdp
 */
final class PdpServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Mockery\MockInterface|LoaResolutionService
     */
    private $loaResolutionService;

    /**
     * @var Mockery\MockInterface|PdpClient
     */
    private $pdpClient;

    /**
     * @var Mockery\MockInterface|PolicyDecisionInterface
     */
    private $policyDecision;

    /**
     * @var Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    protected function setUp()
    {
        $this->loaResolutionService = Mockery::mock(LoaResolutionService::class);
        $this->pdpClient = Mockery::mock(PdpClientInterface::class);
        $this->policyDecision = Mockery::mock(PolicyDecisionInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
    }

    /**
     * @test
     * @dataProvider pdpDenyDecisionProvider
     */
    public function throws_exception_if_policy_denies_access($denyDecision)
    {
        $service = new PdpService(
            $this->pdpClient,
            $this->loaResolutionService,
            $this->logger,
            'StepUp'
        );

        $this->policyDecision->shouldReceive('permitsAccess')
            ->andReturn(false);

        $this->policyDecision->shouldReceive('getFormattedStatus')
            ->andReturn('status message');

        $this->pdpClient->shouldReceive('requestDecisionFor')
            ->andReturn($this->policyDecision);

        $this->setExpectedException(RuntimeException::class, 'The policy decision point (PDP) denied access (status message)');

        $service->enforceObligatoryLoa(
            new Loa(1, 'loa:1'),
            'subjectID',
            'idpID',
            'spID',
            [],
            '::1'
        );
    }

    public function pdpDenyDecisionProvider()
    {
        return [
            [
                PolicyDecision::DECISION_DENY,
                PolicyDecision::DECISION_INDETERMINATE,
            ],
        ];
    }

    /**
     * @test
     */
    public function loa_is_not_updated_on_permit_without_obligations()
    {
        $service = new PdpService(
            $this->pdpClient,
            $this->loaResolutionService,
            $this->logger,
            'StepUp'
        );

        $this->policyDecision->shouldReceive('permitsAccess')
            ->andReturn(true);

        $this->policyDecision->shouldReceive('hasLoaObligations')
            ->andReturn(false);

        $this->pdpClient->shouldReceive('requestDecisionFor')
            ->andReturn($this->policyDecision);

        $upgradedLoa = $service->enforceObligatoryLoa(
            new Loa(Loa::LOA_1, 'loa:1'),
            'subjectID',
            'idpID',
            'spID',
            [],
            '::1'
        );

        $this->assertTrue($upgradedLoa->isOfLevel(Loa::LOA_1), 'LoA should be unchanged because PDP sent no obligations');
    }

    /**
     * @test
     */
    public function loa_is_not_updated_on_obligation_with_lower_loa()
    {
        $service = new PdpService(
            $this->pdpClient,
            $this->loaResolutionService,
            $this->logger,
            'StepUp'
        );

        $this->policyDecision->shouldReceive('permitsAccess')
            ->andReturn(true);

        $this->policyDecision->shouldReceive('hasLoaObligations')
            ->andReturn(true);

        $this->policyDecision->shouldReceive('getLoaObligations')
            ->andReturn(['loa:1']);

        $this->loaResolutionService->shouldReceive('getLoaByLevel')
            ->with(Loa::LOA_1)
            ->andReturn(new Loa(Loa::LOA_1, 'loa:1'));

        $this->loaResolutionService->shouldReceive('hasLoa')
            ->andReturn(true);

        $this->loaResolutionService->shouldReceive('getLoa')
            ->with('loa:1')
            ->andReturn(new Loa(Loa::LOA_1, 'loa:1'));

        $this->logger->shouldReceive('info');

        $this->pdpClient->shouldReceive('requestDecisionFor')
            ->andReturn($this->policyDecision);

        $upgradedLoa = $service->enforceObligatoryLoa(
            new Loa(2, 'loa:2'),
            'subjectID',
            'idpID',
            'spID',
            [],
            '::1'
        );

        $this->assertTrue($upgradedLoa->isOfLevel(Loa::LOA_2), 'LoA should be unchanged because PDP sent obligation for a lower LoA');
    }

    /**
     * @test
     */
    public function loa_is_not_updated_on_obligation_with_equal_loa()
    {
        $service = new PdpService(
            $this->pdpClient,
            $this->loaResolutionService,
            $this->logger,
            'StepUp'
        );

        $this->policyDecision->shouldReceive('permitsAccess')
            ->andReturn(true);

        $this->policyDecision->shouldReceive('hasLoaObligations')
            ->andReturn(true);

        $this->policyDecision->shouldReceive('getLoaObligations')
            ->andReturn(['loa:2']);

        $this->loaResolutionService->shouldReceive('getLoaByLevel')
            ->with(Loa::LOA_1)
            ->andReturn(new Loa(Loa::LOA_1, 'loa:1'));

        $this->loaResolutionService->shouldReceive('hasLoa')
            ->with('loa:2')
            ->andReturn(true);

        $this->loaResolutionService->shouldReceive('getLoa')
            ->with('loa:2')
            ->andReturn(new Loa(Loa::LOA_2, 'loa:2'));

        $this->logger->shouldReceive('info');

        $this->pdpClient->shouldReceive('requestDecisionFor')
            ->andReturn($this->policyDecision);

        $upgradedLoa = $service->enforceObligatoryLoa(
            new Loa(2, 'loa:2'),
            'subjectID',
            'idpID',
            'spID',
            [],
            '::1'
        );

        $this->assertTrue($upgradedLoa->isOfLevel(Loa::LOA_2), 'LoA should be unchanged because PDP sent obligation the same LoA');
    }

    /**
     * @test
     */
    public function loa_is_updated_on_obligation_with_higher_loa()
    {
        $service = new PdpService(
            $this->pdpClient,
            $this->loaResolutionService,
            $this->logger,
            'StepUp'
        );

        $this->policyDecision->shouldReceive('permitsAccess')
            ->andReturn(true);

        $this->policyDecision->shouldReceive('hasLoaObligations')
            ->andReturn(true);

        $this->policyDecision->shouldReceive('getLoaObligations')
            ->andReturn(['loa:1', 'loa:2', 'loa:3']);

        $this->loaResolutionService->shouldReceive('getLoaByLevel')
            ->with(Loa::LOA_1)
            ->andReturn(new Loa(1, 'loa:1'));

        $this->loaResolutionService->shouldReceive('hasLoa')
            ->andReturn(true, true, true);

        $this->loaResolutionService->shouldReceive('getLoa')
            ->andReturn(
                new Loa(1, 'loa:1'),
                new Loa(2, 'loa:2'),
                new Loa(3, 'loa:3')
            );

        $this->logger->shouldReceive('info');

        $this->pdpClient->shouldReceive('requestDecisionFor')
            ->andReturn($this->policyDecision);

        $upgradedLoa = $service->enforceObligatoryLoa(
            new Loa(2, 'loa:2'),
            'subjectID',
            'idpID',
            'spID',
            [],
            '::1'
        );

        $this->assertTrue($upgradedLoa->isOfLevel(Loa::LOA_3), 'LoA should be LOA 3 because PDP sent obligation for a higher LoA');
    }

    /**
     * @test
     */
    public function exception_is_thrown_if_obligation_requires_unknown_loa()
    {
        $service = new PdpService(
            $this->pdpClient,
            $this->loaResolutionService,
            $this->logger,
            'StepUp'
        );

        $this->policyDecision->shouldReceive('permitsAccess')
            ->andReturn(true);

        $this->policyDecision->shouldReceive('hasLoaObligations')
            ->andReturn(true);

        $this->policyDecision->shouldReceive('getLoaObligations')
            ->andReturn(['loa:?']);

        $this->loaResolutionService->shouldReceive('getLoaByLevel')
            ->with(Loa::LOA_1)
            ->andReturn(new Loa(1, 'loa:1'));

        $this->loaResolutionService->shouldReceive('hasLoa')
            ->andReturn(false);

        $this->pdpClient->shouldReceive('requestDecisionFor')
            ->andReturn($this->policyDecision);

        $this->setExpectedException(
            RuntimeException::class,
            'that LoA is not supported in the StepUp configuration'
        );

        $upgradedLoa = $service->enforceObligatoryLoa(
            new Loa(2, 'loa:2'),
            'subjectID',
            'idpID',
            'spID',
            [],
            '::1'
        );
    }

    /**
     * @test
     */
    public function pdp_is_enabled_if_enabled_for_sp()
    {
        $service = new PdpService(
            $this->pdpClient,
            $this->loaResolutionService,
            $this->logger,
            'StepUp'
        );

        $idp = Mockery::mock(IdentityProvider::class);
        $idp->shouldReceive('pdpEnabled')
            ->andReturn(false);

        $sp = Mockery::mock(IdentityProvider::class);
        $sp ->shouldReceive('pdpEnabled')
            ->andReturn(true);

        $context = Mockery::mock(ResponseContext::class);
        $context->shouldReceive('getAuthenticatingIdp')
            ->andReturn($idp);
        $context->shouldReceive('getServiceProvider')
            ->andReturn($sp);

        $enabled = $service->isEnabledForSpOrIdp($context);

        $this->assertTrue($enabled);
    }

    /**
     * @test
     */
    public function pdp_is_enabled_if_enabled_for_idp()
    {
        $service = new PdpService(
            $this->pdpClient,
            $this->loaResolutionService,
            $this->logger,
            'StepUp'
        );

        $idp = Mockery::mock(IdentityProvider::class);
        $idp->shouldReceive('pdpEnabled')
            ->andReturn(true);

        $sp = Mockery::mock(IdentityProvider::class);
        $sp ->shouldReceive('pdpEnabled')
            ->andReturn(false);

        $context = Mockery::mock(ResponseContext::class);
        $context->shouldReceive('getAuthenticatingIdp')
            ->andReturn($idp);
        $context->shouldReceive('getServiceProvider')
            ->andReturn($sp);

        $enabled = $service->isEnabledForSpOrIdp($context);

        $this->assertTrue($enabled);
    }

    /**
     * @test
     */
    public function pdp_is_not_enabled_if_idp_unknown()
    {
        $service = new PdpService(
            $this->pdpClient,
            $this->loaResolutionService,
            $this->logger,
            'StepUp'
        );

        $sp = Mockery::mock(ServiceProvider::class);
        $sp ->shouldReceive('pdpEnabled')
            ->andReturn(false);

        $context = Mockery::mock(ResponseContext::class);
        $context->shouldReceive('getAuthenticatingIdp')
            ->andReturnNull();
        $context->shouldReceive('getServiceProvider')
            ->andReturn($sp);

        $enabled = $service->isEnabledForSpOrIdp($context);

        $this->assertFalse($enabled);
    }
}
