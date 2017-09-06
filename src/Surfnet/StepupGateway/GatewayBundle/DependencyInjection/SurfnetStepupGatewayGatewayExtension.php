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

namespace Surfnet\StepupGateway\GatewayBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SurfnetStepupGatewayGatewayExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('repositories.yml');

        $container
            ->getDefinition('gateway.security.intrinsic_loa')
            ->addArgument($config['intrinsic_loa']);

        // Enabled second factor types (specific and generic) are merged into 'ss.enabled_second_factors'
        $gssfSecondFactors = array_keys($config['enabled_generic_second_factors']);
        $container
            ->getDefinition('gateway.repository.second_factor.enabled')
            ->replaceArgument(1, array_merge($config['enabled_second_factors'], $gssfSecondFactors));

        $container->setParameter('pdp.url', $config['pdp']['url']);
        $container->setParameter('pdp.username', $config['pdp']['username']);
        $container->setParameter('pdp.password', $config['pdp']['password']);
        $container->setParameter('pdp.client_id', $config['pdp']['client_id']);
    }
}
