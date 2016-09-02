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

use JMS\Serializer\Annotation as Serializer;
use Sulu\Bundle\PricingBundle\Pricing\CalculableBulkPriceItemInterface;
use Sulu\Bundle\PricingBundle\Pricing\CalculablePriceGroupItemInterface;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class CalculableItem implements CalculableBulkPriceItemInterface, CalculablePriceGroupItemInterface
{
    private $addon;
    private $currencyCode;
    private $discount;
    private $isRecurringPrice;
    private $price;
    private $product;
    private $quantity;
    private $tax;
    private $useProductsPrice;
    private $totalNetPrice;

    public function __construct(
        $quantity,
        $price,
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

    public function getTotalNetPrice()
    {
        return $this->totalNetPrice;
    }

    public function setTotalNetPrice($totalNetPrice)
    {
        $this->totalNetPrice = $totalNetPrice;
    }
}
