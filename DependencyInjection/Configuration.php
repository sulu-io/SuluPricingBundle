<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PricingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('sulu_pricing')
            ->children()
                ->scalarNode('priceformatter_digits')->defaultValue(2)->end()
                ->scalarNode('item_manager_service')->defaultValue('sulu_sales_core.item_manager')->end()
                ->scalarNode('default_currency')->defaultValue('EUR')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
