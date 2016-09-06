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
    ) {
        $this->itemPriceCalculator = $itemPriceCalculator;
        $this->defaultCurrencyCode = $defaultCurrencyCode;
    }

    /**
     * {@inheritdoc}
     */
    public function calculate(
        array $items,
        $netShippingCosts,
        array &$groupPrices = [],
        array &$groupedItems = [],
        $currency = null,
        $taxfree = false
    ) {
        $totalNetPriceExclShippingCosts = 0;
        $totalPrice = 0;
        $totalRecurringNetPrice = 0;
        $totalRecurringPrice = 0;
        $taxes = [];

        if (!$currency) {
            $currency = $this->defaultCurrencyCode;
        }

        /** @var CalculableBulkPriceItemInterface $item */
        foreach ($items as $item) {
            $itemTotalNetPrice = $this->itemPriceCalculator->calculateItemTotalNetPrice(
                $item,
                $currency,
                $item->getUseProductsPrice()
            );

            // Add total-item-price to group.
            $this->addPriceToPriceGroup($itemTotalNetPrice, $item, $groupPrices, $groupedItems);

            // Calculate Taxes.
            $taxValue = 0;
            if (!$taxfree) {
                $taxValue = $itemTotalNetPrice * $item->getTax() / 100.0;
                $tax = (string)$item->getTax();
                if (array_key_exists($tax, $taxes)) {
                    $taxes[$tax] = (float)$taxes[$tax] + $taxValue;
                } else {
                    $taxes[$tax] = $taxValue;
                }
            }

            // Add to total price.
            if ($item->isRecurringPrice()) {
                $totalRecurringNetPrice += $itemTotalNetPrice;
                $totalRecurringPrice += $itemTotalNetPrice + $taxValue;
            } else {
                $totalNetPriceExclShippingCosts += $itemTotalNetPrice;
                $totalPrice += $itemTotalNetPrice + $taxValue;
            }
        }

        // Calculate shipping costs.
        $shippingCostsTax = 0;
        if (!$taxfree) {
            /** @var CalculableBulkPriceItemInterface $item */
            foreach ($items as $item) {
                if (!$item->isRecurringPrice()) {
                    $itemTotalNetPrice = $this->itemPriceCalculator->calculateItemTotalNetPrice(
                        $item,
                        $currency,
                        $item->getUseProductsPrice()
                    );
                    $tax = (string)$item->getTax();

                    $ratio = 0;
                    if ($totalNetPriceExclShippingCosts != 0) {
                        $ratio = $itemTotalNetPrice / $totalNetPriceExclShippingCosts;
                    } else if (count($items) > 0) {
                        // Handle total net price of 0. Each item has the same ratio.
                        $ratio = 1 / count($items);
                    }

                    $taxValue = $ratio * $netShippingCosts * $item->getTax() / 100;
                    $taxes[$tax] += $taxValue;
                    $shippingCostsTax += $taxValue;
                }
            }
        }
        $shippingCosts = $netShippingCosts + $shippingCostsTax;

        // Add net shipping costs to total prices.
        $totalPrice += $shippingCosts;
        $totalNetPrice = $totalNetPriceExclShippingCosts + $netShippingCosts;

        return [
            'totalNetPriceExclShippingCosts' => $totalNetPriceExclShippingCosts,
            'totalNetPrice' => $totalNetPrice,
            'totalPrice' => $totalPrice,
            'totalRecurringNetPrice' => $totalRecurringNetPrice,
            'totalRecurringPrice' => $totalRecurringPrice,
            'shippingCosts' => $shippingCosts,
            'taxes' => $taxes,
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
            $groupedItems[$itemPriceGroup] = [
                'items' => [],
            ];
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
