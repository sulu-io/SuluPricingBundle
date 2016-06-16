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

/**
 * Calculates price of an item.
 */
class PriceCalculationManager
{
    /**
     * @var ItemPriceCalculator
     */
    private $itemPriceCalculator;

    /**
     * @var null|\Sulu\Bundle\Sales\CoreBundle\Item\ItemManager
     */
    private $itemManager;

    /**
     * @param ItemPriceCalculator $itemPriceCalculator
     */
    public function __construct(
        ItemPriceCalculator $itemPriceCalculator
    ) {
        $this->itemPriceCalculator = $itemPriceCalculator;
    }

    /**
     * @param null|\Sulu\Bundle\Sales\CoreBundle\Item\ItemManager $itemManager
     */
    public function setItemManager($itemManager)
    {
        $this->itemManager = $itemManager;
    }

    /**
     * Calculates prices of all items given in data array.
     *
     * @param array $itemsData
     * @param string $currency
     * @param bool $taxfree
     * @param string $locale
     *
     * @return array
     */
    public function calculateItemPrices($itemsData, $currency, $taxfree, $locale)
    {
        $calculator = $this->itemPriceCalculator;
        $totalNetPrice = 0;
        $totalPrice = 0;
        $items = [];
        $taxes = [];

        foreach ($itemsData as $itemData) {
            $useProductsPrice = false;
            if (isset($itemData['useProductsPrice']) && $itemData['useProductsPrice'] == true) {
                $useProductsPrice = $itemData['useProductsPrice'];
            }

            // Add and remove necessary data for calculation.
            $itemData = $this->setDefaultData($itemData);
            $itemData = $this->unsetUneccesaryData($itemData);

            $item = $this->getItemManager()->save($itemData, $locale);
            $itemPrice = $calculator->getItemPrice(
                $item,
                $currency,
                $useProductsPrice,
                $this->isItemGrossPrice($itemData)
            );
            $itemTotalPrice = $calculator->calculate(
                $item,
                $currency,
                $useProductsPrice,
                $this->isItemGrossPrice($itemData)
            );
            $item->setPrice($itemPrice);
            $item->setTotalNetPrice($itemTotalPrice);

            $totalPrice += $itemTotalPrice;

            // Calculate Taxes.
            if (!$taxfree) {
                $taxValue = $itemPrice * $item->getTax() / 100.0 * $item->getCalcQuantity();
                $totalPrice += $taxValue;
                $tax = (string)$item->getTax();
                if (array_key_exists($tax, $taxes)) {
                    $taxes[$tax] = (float)$taxes[$tax] + $taxValue;
                } else {
                    $taxes[$tax] = $taxValue;
                }
            }

            $items[] = $item;
            $totalNetPrice += $itemTotalPrice;
        }

        return [
            'totalNetPrice' => $totalNetPrice,
            'taxes' => $taxes,
            'totalPrice' => $totalPrice,
            'items' => $items,
        ];
    }

    /**
     * Checks itemData for grossPrice value and returns it.
     *
     * @param array $itemData
     *
     * @return bool
     */
    private function isItemGrossPrice(array $itemData)
    {
        if (isset($itemData['isGrossPrice'])
            && !!json_decode($itemData['isGrossPrice'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Unsets all data from array that's not needed for price calculation.
     *
     * @param array $data
     *
     * @return array
     */
    private function unsetUneccesaryData($data)
    {
        if (isset($data['deliveryDate'])) {
            unset($data['deliveryDate']);
        }
        if (isset($data['deliveryAddress'])) {
            unset($data['deliveryAddress']);
        }

        return $data;
    }

    /**
     * Sets default data to data array, if needed for price calculation.
     *
     * @param array $data
     *
     * @return array
     */
    private function setDefaultData($data)
    {
        // Quantity unit is not necessary for price calculation, so just set it.
        if (empty($data['quantityUnit'])) {
            $data['quantityUnit'] = '';
        }

        return $data;
    }

    /**
     * Returns item manager if exists.
     *
     * @throws PriceCalculationException
     *
     * @return \Sulu\Bundle\Sales\CoreBundle\Item\ItemManager
     */
    private function getItemManager()
    {
        if (!$this->itemManager) {
            throw new PriceCalculationException(sprintf('Item manager not set'));
        }

        return $this->itemManager;
    }
}
