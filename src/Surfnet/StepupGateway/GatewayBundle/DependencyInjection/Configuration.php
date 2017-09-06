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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('surfnet_stepup_gateway_gateway');

        $children = $rootNode->children();
        $children
            ->scalarNode('intrinsic_loa')
                ->isRequired()
            ->end()
            ->arrayNode('enabled_second_factors')
                ->isRequired()
                ->prototype('scalar')
            ->end();
        $children
            ->arrayNode('enabled_generic_second_factors')
                ->isRequired()
                ->prototype('array')
                ->children()
                    ->scalarNode('loa')
                    ->isRequired()
                    ->info('The lao level of the Gssf')
                ->end()
            ->end();
        $children
            ->arrayNode('pdp')
            ->isRequired()
            ->children()
            ->scalarNode('url')
                ->isRequired()
                ->info('The full URL to the PDP endpoint')
                ->end()
            ->scalarNode('username')
                ->isRequired()
                ->info('The username for authentication on PDP')
                ->end()
            ->scalarNode('password')
                ->isRequired()
                ->info('The password for authentication on PDP')
                ->end()
            ->scalarNode('client_id')
                ->isRequired()
                ->info('The StepUp client ID for PDP')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
