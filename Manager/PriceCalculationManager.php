<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PricingBundle\Manager;

use Sulu\Bundle\PricingBundle\Pricing\Exceptions\PriceCalculationException;
use Sulu\Bundle\PricingBundle\Pricing\ItemPriceCalculator;

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
     * Calculates total prices of all items given in data array.
     *
     * @param array $itemsData
     * @param string $currency
     * @param string $locale
     *
     * @return array
     */
    public function retrieveItemPrices($itemsData, $currency, $locale)
    {
        $items = [];

        // Prepare item data.
        foreach ($itemsData as $itemData) {
            $useProductsPrice = false;
            if (isset($itemData['useProductsPrice']) && $itemData['useProductsPrice'] == true) {
                $useProductsPrice = $itemData['useProductsPrice'];
            }

            // Add and remove necessary data for calculation.
            $itemData = $this->setDefaultData($itemData);
            $itemData = $this->unsetUneccesaryData($itemData);

            // Generate item.
            $item = $this->getItemManager()->save($itemData, $locale);
            $item->setUseProductsPrice($useProductsPrice);

            // Calculate total net price of item.
            $itemPrice = $this->itemPriceCalculator->calculateItemNetPrice(
                $item,
                $currency,
                $useProductsPrice,
                $this->isItemGrossPrice($itemData)
            );
            $itemTotalNetPrice = $this->itemPriceCalculator->calculateItemTotalNetPrice(
                $item,
                $currency,
                $useProductsPrice,
                $this->isItemGrossPrice($itemData)
            );
            $item->setPrice($itemPrice);
            $item->setTotalNetPrice($itemTotalNetPrice);

            $items[] = $item;
        }

        return [
            'items' => $items
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
    private function unsetUneccesaryData(array $data)
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
    private function setDefaultData(array $data)
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
