<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Bundle\PricingBundle\Pricing;

interface GroupedItemsPriceCalculatorInterface
{
    /**
     * calculate price of items array
     *
     * @param CalculableBulkPriceItemInterface[] $items Array with PriceCalculationItems
     * @param float $netShippingCosts
     * @param array $groupPrices Prices grouped by priceGroup
     * @param array $groupedItems Will be filled with items and prices
     * @param string $currencyCode The currency for which the price should be calculated
     * @param bool $taxfree
     *
     * @return float total-price of all items
     */
    public function calculate(
        $items,
        $netShippingCosts,
        &$groupPrices = array(),
        &$groupedItems = array(),
        $currencyCode = null,
        $taxfree = false
    );

    /**
     * Sets all item prices to the changed prices
     * Note: This will only work, if price changes have been calculated before. Since this is a service which can be
     *       called multiple times with many different items.
     *
     * @param CalculableBulkPriceItemInterface[] $items
     *
     * @return bool If prices have changed
     */
    public function setPricesOfChanged($items);
}
