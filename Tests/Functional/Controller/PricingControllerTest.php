<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PricingBundle\Tests\Functional\Controller;

use Sulu\Bundle\ProductBundle\Tests\Resources\ProductTestData;
use Sulu\Bundle\PricingBundle\Tests\Resources\BaseTestCase;

/**
 * Testing Pricing controller.
 */
class PricingControllerTest extends BaseTestCase
{
    protected $locale = 'en';

    /**
     * @var OrderDataSetup
     */
    protected $data;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ProductTestData
     */
    protected $productData;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
        $this->setUpTestData();
        $this->em->flush();
        $this->client = $this->createAuthenticatedClient();
    }

    /**
     * Setup test data.
     */
    protected function setUpTestData()
    {
        $this->productData = new ProductTestData($this->getContainer());
    }

    /**
     * Simple test for pricing api.
     */
    public function testSimplePricing()
    {
        $itemData = [
            $this->getItemSampleData(),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/pricings',
            [
                'taxfree' => true,
                'currency' => 'EUR',
                'items' => $itemData
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $response = json_decode($response->getContent());

        $totalNetPrice = $itemData[0]['price'] * $itemData[0]['quantity'];
        $this->assertEquals(
            $itemData[0]['price'],
            $response->items[0]->price,
            'Wrong item price'
        );
        $this->assertEquals(
            $totalNetPrice,
            $response->items[0]->totalNetPrice,
            'Wrong item net price'
        );
        // Since the taxfree flag is true, the net price equals the total price.
        $this->assertEquals(
            $totalNetPrice,
            $response->totalNetPrice,
            'Wrong total net price'
        );
        $this->assertEquals(
            $totalNetPrice,
            $response->totalPrice,
            'Wrong total price'
        );
    }

    /**
     * Simple test for brutto prices.
     */
    public function testSimpleBruttoPricing()
    {
        $itemData = [
            $this->getItemSampleData(1, 2),
            $this->getItemSampleData(2, 1)
        ];

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/pricings',
            [
                'taxfree' => false,
                'currency' => 'EUR',
                'items' => $itemData
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $response = json_decode($response->getContent());

        $totalNetPrice1 = $itemData[0]['price'] * $itemData[0]['quantity'];
        $totalNetPrice2 = $itemData[1]['price'] * $itemData[1]['quantity'];
        $totalNetPrice = $totalNetPrice1 + $totalNetPrice2;

        $this->assertEquals($itemData[0]['price'], $response->items[0]->price);

        $this->assertEquals($totalNetPrice, $response->totalNetPrice);
        $this->assertEquals($totalNetPrice += $totalNetPrice * 0.2, $response->totalPrice);

        $taxes = get_object_vars($response->taxes);
        $this->assertEquals($taxes['20'], 13.14);
    }

    /**
     * Simple test for pricing api.
     */
    public function testMultiplePricings()
    {
        $itemData = [
            $this->getItemSampleData(),
            $this->getItemSampleData(),
            $this->getItemSampleData(),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/pricings',
            [
                'taxfree' => true,
                'currency' => 'EUR',
                'items' => $itemData
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $response = json_decode($response->getContent());

        $totalNetPrice = 0;
        foreach ($itemData as $index => $data) {
            $this->assertEquals($itemData[$index]['price'], $response->items[$index]->price);
            $this->assertEquals(
                $itemData[$index]['price'] * $itemData[$index]['quantity'],
                $response->items[$index]->totalNetPrice
            );
            $totalNetPrice += $response->items[$index]->totalNetPrice;
        }

        $this->assertEquals($totalNetPrice, $response->totalNetPrice);
    }

    /**
     * Returns sample data for item.
     *
     * @return array
     */
    private function getItemSampleData($productId = 1, $quantity = 1.0)
    {
        return [
            'id' => 1,
            'name' => 'name',
            'quantity' => $quantity,
            'quantityUnit' => 'pc',
            'useProductsPrice' => false,
            'price' => 21.90,
            'discount' => 0,
            'product' => [
                'id' => $productId,
            ],
        ];
    }
}
