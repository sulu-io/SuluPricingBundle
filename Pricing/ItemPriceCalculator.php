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

use Sulu\Bundle\PricingBundle\Pricing\Exceptions\PriceCalculationException;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;
use Sulu\Bundle\ProductBundle\Entity\Type;
use Sulu\Bundle\ProductBundle\Product\ProductPriceManagerInterface;

/**
 * Calculates price of an item.
 */
class ItemPriceCalculator
{
    /**
     * @var ProductPriceManagerInterface
     */
    protected $priceManager;

    /**
     * @var string
     */
    protected $defaultLocale;

    /**
     * @var array
     */
    private $productTypesMap;

    /**
     * @param ProductPriceManagerInterface $priceManager
     * @param string $defaultLocale
     * @param array $productTypesMap
     */
    public function __construct(
        ProductPriceManagerInterface $priceManager,
        $defaultLocale,
        array $productTypesMap
    ) {
        $this->priceManager = $priceManager;
        $this->defaultLocale = $defaultLocale;
        $this->productTypesMap = $productTypesMap;
    }

    /**
     * Calculates the overall total price of an item.
     *
     * @param CalculableBulkPriceItemInterface $item
     * @param string|null $currency
     * @param bool|null $useProductsPrice
     * @param bool|null $isGrossPrice
     *
     * @return float
     */
    public function calculateItemTotalNetPrice($item, $currency = null, $useProductsPrice = true, $isGrossPrice = false)
    {
        $priceValue = $this->calculateItemNetPrice($item, $currency, $useProductsPrice, $isGrossPrice);

        if ($priceValue === null) {
            $priceValue = 0;
        }

        if ($item->getPrice() && $item->getPrice() !== $priceValue) {
            $item->setPriceChange($item->getPrice(), $priceValue);
        }

        $itemPrice = $priceValue * $item->getCalcQuantity();

        // Calculate items discount.
        $discount = ($itemPrice / 100) * $item->getCalcDiscount();

        // Calculate total item price.
        $totalPrice = $itemPrice - $discount;

        return $totalPrice;
    }

    /**
     * Returns price of a single item based on it's quantity.
     *
     * @param CalculableBulkPriceItemInterface $item
     * @param null|string $currency
     * @param null|bool $useProductsPrice
     * @param bool|null $isGrossPrice
     *
     * @throws PriceCalculationException
     *
     * @return float
     */
    public function calculateItemNetPrice($item, $currency = null, $useProductsPrice = null, $isGrossPrice = false)
    {
        $currency = $this->getCurrency($currency);

        if ($useProductsPrice === null) {
            $useProductsPrice = $item->getUseProductsPrice();
        }

        // Validate item
        $this->validateItem($item);

        $priceValue = $item->getPrice();

        if ($useProductsPrice && ($item->getCalcProduct() || $item->getAddon())) {
            $priceValue = $this->getValidProductNetPriceForItem($item, $currency);
        } elseif ($isGrossPrice) {
            // Handle gross prices.
            $tax = $item->getTax();
            if ($tax > 0) {
                $priceValue = ($priceValue / (100 + $tax)) * 100;
            }
        }

        return $priceValue;
    }

    /**
     * Format price.
     *
     * @param float $price
     * @param string $currency
     * @param string $locale
     *
     * @return string
     */
    public function formatPrice($price, $currency, $locale = null)
    {
        return $this->priceManager->getFormattedPrice($price, $currency, $locale);
    }

    /**
     * Validate item values.
     *
     * @param CalculableBulkPriceItemInterface $item
     *
     * @throws PriceCalculationException
     */
    protected function validateItem($item)
    {
        // validate not null
        $this->validateNotNull('quantity', $item->getCalcQuantity());

        // validate discount
        $discountPercent = $item->getCalcDiscount();
        if ($discountPercent < 0 || $discountPercent > 100) {
            throw new PriceCalculationException('Discount must be within 0 and 100 percent');
        }
    }

    /**
     * Throws an exception if value is null.
     *
     * @param string $key
     * @param mixed $value
     *
     * @throws PriceCalculationException
     */
    protected function validateNotNull($key, $value)
    {
        if ($value === null) {
            throw new PriceCalculationException('Attribute ' . $key . ' must not be null');
        }
    }

    /**
     * Either returns currency or default currency.
     *
     * @param string $currency
     *
     * @return string
     */
    private function getCurrency($currency)
    {
        return $currency ?: $this->defaultLocale;
    }

    /**
     * Returns the valid product net price for item.
     *
     * @param CalculableBulkPriceItemInterface $item
     * @param string $currency
     *
     * @return int
     */
    private function getValidProductNetPriceForItem(CalculableBulkPriceItemInterface $item, $currency)
    {
        $product = $item->getCalcProduct();
        $areGrossPrices = false;

        // Get addon price.
        $addon = $item->getAddon();
        if ($addon) {
            $addonPrice = $this->priceManager->getAddonPriceForCurrency($item->getAddon(), $currency);
            if ($addonPrice) {
                $priceValue = $addonPrice->getPrice();
            } else {
                $priceValue = $this->getPriceOfProduct($addon->getAddon(), $item->getCalcQuantity(), $currency);
            }
            $areGrossPrices = $addon->getAddon()->getAreGrossPrices();
        } elseif ($product) {
            $priceValue = $this->getPriceOfProduct($item->getCalcProduct(), $item->getCalcQuantity(), $currency);
            $areGrossPrices = $product->getAreGrossPrices();
        }

        // If no price is set - return 0.
        if (empty($priceValue)) {
            return 0;
        }

        // Check if product price is gross price and return net price instead.
        if ($areGrossPrices) {
            $tax = $item->getTax();
            if ($tax > 0) {
                $priceValue = ($priceValue / (100 + $tax)) * 100;
            }
        }

        return $priceValue;
    }

    /**
     * @param ProductInterface $product
     * @param float $quantity
     * @param string|null $currency
     *
     * @return float|null
     */
    private function getPriceOfProduct(ProductInterface $product, $quantity, $currency)
    {
        $specialPriceValue = null;
        $bulkPriceValue = null;

        // Get special price.
        $specialPrice = $this->priceManager->getSpecialPriceForCurrency($product, $currency);
        if ($specialPrice) {
            $specialPriceValue = $specialPrice->getPrice();
        }

        // Get bulk price.
        $bulkPrice = $this->priceManager->getBulkPriceForCurrency($product, $quantity, $currency);
        if ($bulkPrice) {
            $bulkPriceValue = $bulkPrice->getPrice();
        }

        if (!empty($specialPriceValue) && !empty($bulkPriceValue)) {
            // Else take the smallest product price.
            $priceValue = $specialPriceValue;
            if ($specialPriceValue > $bulkPriceValue) {
                $priceValue = $bulkPriceValue;
            }
        } elseif (!empty($specialPriceValue)) {
            $priceValue = $specialPriceValue;
        } else {
            $priceValue = $bulkPriceValue;
        }

        // If product has no price check if it's a product variant. Then get price of product parent.
        if (!$priceValue
            && $product->getType()->getId() === $this->retrieveProductTypeIdByKey('PRODUCT_VARIANT')
            && $product->getParent()
        ) {
            $priceValue = $this->getPriceOfProduct($product->getParent(), $quantity, $currency);
        }

        return $priceValue;
    }

    /**
     * Returns product type id by key.
     *
     * @param string $key
     *
     * @return int
     */
    private function retrieveProductTypeIdByKey($key)
    {
        if (!isset($this->productTypesMap[$key])) {
            return null;
        }

        return intval($this->productTypesMap[$key]);
    }
}
