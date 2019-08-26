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

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Surfnet\StepupGateway\ApiBundle\SurfnetStepupGatewayApiBundle(),
            new Nelmio\SecurityBundle\NelmioSecurityBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Mopa\Bundle\BootstrapBundle\MopaBootstrapBundle(),
            new Surfnet\MessageBirdApiClientBundle\SurfnetMessageBirdApiClientBundle(),
            new Surfnet\YubikeyApiClientBundle\SurfnetYubikeyApiClientBundle(),
            new Surfnet\StepupBundle\SurfnetStepupBundle(),
            new Surfnet\StepupGateway\GatewayBundle\SurfnetStepupGatewayGatewayBundle(),
            new Surfnet\SamlBundle\SurfnetSamlBundle(),
            new JMS\TranslationBundle\JMSTranslationBundle(),
            new Surfnet\StepupGateway\SamlStepupProviderBundle\SurfnetStepupGatewaySamlStepupProviderBundle(),
            new Surfnet\StepupGateway\SecondFactorOnlyBundle\SurfnetStepupGatewaySecondFactorOnlyBundle(),
            new OpenConext\MonitorBundle\OpenConextMonitorBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.yml');
    }
}
