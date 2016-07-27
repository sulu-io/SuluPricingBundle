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

/**
 * Calculate Price of an Order
 */
class GroupedItemsPriceCalculator implements GroupedItemsPriceCalculatorInterface
{
    /**
     * @var ItemPriceCalculator
     */
    protected $itemPriceCalculator;

    /**
     * @var string
     */
    private $defaultCurrencyCode;

    /**
     * @param ItemPriceCalculator $itemPriceCalculator
     * @param string $defaultCurrencyCode
     */
    public function __construct(
        ItemPriceCalculator $itemPriceCalculator,
        $defaultCurrencyCode
    )
    {
        $this->itemPriceCalculator = $itemPriceCalculator;
        $this->defaultCurrencyCode = $defaultCurrencyCode;
    }

    /**
     * {@inheritdoc}
     */
    public function calculate(
        $items,
        &$groupPrices = array(),
        &$groupedItems = array(),
        $currency = null
    ) {
        $overallPrice = 0;
        $overallRecurringPrice = 0;

        if (!$currency) {
            $currency = $this->defaultCurrencyCode;
        }

        /** @var CalculableBulkPriceItemInterface $item */
        foreach ($items as $item) {
            $itemPrice = $this->itemPriceCalculator->calculate($item, $currency, $item->getUseProductsPrice());

            // add total-item-price to group
            $this->addPriceToPriceGroup($itemPrice, $item, $groupPrices, $groupedItems);

            // add to overall price
            if ($item->isRecurringPrice()) {
                $overallRecurringPrice += $itemPrice;
            } else {
                $overallPrice += $itemPrice;
            }
        }

        return [
            'totalPrice' => $overallPrice,
            'totalRecurringPrice' => $overallRecurringPrice
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setPricesOfChanged($items)
    {
        $hasChanged = false;
        foreach ($items as $item) {
            $priceChange = $item->getPriceChange();
            if ($priceChange) {
                $item->setPrice($priceChange['to']);
                $hasChanged = true;
            }
        }

        return $hasChanged;
    }

    /**
     * adds price to a price-group
     *
     * @param float $price
     * @param CalculablePriceGroupItemInterface $item
     * @param array $groupPrices
     * @param array $groupedItems
     *
     * @internal param $itemPriceGroup
     */
    protected function addPriceToPriceGroup($price, $item, &$groupPrices, &$groupedItems)
    {
        $itemPriceGroup = $item->getCalcPriceGroup();

        if ($itemPriceGroup === null) {
            $itemPriceGroup = 'undefined';
        }

        if (!isset($groupPrices[$itemPriceGroup])) {
            $groupPrices[$itemPriceGroup] = 0;
        }
        $groupPrices[$itemPriceGroup] += $price;

        // add to grouped items
        if (!isset($groupedItems[$itemPriceGroup])) {
            $groupedItems[$itemPriceGroup] = array(
                'items' => array()
            );
            if (method_exists($item, 'getCalcPriceGroupContent') &&
                $content = $item->getCalcPriceGroupContent()
            ) {
                $groupedItems[$itemPriceGroup] = array_merge($content, $groupedItems[$itemPriceGroup]);
            }
        }
        $groupedItems[$itemPriceGroup]['items'][] = $item;
        $groupedItems[$itemPriceGroup]['price'] = $groupPrices[$itemPriceGroup];
        $groupedItems[$itemPriceGroup]['priceFormatted'] = $this->itemPriceCalculator->formatPrice(
            $groupPrices[$itemPriceGroup],
            null
        );
    }
}
