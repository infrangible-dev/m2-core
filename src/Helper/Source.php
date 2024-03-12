<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Source
{
    /** @var Product */
    protected $productHelper;

    /** @var Instances */
    protected $instanceHelper;

    /** @var bool */
    protected $inventoryAvailable = false;

    /** @var mixed */
    private $sourceItemFactory;

    /** @var mixed */
    private $sourceItemResourceFactory;

    /** @var mixed */
    private $sourceItemCollectionFactory;

    /** @var mixed */
    private $defaultSourceProvider;

    /** @var array */
    private $sourceItemsBySku = [];

    /** @var array */
    private $sourceItemsById = [];

    /**
     * @param Product   $productHelper
     * @param Instances $instanceHelper
     */
    public function __construct(Product $productHelper, Instances $instanceHelper)
    {
        $this->productHelper = $productHelper;
        $this->instanceHelper = $instanceHelper;

        $this->inventoryAvailable = class_exists('Magento\Inventory\Model\SourceItem');
    }

    /**
     * @return mixed
     */
    public function getSourceItemFactory()
    {
        if ($this->sourceItemFactory === null && $this->inventoryAvailable) {
            $this->sourceItemFactory = $this->instanceHelper->getSingleton('Magento\Inventory\Model\SourceItemFactory');
        }

        return $this->sourceItemFactory;
    }

    /**
     * @return mixed
     */
    public function getSourceItemResourceFactory()
    {
        if ($this->sourceItemResourceFactory === null && $this->inventoryAvailable) {
            $this->sourceItemResourceFactory =
                $this->instanceHelper->getSingleton('Magento\Inventory\Model\ResourceModel\SourceItemFactory');
        }

        return $this->sourceItemResourceFactory;
    }

    /**
     * @return mixed
     */
    public function getSourceItemCollectionFactory()
    {
        if ($this->sourceItemCollectionFactory === null && $this->inventoryAvailable) {
            $this->sourceItemCollectionFactory = $this->instanceHelper->getSingleton(
                'Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory'
            );
        }

        return $this->sourceItemCollectionFactory;
    }

    /**
     * @return mixed
     */
    public function getDefaultSourceProvider()
    {
        if ($this->defaultSourceProvider === null && $this->inventoryAvailable) {
            $this->defaultSourceProvider =
                $this->instanceHelper->getSingleton('Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface');
        }

        return $this->defaultSourceProvider;
    }

    /**
     * @return mixed
     */
    public function newSourceItem()
    {
        $sourceItemFactory = $this->getSourceItemFactory();

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $sourceItemFactory ? $this->getSourceItemFactory()->create() : null;
    }

    /**
     * @param int $productId
     *
     * @return mixed
     */
    public function newProductSourceItem(int $productId)
    {
        $skus = $this->productHelper->determineSKUs([$productId]);

        $sku = reset($skus);

        $sourceItem = $this->newSourceItem();

        $sourceItem->setData('sku', $sku);
        $sourceItem->setData('source_code', $this->getDefaultSourceProvider()->getCode());

        return $sourceItem;
    }

    /**
     * @param int $sourceItemId
     *
     * @return mixed
     */
    public function loadSourceItem(int $sourceItemId)
    {
        $sourceItem = $this->newSourceItem();

        $sourceItemResourceFactory = $this->getSourceItemResourceFactory();

        if ($sourceItemResourceFactory) {
            $sourceItemResourceFactory->create()->load($sourceItem, $sourceItemId);
        }

        return $sourceItem;
    }

    /**
     * @param int[]       $productIds
     * @param string|null $sourceCode
     */
    public function loadSourceItemsByProductId(array $productIds, string $sourceCode = null)
    {
        if ($sourceCode === null) {
            $sourceCode = $this->getDefaultSourceProviderCode();
        }

        $skus = $this->productHelper->determineSKUs($productIds, true);
        $entityIds = array_flip($skus);

        $sourceItemCollection = $this->getSourceItemCollection();

        $sourceItemCollection->addFieldToFilter('sku', ['in' => $skus]);
        $sourceItemCollection->addFieldToFilter('source_code', ['eq' => $sourceCode]);

        $sourceItems = $sourceItemCollection->getItems();

        if (is_array($sourceItems)) {
            foreach ($sourceItems as $sourceItem) {
                $sku = $sourceItem->getData('sku');

                $key = sprintf('%s_%s', $sku, $sourceItem->getData('source_code'));

                $this->sourceItemsBySku[$key] = $sourceItem;

                if (array_key_exists($sku, $entityIds)) {
                    $key = sprintf('%s_%s', $entityIds[$sku], $sourceItem->getData('source_code'));

                    $this->sourceItemsById[$key] = $sourceItem;
                }
            }
        }
    }

    /**
     * @param int         $productId
     * @param string|null $sourceCode
     *
     * @return mixed
     */
    public function loadSourceItemByProductId(int $productId, string $sourceCode = null)
    {
        if ($sourceCode === null) {
            $sourceCode = $this->getDefaultSourceProviderCode();
        }

        $key = sprintf('%s_%s', $productId, $sourceCode);

        if (array_key_exists($key, $this->sourceItemsById)) {
            return $this->sourceItemsById[$key];
        }

        $skus = $this->productHelper->determineSKUs([$productId]);

        $sku = reset($skus);

        if (empty($sku)) {
            return null;
        }

        $sourceItem = $this->loadSourceItemByProductSku(strval($productId), $sourceCode);

        $this->sourceItemsById[$key] = $sourceItem;

        return $sourceItem;
    }

    /**
     * @param string      $sku
     * @param string|null $sourceCode
     *
     * @return mixed
     */
    public function loadSourceItemByProductSku(string $sku, string $sourceCode = null)
    {
        if ($sourceCode === null) {
            $sourceCode = $this->getDefaultSourceProviderCode();
        }

        $key = sprintf('%s_%s', $sku, $sourceCode);

        if (array_key_exists($key, $this->sourceItemsBySku)) {
            return $this->sourceItemsBySku[$key];
        }

        $sourceItemCollection = $this->getSourceItemCollection();

        $sourceItemCollection->addFieldToFilter('sku', ['eq' => $sku]);
        $sourceItemCollection->addFieldToFilter('source_code', ['eq' => $sourceCode]);

        $sourceItem = $sourceItemCollection->getFirstItem();

        $this->sourceItemsBySku[$key] = $sourceItem && $sourceItem->getId() ? $sourceItem : null;

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $this->sourceItemsBySku[$key];
    }

    /**
     * @param mixed $sourceItem
     */
    public function saveSourceItem($sourceItem)
    {
        $sourceItemResourceFactory = $this->getSourceItemResourceFactory();

        if ($sourceItemResourceFactory) {
            $sourceItemResourceFactory->create()->save($sourceItem);
        }
    }

    /**
     * @return mixed
     */
    public function getSourceItemCollection()
    {
        $sourceItemCollectionFactory = $this->getSourceItemCollectionFactory();

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $sourceItemCollectionFactory ? $this->getSourceItemCollectionFactory()->create() : null;
    }

    /**
     * @return string|null
     */
    public function getDefaultSourceProviderCode(): ?string
    {
        $defaultSourceProvider = $this->getDefaultSourceProvider();

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $defaultSourceProvider ? $this->getDefaultSourceProvider()->getCode() : null;
    }
}
