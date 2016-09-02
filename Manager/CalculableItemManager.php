<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PricingBundle\Manager;

use Sulu\Bundle\PricingBundle\Model\CalculableItem;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;
use Sulu\Bundle\ProductBundle\Product\ProductRepositoryInterface;

class CalculableItemManager
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * @param array $data
     *
     * @return CalculableItem
     */
    public function createCalculableItem($data)
    {
        $quantity = $this->getProperty($data, 'quantity');
        $discount = $this->getProperty($data, 'discount');
        $tax = $this->getProperty($data, 'tax');
        $price = $this->getProperty($data, 'price');
        $isRecurringPrice = $this->getProperty($data, 'isRecurringPrice', false);
        $currencyCode = $this->getProperty($data, 'currencyCode');
        $useProductsPrice = $this->getProperty($data, 'useProductsPrice', true);

        // Get Product data.
        $productData = $this->getProperty($data, 'product');
        $addonData = $this->getProperty($data, 'addon');
        $product = $this->getProductByData($productData);
        $addon = $this->getProductByData($addonData);

        $item = new CalculableItem(
            $quantity,
            $price,
            $discount,
            $tax,
            $currencyCode,
            $isRecurringPrice,
            $useProductsPrice,
            $product,
            $addon
        );

        return $item;
    }

    /**
     * Returns the entry of associative data array with the given key, if it exists.
     * Otherwise the $default is returned.
     *
     * @param array $data
     * @param string $key
     * @param string $default
     *
     * @return mixed
     */
    protected function getProperty(array $data, $key, $default = null)
    {
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    /**
     * @param array $productData
     *
     * @return ProductInterface|null
     */
    private function getProductByData($productData)
    {
        $product = null;

        if (isset($productData['id'])) {
            $product = $this->productRepository->find($productData['id']);
        }

        return $product;
    }
}
