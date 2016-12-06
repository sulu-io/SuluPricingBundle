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
use Sulu\Bundle\PricingBundle\Manager\PriceCalculationManager;
use Sulu\Bundle\PricingBundle\Pricing\Exceptions\PriceCalculationException;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles price calculations by api.
 */
class PricingController extends RestController implements ClassResourceInterface
{
    /**
     * Calculates total prices of all given items.
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

            // Calculate prices for given item
            $price = $this->getPriceCalculationManager()->retrieveItemPrices(
                $data['items'],
                $data['currency'],
                $locale
            );

            $view = $this->view($price, 200);
        } catch (PriceCalculationException $pce) {
            $view = $this->view($pce->getMessage(), 400);
        }

        return $this->handleView($view);
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
     * @return PriceCalculationManager
     */
    private function getPriceCalculationManager()
    {
        return $this->get('sulu_pricing.price_calculation_manager');
    }
}
