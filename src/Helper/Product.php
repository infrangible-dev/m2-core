<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Zend_Db_Select;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Product
{
    /** @var Arrays */
    protected $arrays;

    /** @var Attribute */
    protected $attributeHelper;

    /** @var Database */
    protected $databaseHelper;

    /** @var Data */
    protected $catalogHelper;

    /** @var LoggerInterface */
    protected $logging;

    /** @var ProductFactory */
    protected $productFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\ProductFactory */
    protected $productResourceFactory;

    /** @var CollectionFactory */
    protected $productCollectionFactory;

    /** @var Config */
    protected $productMediaConfig;

    /** @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\CollectionFactory */
    protected $configurableAttributeCollectionFactory;

    /** @var ConfigInterface */
    protected $config;

    /** @var array */
    private $entitySkus = [];

    /** @var array */
    private $websiteIds = [];

    /** @var array */
    private $types;

    /** @var array */
    private $categoryIds = [];

    /**
     * @param Arrays                                                                                                 $arrays
     * @param Attribute                                                                                              $attributeHelper
     * @param Database                                                                                               $databaseHelper
     * @param Data                                                                                                   $catalogHelper
     * @param LoggerInterface                                                                                        $logging
     * @param ProductFactory                                                                                         $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\ProductFactory                                                    $productResourceFactory
     * @param CollectionFactory                                                                                      $productCollectionFactory
     * @param Config                                                                                                 $productMediaConfig
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\CollectionFactory $configurableAttributeCollectionFactory
     * @param ConfigInterface                                                                                        $config
     */
    public function __construct(
        Arrays $arrays,
        Attribute $attributeHelper,
        Database $databaseHelper,
        Data $catalogHelper,
        LoggerInterface $logging,
        ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $productResourceFactory,
        CollectionFactory $productCollectionFactory,
        Config $productMediaConfig,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\CollectionFactory $configurableAttributeCollectionFactory,
        ConfigInterface $config
    ) {
        $this->arrays = $arrays;
        $this->attributeHelper = $attributeHelper;
        $this->databaseHelper = $databaseHelper;
        $this->catalogHelper = $catalogHelper;

        $this->logging = $logging;
        $this->productFactory = $productFactory;
        $this->productResourceFactory = $productResourceFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productMediaConfig = $productMediaConfig;
        $this->configurableAttributeCollectionFactory = $configurableAttributeCollectionFactory;
        $this->config = $config;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    public function newProduct(): \Magento\Catalog\Model\Product
    {
        return $this->productFactory->create();
    }

    /**
     * @param int      $productId
     * @param int|null $storeId
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function loadProduct(int $productId, int $storeId = null): \Magento\Catalog\Model\Product
    {
        $product = $this->newProduct();

        if (!empty($storeId)) {
            $product->setStoreId($storeId);
        }

        $this->productResourceFactory->create()->load($product, $productId);

        return $product;
    }

    /**
     * @param string   $productSku
     * @param int|null $storeId
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function loadProductBySku(string $productSku, int $storeId = null): \Magento\Catalog\Model\Product
    {
        $product = $this->newProduct();

        $productId = $product->getIdBySku($productSku);

        if (!empty($storeId)) {
            $product->setDataUsingMethod('store_id', $storeId);
        }

        $this->productResourceFactory->create()->load($product, $productId);

        return $product;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @throws Exception
     */
    public function saveProduct(\Magento\Catalog\Model\Product $product)
    {
        $this->productResourceFactory->create()->save($product);
    }

    /**
     * @return Collection
     */
    public function getProductCollection(): Collection
    {
        return $this->productCollectionFactory->create();
    }

    /**
     * @return Config
     */
    public function getProductMediaConfig(): Config
    {
        return $this->productMediaConfig;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $parentIds
     * @param bool             $excludeInactive
     * @param bool             $excludeOutOfStock
     * @param bool             $maintainAssociation
     * @param bool             $useSuperLink
     * @param bool             $includeParents
     * @param int|null         $storeId
     *
     * @return array
     * @throws Exception
     */
    public function getChildIds(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        bool $excludeOutOfStock = false,
        bool $maintainAssociation = false,
        bool $useSuperLink = true,
        bool $includeParents = false,
        int $storeId = null
    ): array {
        $this->logging->debug(sprintf('Searching child ids for parent id(s): %s', implode(', ', $parentIds)));

        $childIdQuery =
            $this->getChildIdQuery($dbAdapter, $parentIds, $excludeInactive, $useSuperLink, $includeParents, $storeId);

        if ($excludeOutOfStock) {
            $tableName = $this->databaseHelper->getTableName(
                $useSuperLink ? 'catalog_product_super_link' : 'catalog_product_relation'
            );
            $childColumnName = $useSuperLink ? 'product_id' : 'child_id';

            $childIdQuery->join(['stock_item' => $this->databaseHelper->getTableName('cataloginventory_stock_item')],
                                sprintf(
                                    '%s = %s',
                                    $dbAdapter->quoteIdentifier('stock_item.product_id'),
                                    $dbAdapter->quoteIdentifier(sprintf('%s.%s', $tableName, $childColumnName))
                                ),
                                []);

            $childIdQuery->where(
                $dbAdapter->prepareSqlCondition(
                    $dbAdapter->quoteIdentifier('stock_item.is_in_stock'),
                    ['eq' => 1]
                ),
                null,
                Select::TYPE_CONDITION
            );
        }

        $queryResult = $this->databaseHelper->fetchAssoc($childIdQuery, $dbAdapter);

        if ($maintainAssociation) {
            $childIds = [];

            foreach ($queryResult as $childId => $queryRow) {
                $childIds[(int) $childId] = (int) $this->arrays->getValue($queryRow, 'parent_id');
            }
        } else {
            $childIds = array_keys($queryResult);

            $childIds = array_map('intval', $childIds);
        }

        $this->logging->debug(sprintf('Found %d child id(s)', count($childIds)));

        return $childIds;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $parentIds
     * @param bool             $excludeInactive
     * @param bool             $useSuperLink
     * @param bool             $includeParents
     * @param int|null         $storeId
     *
     * @return Select
     * @throws Exception
     */
    public function getChildIdQuery(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        bool $useSuperLink = true,
        bool $includeParents = false,
        int $storeId = null
    ): Select {
        $tableName = $this->databaseHelper->getTableName(
            $useSuperLink ? 'catalog_product_super_link' : 'catalog_product_relation'
        );
        $childColumnName = $useSuperLink ? 'product_id' : 'child_id';

        $childIdQuery = $dbAdapter->select()->from([$tableName], [
            $childColumnName,
            'parent_id'
        ]);

        $childIdQuery->where(
            $dbAdapter->prepareSqlCondition(
                $dbAdapter->quoteIdentifier(
                    sprintf(
                        '%s.parent_id',
                        $tableName
                    )
                ),
                ['in' => $parentIds]
            ),
            null,
            Select::TYPE_CONDITION
        );

        if ($includeParents) {
            // in case child ids are included in the parent id list
            $childIdQuery->orWhere(
                $dbAdapter->prepareSqlCondition(
                    sprintf('%s.%s', $tableName, $childColumnName),
                    ['in' => $parentIds]
                ),
                null,
                Select::TYPE_CONDITION
            );
        }

        if ($excludeInactive) {
            $statusAttribute = $this->attributeHelper->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'status');

            if (empty($storeId)) {
                $childIdQuery->join(['status0' => $statusAttribute->getBackend()->getTable()],
                                    $dbAdapter->quoteInto(
                                        sprintf(
                                            '%s = %s AND %s = ? AND %s = 0',
                                            $dbAdapter->quoteIdentifier('status0.entity_id'),
                                            $dbAdapter->quoteIdentifier(sprintf('%s.%s', $tableName, $childColumnName)),
                                            $dbAdapter->quoteIdentifier('status0.attribute_id'),
                                            $dbAdapter->quoteIdentifier('status0.store_id')
                                        ),
                                        $statusAttribute->getAttributeId()
                                    ),
                                    []);

                $childIdQuery->where(
                    $dbAdapter->prepareSqlCondition(
                        $dbAdapter->quoteIdentifier('status0.value'),
                        ['eq' => Status::STATUS_ENABLED]
                    ),
                    null,
                    Select::TYPE_CONDITION
                );
            } else {
                $childIdQuery->joinLeft(['status0' => $statusAttribute->getBackend()->getTable()],
                                        $dbAdapter->quoteInto(
                                            sprintf(
                                                '%s = %s AND %s = ? AND %s = 0',
                                                $dbAdapter->quoteIdentifier('status0.entity_id'),
                                                $dbAdapter->quoteIdentifier(
                                                    sprintf('%s.%s', $tableName, $childColumnName)
                                                ),
                                                $dbAdapter->quoteIdentifier('status0.attribute_id'),
                                                $dbAdapter->quoteIdentifier('status0.store_id')
                                            ),
                                            $statusAttribute->getAttributeId()
                                        ),
                                        []);

                $tableAlias = sprintf('status_%d', $storeId);

                $childIdQuery->joinLeft([$tableAlias => $statusAttribute->getBackend()->getTable()],
                                        sprintf(
                                            '%s = %s AND %s = %d AND %s = %d',
                                            $dbAdapter->quoteIdentifier(sprintf('%s.entity_id', $tableAlias)),
                                            $dbAdapter->quoteIdentifier(sprintf('%s.%s', $tableName, $childColumnName)),
                                            $dbAdapter->quoteIdentifier(sprintf('%s.attribute_id', $tableAlias)),
                                            $statusAttribute->getAttributeId(),
                                            $dbAdapter->quoteIdentifier(sprintf('%s.store_id', $tableAlias)),
                                            $storeId
                                        ),
                                        []);

                $childIdQuery->where(
                    $dbAdapter->getIfNullSql(
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.value',
                                $tableAlias
                            )
                        ),
                        $dbAdapter->quoteIdentifier('status0.value')
                    ).' = ?',
                    Status::STATUS_ENABLED
                );
            }
        }

        return $childIdQuery;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $parentIds
     * @param bool             $excludeInactive
     * @param bool             $excludeOutOfStock
     * @param bool             $maintainAssociation
     * @param int|null         $storeId
     *
     * @return array
     * @throws Exception
     */
    public function getBundledIds(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        bool $excludeOutOfStock = false,
        bool $maintainAssociation = false,
        int $storeId = null
    ): array {
        $this->logging->debug(sprintf('Searching bundled ids for parent id(s): %s', implode(', ', $parentIds)));

        $buildIdQuery = $this->getBundledIdQuery($dbAdapter, $parentIds, $excludeInactive, $storeId);

        if ($excludeOutOfStock) {
            $tableName = $this->databaseHelper->getTableName('catalog_product_bundle_selection');

            $buildIdQuery->join(['stock_item' => $this->databaseHelper->getTableName('cataloginventory_stock_item')],
                                sprintf(
                                    '%s = %s',
                                    $dbAdapter->quoteIdentifier('stock_item.product_id'),
                                    $dbAdapter->quoteIdentifier(sprintf('%s.%s', $tableName, 'product_id'))
                                ),
                                []);

            $buildIdQuery->where(
                $dbAdapter->prepareSqlCondition(
                    $dbAdapter->quoteIdentifier('stock_item.is_in_stock'),
                    ['eq' => 1]
                ),
                null,
                Select::TYPE_CONDITION
            );
        }

        $queryResult = $this->databaseHelper->fetchAssoc($buildIdQuery, $dbAdapter);

        if ($maintainAssociation) {
            $childIds = [];

            foreach ($queryResult as $childId => $queryRow) {
                $childIds[(int) $childId] = (int) $this->arrays->getValue($queryRow, 'parent_product_id');
            }
        } else {
            $childIds = array_keys($queryResult);

            $childIds = array_map('intval', $childIds);
        }

        $this->logging->debug(sprintf('Found %d bundled id(s)', count($childIds)));

        return $childIds;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $parentIds
     * @param bool             $excludeInactive
     * @param int|null         $storeId
     *
     * @return Select
     * @throws Exception
     */
    public function getBundledIdQuery(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        int $storeId = null
    ): Select {
        $tableName = $this->databaseHelper->getTableName('catalog_product_bundle_selection');

        $bundleIdQuery = $dbAdapter->select()->from([$tableName], [
            'product_id',
            'parent_product_id'
        ]);

        $bundleIdQuery->where(
            $dbAdapter->prepareSqlCondition(
                $dbAdapter->quoteIdentifier(
                    sprintf(
                        '%s.parent_product_id',
                        $tableName
                    )
                ),
                ['in' => $parentIds]
            ),
            null,
            Select::TYPE_CONDITION
        );

        if ($excludeInactive) {
            $statusAttribute = $this->attributeHelper->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'status');

            if (empty($storeId)) {
                $bundleIdQuery->join(['status_0' => $statusAttribute->getBackend()->getTable()],
                                     $dbAdapter->quoteInto(
                                         sprintf(
                                             '%s = %s AND %s = ? AND %s = 0',
                                             $dbAdapter->quoteIdentifier('status_0.entity_id'),
                                             $dbAdapter->quoteIdentifier(sprintf('%s.%s', $tableName, 'product_id')),
                                             $dbAdapter->quoteIdentifier('status_0.attribute_id'),
                                             $dbAdapter->quoteIdentifier('status_0.store_id')
                                         ),
                                         $statusAttribute->getAttributeId()
                                     ),
                                     []);

                $bundleIdQuery->where(
                    $dbAdapter->prepareSqlCondition(
                        $dbAdapter->quoteIdentifier('status_0.value'),
                        ['eq' => Status::STATUS_ENABLED]
                    ),
                    null,
                    Select::TYPE_CONDITION
                );
            } else {
                $bundleIdQuery->joinLeft(['status_0' => $statusAttribute->getBackend()->getTable()],
                                         $dbAdapter->quoteInto(
                                             sprintf(
                                                 '%s = %s AND %s = ? AND %s = 0',
                                                 $dbAdapter->quoteIdentifier('status_0.entity_id'),
                                                 $dbAdapter->quoteIdentifier(
                                                     sprintf('%s.%s', $tableName, 'product_id')
                                                 ),
                                                 $dbAdapter->quoteIdentifier('status_0.attribute_id'),
                                                 $dbAdapter->quoteIdentifier('status_0.store_id')
                                             ),
                                             $statusAttribute->getAttributeId()
                                         ),
                                         []);

                $tableAlias = sprintf('status_%d', $storeId);

                $bundleIdQuery->joinLeft([$tableAlias => $statusAttribute->getBackend()->getTable()],
                                         sprintf(
                                             '%s = %s AND %s = %d AND %s = %d',
                                             $dbAdapter->quoteIdentifier(sprintf('%s.entity_id', $tableAlias)),
                                             $dbAdapter->quoteIdentifier(sprintf('%s.%s', $tableName, 'product_id')),
                                             $dbAdapter->quoteIdentifier(sprintf('%s.attribute_id', $tableAlias)),
                                             $statusAttribute->getAttributeId(),
                                             $dbAdapter->quoteIdentifier(sprintf('%s.store_id', $tableAlias)),
                                             $storeId
                                         ),
                                         []);

                $bundleIdQuery->where(
                    $dbAdapter->getIfNullSql(
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.value',
                                $tableAlias
                            )
                        ),
                        $dbAdapter->quoteIdentifier('status_0.value')
                    ).' = ?',
                    Status::STATUS_ENABLED
                );
            }
        }

        return $bundleIdQuery;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $parentIds
     * @param bool             $excludeInactive
     * @param bool             $excludeOutOfStock
     * @param bool             $maintainAssociation
     * @param bool             $includeParents
     * @param int|null         $storeId
     *
     * @return array
     * @throws Exception
     */
    public function getGroupedIds(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        bool $excludeOutOfStock = false,
        bool $maintainAssociation = false,
        bool $includeParents = false,
        int $storeId = null
    ): array {
        return $this->getChildIds(
            $dbAdapter,
            $parentIds,
            $excludeInactive,
            $excludeOutOfStock,
            $maintainAssociation,
            false,
            $includeParents,
            $storeId
        );
    }

    /**
     * @return \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\Collection
     */
    public function getConfigurableAttributeCollection(
    ): \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\Collection
    {
        return $this->configurableAttributeCollectionFactory->create();
    }

    /**
     * @param array $entityIds
     * @param bool  $keepAssociation
     *
     * @return array
     */
    public function determineSKUs(array $entityIds, bool $keepAssociation = false): array
    {
        $skus = [];

        $this->logging->debug(sprintf('Determining skus for %d entity id(s)', count($entityIds)));

        foreach ($entityIds as $key => $entityId) {
            if (array_key_exists($entityId, $this->entitySkus)) {
                $skus[$key] = $this->entitySkus[$entityId];

                unset($entityIds[$key]);
            }
        }

        $this->logging->debug(sprintf('Searching skus for %d entity id(s)', count($entityIds)));

        if (!empty($entityIds)) {
            $entityIdChunks = array_chunk($entityIds, 1000, true);

            foreach ($entityIdChunks as $entityIdChunk) {
                $productCollection = $this->getProductCollection();

                $productCollection->getSelect()->reset(Zend_Db_Select::COLUMNS);
                $productCollection->getSelect()->columns([
                                                             'entity_id',
                                                             'sku'
                                                         ]);

                $productCollection->addAttributeToFilter('entity_id', ['in' => $entityIdChunk]);

                $productData = $this->databaseHelper->fetchAssoc($productCollection->getSelect());

                foreach ($entityIdChunk as $elementNumber => $entityId) {
                    if (array_key_exists($entityId, $productData) && array_key_exists('sku', $productData[$entityId])) {
                        $skus[$keepAssociation ? $entityId : $elementNumber] = $productData[$entityId]['sku'];

                        $this->entitySkus[$entityId] = $productData[$entityId]['sku'];
                    }
                }
            }
        }

        $this->logging->debug(sprintf('Found %d sku(s)', count($skus)));

        return $skus;
    }

    /**
     * @param array $skus
     *
     * @return array
     */
    public function determineEntityIds(array $skus): array
    {
        $entityIds = [];

        $this->logging->debug(sprintf('Determining entity ids for %d sku(s)', count($skus)));

        $entitySkus = array_flip($this->entitySkus);

        foreach ($skus as $key => $sku) {
            $entityId = array_key_exists($sku, $entitySkus) ? $entitySkus[$sku] : false;

            if ($entityId !== false) {
                $entityIds[$key] = $entityId;

                unset($skus[$key]);
            }
        }

        $this->logging->debug(sprintf('Searching entity ids for %d sku(s)', count($skus)));

        if (!empty($skus)) {
            $skuChunks = array_chunk($skus, 1000, true);

            foreach ($skuChunks as $skuChunk) {
                $productCollection = $this->getProductCollection();

                $productCollection->getSelect()->reset(Zend_Db_Select::COLUMNS);
                $productCollection->getSelect()->columns([
                                                             'sku',
                                                             'entity_id'
                                                         ]);

                $productCollection->addAttributeToFilter('sku', ['in' => $skuChunk]);

                $productData = $this->databaseHelper->fetchAssoc($productCollection->getSelect());

                foreach ($skuChunk as $elementNumber => $sku) {
                    if (array_key_exists($sku, $productData) && array_key_exists('entity_id', $productData[$sku])) {
                        $entityIds[$elementNumber] = $productData[$sku]['entity_id'];

                        $this->entitySkus[$productData[$sku]['entity_id']] = $sku;
                    }
                }
            }
        }

        $this->logging->debug(sprintf('Found %d entity id(s)', count($entityIds)));

        return $entityIds;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $childIds
     * @param bool             $maintainAssociation
     * @param bool             $useSuperLink
     * @param bool             $includeChildren
     *
     * @return array
     */
    public function getParentIds(
        AdapterInterface $dbAdapter,
        array $childIds,
        bool $maintainAssociation = false,
        bool $useSuperLink = true,
        bool $includeChildren = false
    ): array {
        $this->logging->debug(sprintf('Searching parent ids for child id(s): %s', implode(', ', $childIds)));

        $tableName = $this->databaseHelper->getTableName(
            $useSuperLink ? 'catalog_product_super_link' : 'catalog_product_relation'
        );

        $childColumnName = $useSuperLink ? 'product_id' : 'child_id';

        $superLinkQuery = $dbAdapter->select()->from([$tableName], [
            'parent_id',
            $childColumnName
        ]);

        $superLinkQuery->where(
            $dbAdapter->prepareSqlCondition($childColumnName, ['in' => $childIds]),
            null,
            Select::TYPE_CONDITION
        );

        if ($includeChildren) {
            // in case parent ids are included in the child id list
            $superLinkQuery->orWhere(
                $dbAdapter->prepareSqlCondition('parent_id', ['in' => $childIds]),
                null,
                Select::TYPE_CONDITION
            );
        }

        $queryResult = $this->databaseHelper->fetchAssoc($superLinkQuery);

        if ($maintainAssociation) {
            $parentIds = array_values($queryResult);
        } else {
            $parentIds = array_keys($queryResult);
        }

        $parentIds = array_map('intval', $parentIds);

        $this->logging->debug(sprintf('Found %d parent id(s)', count($parentIds)));

        return $parentIds;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param int              $productId
     *
     * @return array
     */
    public function getWebsiteIds(AdapterInterface $dbAdapter, int $productId): array
    {
        if (!array_key_exists($productId, $this->websiteIds)) {
            $tableName = $this->databaseHelper->getTableName('catalog_product_website');

            $websiteQuery = $dbAdapter->select()->from([$tableName], ['website_id']);

            $websiteQuery->where(
                $dbAdapter->prepareSqlCondition('product_id', ['eq' => $productId]),
                null,
                Select::TYPE_CONDITION
            );

            $queryResult = $this->databaseHelper->fetchAssoc($websiteQuery);

            $this->websiteIds[$productId] = array_keys($queryResult);
        }

        return $this->websiteIds[$productId];
    }

    /**
     * Get product types
     *
     * @return array
     */
    public function getTypes(): array
    {
        if ($this->types === null) {
            $productTypes = $this->config->getAll();

            foreach ($productTypes as $productTypeKey => $productTypeConfig) {
                $productTypes[$productTypeKey]['label'] = __($productTypeConfig['label']);
            }

            $this->types = $productTypes;
        }

        return $this->types;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $productIds
     *
     * @return array
     */
    public function getCategoryIds(AdapterInterface $dbAdapter, array $productIds): array
    {
        $result = [];

        $loadProductIds = [];

        foreach ($productIds as $productId) {
            if (array_key_exists($productId, $this->categoryIds)) {
                $result[$productId] = $this->categoryIds[$productId];
            } else {
                $loadProductIds[] = $productId;
            }
        }

        if (!empty($loadProductIds)) {
            $categoryQuery = $this->databaseHelper->select(
                $this->databaseHelper->getTableName('catalog_category_product'),
                ['product_id', 'category_id']
            );

            $categoryQuery->where('product_id IN (?)', $loadProductIds);

            $queryResult = $this->databaseHelper->fetchAll($categoryQuery, $dbAdapter);

            foreach ($queryResult as $row) {
                $productId = $this->arrays->getValue($row, 'product_id');
                $categoryId = $this->arrays->getValue($row, 'category_id');

                $this->categoryIds[$productId][] = $categoryId;
                $result[$productId][] = $categoryId;
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param float                          $price
     * @param null|bool                      $includingTax
     * @param AbstractAddress|null           $shippingAddress
     * @param AbstractAddress|null           $billingAddress
     * @param int|null                       $ctc
     * @param null|string|bool|int|Store     $store
     * @param bool|null                      $priceIncludesTax
     * @param bool                           $roundPrice
     *
     * @return  float
     */
    public function getTaxPrice(
        \Magento\Catalog\Model\Product $product,
        float $price,
        ?bool $includingTax = null,
        ?AbstractAddress $shippingAddress = null,
        ?AbstractAddress $billingAddress = null,
        ?int $ctc = null,
        $store = null,
        ?bool $priceIncludesTax = null,
        ?bool $roundPrice = true
    ): float {
        return $this->catalogHelper->getTaxPrice(
            $product,
            $price,
            $includingTax,
            $shippingAddress,
            $billingAddress,
            $ctc,
            $store,
            $priceIncludesTax,
            $roundPrice
        );
    }
}
