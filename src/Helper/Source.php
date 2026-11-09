<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\ResourceModel\SourceItem\Collection;
use Magento\Inventory\Model\SourceItem;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use RuntimeException;

/**
 * Source items of the multi source inventory. Requires the optional module Magento_Inventory (and
 * Magento_InventoryCatalogApi for the default source), which is only loaded when a method is called. Without it, the
 * methods return null or do nothing.
 */
class Source
{
    /** @var bool */
    protected bool $inventoryAvailable = false;

    /** @var array<string, SourceItem|null> */
    private array $sourceItemsBySku = [];

    /** @var array<string, SourceItem|null> */
    private array $sourceItemsById = [];

    /**
     * @param Product $productHelper
     * @param Instances $instanceHelper
     */
    public function __construct(
        protected Product $productHelper,
        protected Instances $instanceHelper
    ) {
        $this->inventoryAvailable = class_exists(SourceItem::class);
    }

    /**
     * Returns the provider of the default source, or null if Magento_InventoryCatalogApi is not installed.
     *
     * @return DefaultSourceProviderInterface|null
     */
    public function getDefaultSourceProvider(): ?DefaultSourceProviderInterface
    {
        if (! interface_exists(DefaultSourceProviderInterface::class)) {
            return null;
        }

        return $this->instanceHelper->getSharedInstance(DefaultSourceProviderInterface::class);
    }

    /**
     * Creates a new source item, or returns null if Magento_Inventory is not installed.
     *
     * @return SourceItem|null
     */
    public function newSourceItem(): ?SourceItem
    {
        return $this->inventoryAvailable ? $this->instanceHelper->createInstance(SourceItem::class) : null;
    }

    /**
     * Creates a new source item of the product in the default source.
     *
     * Returns null if Magento_Inventory is not installed.
     *
     * @param int $productId
     *
     * @return SourceItem|null
     * @throws LocalizedException
     */
    public function newProductSourceItem(int $productId): ?SourceItem
    {
        $sourceItem = $this->newSourceItem();

        if ($sourceItem === null) {
            return null;
        }

        $skus = $this->productHelper->determineSKUs([$productId]);

        $sourceItem->setData('sku', reset($skus));
        $sourceItem->setData('source_code', $this->getDefaultSourceProviderCode());

        return $sourceItem;
    }

    /**
     * Loads the source item with the id, or returns null if Magento_Inventory is not installed.
     *
     * @param int $sourceItemId
     *
     * @return SourceItem|null
     */
    public function loadSourceItem(int $sourceItemId): ?SourceItem
    {
        $sourceItem = $this->newSourceItem();

        if ($sourceItem !== null) {
            $this->getSourceItemResource()->load($sourceItem, $sourceItemId);
        }

        return $sourceItem;
    }

    /**
     * Loads the source items of the products into the cache.
     *
     * The cache is used by loadSourceItemByProductId() and loadSourceItemByProductSku().
     *
     * @param int[]       $productIds
     * @param string|null $sourceCode the default source if null
     *
     * @return void
     * @throws LocalizedException
     */
    public function loadSourceItemsByProductId(array $productIds, ?string $sourceCode = null): void
    {
        $sourceItemCollection = $this->getSourceItemCollection();

        if ($sourceItemCollection === null) {
            return;
        }

        $sourceCode ??= $this->getDefaultSourceProviderCode();

        $skus = $this->productHelper->determineSKUs($productIds, true);
        $entityIds = array_flip($skus);

        $sourceItemCollection->addFieldToFilter('sku', ['in' => $skus]);
        $sourceItemCollection->addFieldToFilter('source_code', ['eq' => $sourceCode]);

        /** @var SourceItem $sourceItem */
        foreach ($sourceItemCollection->getItems() as $sourceItem) {
            $sku = (string)$sourceItem->getSku();
            $itemSourceCode = (string)$sourceItem->getSourceCode();

            $this->sourceItemsBySku[ sprintf('%s_%s', $sku, $itemSourceCode) ] = $sourceItem;

            if (array_key_exists($sku, $entityIds)) {
                $this->sourceItemsById[ sprintf('%s_%s', $entityIds[ $sku ], $itemSourceCode) ] = $sourceItem;
            }
        }
    }

    /**
     * Loads the source item of the product.
     *
     * @param int         $productId
     * @param string|null $sourceCode the default source if null
     *
     * @return SourceItem|null null if the product has no source item or Magento_Inventory is not installed
     * @throws LocalizedException
     */
    public function loadSourceItemByProductId(int $productId, ?string $sourceCode = null): ?SourceItem
    {
        $sourceCode ??= $this->getDefaultSourceProviderCode();

        $key = sprintf('%s_%s', $productId, $sourceCode);

        if (array_key_exists($key, $this->sourceItemsById)) {
            return $this->sourceItemsById[ $key ];
        }

        $skus = $this->productHelper->determineSKUs([$productId]);

        $sku = reset($skus);

        if (empty($sku)) {
            return null;
        }

        $sourceItem = $this->loadSourceItemByProductSku($sku, $sourceCode);

        $this->sourceItemsById[ $key ] = $sourceItem;

        return $sourceItem;
    }

    /**
     * Loads the source item of the product with the SKU.
     *
     * @param string      $sku
     * @param string|null $sourceCode the default source if null
     *
     * @return SourceItem|null null if the product has no source item or Magento_Inventory is not installed
     * @throws LocalizedException
     */
    public function loadSourceItemByProductSku(string $sku, ?string $sourceCode = null): ?SourceItem
    {
        $sourceCode ??= $this->getDefaultSourceProviderCode();

        $key = sprintf('%s_%s', $sku, $sourceCode);

        if (array_key_exists($key, $this->sourceItemsBySku)) {
            return $this->sourceItemsBySku[ $key ];
        }

        $sourceItemCollection = $this->getSourceItemCollection();

        if ($sourceItemCollection === null) {
            return null;
        }

        $sourceItemCollection->addFieldToFilter('sku', ['eq' => $sku]);
        $sourceItemCollection->addFieldToFilter('source_code', ['eq' => $sourceCode]);

        $sourceItem = $sourceItemCollection->getFirstItem();

        $this->sourceItemsBySku[ $key ] =
            $sourceItem instanceof SourceItem && $sourceItem->getId() ? $sourceItem : null;

        return $this->sourceItemsBySku[ $key ];
    }

    /**
     * Saves the source item.
     *
     * @param SourceItem $sourceItem
     *
     * @return void
     * @throws Exception
     */
    public function saveSourceItem(SourceItem $sourceItem): void
    {
        $this->getSourceItemResource()->save($sourceItem);
    }

    /**
     * Returns a new collection of source items, or null if Magento_Inventory is not installed.
     *
     * @return Collection|null
     */
    public function getSourceItemCollection(): ?Collection
    {
        return $this->inventoryAvailable ? $this->instanceHelper->createInstance(Collection::class) : null;
    }

    /**
     * Returns the code of the default source, or null if Magento_InventoryCatalogApi is not installed.
     *
     * @return string|null
     */
    public function getDefaultSourceProviderCode(): ?string
    {
        return $this->getDefaultSourceProvider()?->getCode();
    }

    /**
     * Returns the resource model of source items.
     *
     * @return \Magento\Inventory\Model\ResourceModel\SourceItem
     * @throws RuntimeException if Magento_Inventory is not installed
     */
    private function getSourceItemResource(): \Magento\Inventory\Model\ResourceModel\SourceItem
    {
        return $this->instanceHelper->getSharedInstance(\Magento\Inventory\Model\ResourceModel\SourceItem::class);
    }
}
