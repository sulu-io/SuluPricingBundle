<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PricingBundle\Model;

use Sulu\Bundle\PricingBundle\Pricing\CalculableBulkPriceItemInterface;
use Sulu\Bundle\PricingBundle\Pricing\CalculablePriceGroupItemInterface;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;

class CalculableItem implements CalculableBulkPriceItemInterface, CalculablePriceGroupItemInterface
{
    private $addon;
    private $currencyCode;
    private $discount;
    private $isRecurringPrice;
    private $price;
    private $priceChange;
    private $priceGroup;
    private $priceGroupContent;
    private $product;
    private $quantity;
    private $tax;
    private $useProductsPrice;

    public function __construct(
        $quantity,
        $price,
//        $priceChange,
//        $priceGroup,
//        $priceGroupContent,
        $discount,
        $tax,
        $currencyCode,
        $isRecurringPrice = false,
        $useProductsPrice = false,
        ProductInterface $product = null,
        ProductInterface $addon = null
    ) {
        $this->quantity = $quantity;
        $this->price = $price;
//        $this->priceChange = $priceChange;
//        $this->priceGroup = $priceGroup;
//        $this->priceGroupContent = $priceGroupContent;
        $this->isRecurringPrice = $isRecurringPrice;
        $this->discount = $discount;
        $this->tax = $tax;
        $this->currencyCode = $currencyCode;
        $this->useProductsPrice = $useProductsPrice;
        $this->product = $product;
        $this->addon = $addon;
    }

    public function getCalcProduct()
    {
        return $this->product;
    }

    public function getCalcQuantity()
    {
        return $this->quantity;
    }

    public function getCalcDiscount()
    {
        return $this->discount;
    }

    public function getCalcCurrencyCode()
    {
        return $this->currencyCode;
    }

    public function getTax()
    {
        return $this->tax;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function setPriceChange($from, $to)
    {
        return null;
    }

    public function getPriceChange()
    {
        return null;
    }

    public function getUseProductsPrice()
    {
        return $this->useProductsPrice;
    }

    public function getAddon()
    {
        return $this->addon;
    }

    public function isRecurringPrice()
    {
        return $this->isRecurringPrice;
    }

    public function getCalcPriceGroupContent()
    {
        return null;
    }

    public function getCalcPriceGroup()
    {
        return null;
    }


}
