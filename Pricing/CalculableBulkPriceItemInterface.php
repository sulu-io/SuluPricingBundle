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

use Sulu\Bundle\ProductBundle\Entity\Addon;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;

interface CalculableBulkPriceItemInterface
{
    /**
     * Returns a product to calculate prices.
     *
     * @return ProductInterface
     */
    public function getCalcProduct();

    /**
     * Returns quantity of items.
     *
     * @return float
     */
    public function getCalcQuantity();

    /**
     * Returns discount in percent of an item.
     *
     * @return float from 0 to 100
     */
    public function getCalcDiscount();

    /**
     * Returns the currency of an item.
     *
     * @return string
     */
    public function getCalcCurrencyCode();

    /**
     * Returns the tax of an item.
     * Will be needed for calculating gross prices.
     *
     * @return float
     */
    public function getTax();

    /**
     * Get items current price.
     *
     * @return float
     */
    public function getPrice();

    /**
     * @param float $totalNetPrice
     *
     * @return self
     */
    public function setTotalNetPrice($totalNetPrice);

    /**
     * @param float $price
     *
     * @return self
     */
    public function setPrice($price);

    /**
     * Set price-change to item.
     *
     * @param float $from
     * @param float $to
     *
     * @return
     */
    public function setPriceChange($from, $to);

    /**
     * @return array[string]float
     */
    public function getPriceChange();

    /**
     * @return bool
     */
    public function getUseProductsPrice();

    /**
     * Returns a product-addon relation.
     *
     * @return Addon
     */
    public function getAddon();

    /**
     * @return bool
     */
    public function isRecurringPrice();
}
