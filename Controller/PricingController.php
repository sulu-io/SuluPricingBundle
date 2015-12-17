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
use Sulu\Bundle\PricingBundle\Pricing\Exceptions\PriceCalculationException;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Bundle\PricingBundle\Pricing\ItemPriceCalculator;
use Sulu\Component\Rest\RestController;

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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $data = $request->request->all();

            $locale = $this->getLocale($request);

            $prices = $this->calculateItemPrices($data['items'], $data['currency'], $data['taxfree'], $locale);

            $view = $this->view($prices['items'], 200);
        } catch (OrderDependencyNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 400);
        } catch (MissingOrderAttributeException $exc) {
            $exception = new MissingArgumentException(self::$orderEntityName, $exc->getAttribute());
            $view = $this->view($exception->toArray(), 400);
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
     * @param bool $taxfree
     * @param string $locale
     *
     * @return array
     */
    private function calculateItemPrices($itemsData, $currency, $taxfree, $locale)
    {
        $calculator = $this->getItemPriceCalculator();
        $totalPrice = 0;
        $items = [];

        foreach ($itemsData as $itemData) {
            $useProductsPrice = false;
            if (isset($itemData['useProductsPrice']) && $itemData['useProductsPrice'] == true) {
                $useProductsPrice = $itemData['useProductsPrice'];
            }

            $item = $this->getItemManager()->save($itemData, $locale);
            $itemPrice = $calculator->getItemPrice($item, $currency, $useProductsPrice);
            $itemTotalPrice = $calculator->calculate($item, $currency, $useProductsPrice);
            $item->setPrice($itemPrice);
            $item->setTotalNetPrice($itemTotalPrice);

            $items[] = $item;
            $totalPrice += $itemPrice;
        }

        return [
            'total' => $totalPrice,
            'items' => $items,
        ];
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
