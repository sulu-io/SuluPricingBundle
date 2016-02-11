<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PricingBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sulu\Component\Rest\RestController;
use Sulu\Bundle\PricingBundle\Pricing\Exceptions\PriceCalculationException;
use Sulu\Bundle\PricingBundle\Pricing\ItemPriceCalculator;

/**
 * Handles price calculations by api.
 */
class PricingController extends RestController implements ClassResourceInterface
{
    /**
     * Calculate pricing of an array of items
     *
     * @param Request $request
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        try {
            $data = $request->request->all();
            $this->validatePostData($data);
            $locale = $this->getLocale($request);

            // Calculate prices for all given items.
            $prices = $this->calculateItemPrices($data['items'], $data['currency'], $locale);

            $view = $this->view($prices, 200);
        } catch (PriceCalculationException $pce) {
            $view = $this->view($pce->getMessage(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Calculates prices of all items.
     *
     * @param array $itemsData
     * @param string $currency
     * @param string $locale
     *
     * @return array
     */
    private function calculateItemPrices($itemsData, $currency, $locale)
    {
        // TODO: Move logic to the manager
        $calculator = $this->getItemPriceCalculator();
        $totalNetPrice = 0;
        $totalPrice = 0;
        $items = [];
        $taxes = [];

        foreach ($itemsData as $itemData) {
            $useProductsPrice = false;
            if (isset($itemData['useProductsPrice']) && $itemData['useProductsPrice'] == true) {
                $useProductsPrice = $itemData['useProductsPrice'];
            }


            $itemData = $this->setDefaultData($itemData);
            $itemData = $this->unsetUneccesaryData($itemData);

            $item = $this->getItemManager()->save($itemData, $locale);
            $itemPrice = $calculator->getItemPrice($item, $currency, $useProductsPrice);
            $itemTotalPrice = $calculator->calculate($item, $currency, $useProductsPrice);
            $item->setPrice($itemPrice);
            $item->setTotalNetPrice($itemTotalPrice);

            // Calculate Taxes
            $taxValue = $itemPrice * $item->getTax() / 100.0 * $item->getCalcQuantity();
            $totalPrice += $itemPrice * $item->getCalcQuantity() + $taxValue;
            $tax = (string)$item->getTax();
            $taxes[$tax] = $taxValue;
            if (array_key_exists($tax, $taxes)) {
                $taxes[$tax] = (float)$taxes[$tax] + $taxValue;
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
     * Sets default data to data array, if needed for price calculation
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
     * Checks if all necessary data for post request is set.
     *
     * @param array $data
     *
     * @throws PriceCalculationException
     */
    private function validatePostData($data)
    {
        $required = ['items', 'currency'];

        foreach ($required as $field) {
            if (!isset($data[$field]) && !is_null($data[$field])) {
                throw new PriceCalculationException($field . ' is required but not set properly');
            }
        }
    }

    /**
     * @return ItemPriceCalculator
     */
    private function getItemPriceCalculator()
    {
        return $this->get('sulu_pricing.item_price_calculator');
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
        $itemManagerServiceName = $this->container->getParameter('sulu_pricing.item_manager_service');

        if (!$this->has($itemManagerServiceName)) {
            throw new PriceCalculationException(sprintf('Item manager \'%s\' not found', $itemManagerServiceName));
        }

        return $this->get($itemManagerServiceName);
    }
}
