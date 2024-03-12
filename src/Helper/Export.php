<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Helper\Data;
use Psr\Log\LoggerInterface;
use Zend_Db_Select;
use Zend_Db_Select_Exception;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Export
{
    /** @var Variables */
    protected $variables;

    /** @var Arrays */
    protected $arrays;

    /** @var Database */
    protected $databaseHelper;

    /** @var Stores */
    protected $storeHelper;

    /** @var \Infrangible\Core\Helper\Attribute */
    protected $attributeHelper;

    /** @var Product */
    protected $productHelper;

    /** @var Category */
    protected $categoryHelper;

    /** @var Customer */
    protected $customerHelper;

    /** @var Address */
    protected $addressHelper;

    /** @var Data */
    protected $taxHelper;

    /** @var LoggerInterface */
    protected $logging;

    /** @var Visibility */
    protected $productVisible;

    /** @var Status */
    protected $productStatus;

    /** @var Config */
    protected $eavConfig;

    /** @var array */
    private $searchableAttributes = [];

    /** @var array */
    private $searchableAttributesByType = [];

    /** @var array */
    private $exportableAttributes = [];

    /** @var int */
    private $maxBestsellerRating;

    /**
     * @param Variables                          $variables
     * @param Arrays                             $arrays
     * @param Database                           $databaseHelper
     * @param Stores                             $storeHelper
     * @param \Infrangible\Core\Helper\Attribute $attributeHelper
     * @param Product                            $productHelper
     * @param Category                           $categoryHelper
     * @param Customer                           $customerHelper
     * @param Address                            $addressHelper
     * @param Data                               $taxHelper
     * @param LoggerInterface                    $logging
     * @param Visibility                         $productVisible
     * @param Status                             $productStatus
     * @param Config                             $eavConfig
     */
    public function __construct(
        Variables $variables,
        Arrays $arrays,
        Database $databaseHelper,
        Stores $storeHelper,
        \Infrangible\Core\Helper\Attribute $attributeHelper,
        Product $productHelper,
        Category $categoryHelper,
        Customer $customerHelper,
        Address $addressHelper,
        Data $taxHelper,
        LoggerInterface $logging,
        Visibility $productVisible,
        Status $productStatus,
        Config $eavConfig
    ) {
        $this->databaseHelper = $databaseHelper;
        $this->storeHelper = $storeHelper;
        $this->variables = $variables;
        $this->arrays = $arrays;
        $this->attributeHelper = $attributeHelper;
        $this->productHelper = $productHelper;
        $this->categoryHelper = $categoryHelper;
        $this->customerHelper = $customerHelper;
        $this->addressHelper = $addressHelper;
        $this->taxHelper = $taxHelper;

        $this->logging = $logging;
        $this->productVisible = $productVisible;
        $this->productStatus = $productStatus;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param int   $storeId
     * @param bool  $onlyWithStock
     * @param array $productIds
     * @param int   $lastProductId
     * @param int   $limit
     *
     * @return int[]
     *
     * @throws Exception
     */
    public function getExportableProductIds(
        int $storeId,
        bool $onlyWithStock = true,
        array $productIds = [],
        int $lastProductId = 0,
        int $limit = 100
    ): array {
        $select = $this->getExportableProductsSelect($storeId, $onlyWithStock, $productIds, $lastProductId, $limit);

        $queryResult = $this->databaseHelper->fetchAssoc($select, $this->databaseHelper->getDefaultConnection());

        if (count($queryResult) > 0) {
            $this->logging->info(
                sprintf(
                    'Found %d searchable product(s) in store with id: %d',
                    count($queryResult),
                    $storeId
                )
            );
        } elseif ($lastProductId === 0) {
            $this->logging->info(sprintf('Found no searchable products in store with id: %d', $storeId));
        }

        return array_keys($queryResult);
    }

    /**
     * @param int   $storeId
     * @param bool  $onlyWithStock
     * @param array $productIds
     * @param int   $lastProductId
     * @param int   $limit
     *
     * @return Select
     *
     * @throws Exception
     */
    protected function getExportableProductsSelect(
        int $storeId,
        bool $onlyWithStock = true,
        array $productIds = [],
        int $lastProductId = 0,
        int $limit = 100
    ): Select {
        // status and visibility filter
        $visibility = $this->attributeHelper->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'visibility');
        $status = $this->attributeHelper->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'status');

        $configManageStock =
            (int) $this->storeHelper->getStoreConfig(Configuration::XML_PATH_MANAGE_STOCK, false, true);

        $adapter = $this->databaseHelper->getDefaultConnection();

        $select = $adapter->select();

        $select->useStraightJoin();

        $select->from(['e' => $this->databaseHelper->getTableName('catalog_product_entity')], ['entity_id', 'type_id']);

        if ($onlyWithStock || $storeId > 0) {
            $select->joinLeft(['super_link' => $this->databaseHelper->getTableName('catalog_product_super_link')],
                              'super_link.parent_id = e.entity_id',
                              []);
        }

        if ($storeId > 0) {
            $websiteId = $this->storeHelper->getStore($storeId)->getWebsiteId();

            $select->join(['website' => $this->databaseHelper->getTableName('catalog_product_website')],
                          $adapter->quoteInto(
                              'website.product_id = e.entity_id AND website.website_id = ?',
                              $websiteId
                          ),
                          []);

            $select->joinLeft(['simple_website' => $this->databaseHelper->getTableName('catalog_product_website')],
                              $adapter->quoteInto(
                                  'simple_website.product_id = super_link.product_id AND simple_website.website_id = ?',
                                  $websiteId
                              ),
                              []);
        }

        if ($onlyWithStock) {
            $select->join(['stock_item' => $this->databaseHelper->getTableName('cataloginventory_stock_item')],
                          $adapter->quoteInto(
                              'stock_item.product_id = e.entity_id AND stock_item.stock_id = ?',
                              $this->getStockId($storeId)
                          ),
                          []);

            $select->joinLeft(
                ['simple_stock_item' => $this->databaseHelper->getTableName('cataloginventory_stock_item')], sprintf(
                '%s AND %s',
                $adapter->quoteInto(
                    'simple_stock_item.product_id = super_link.product_id AND stock_item.stock_id = ?',
                    $this->getStockId(
                        $storeId
                    )
                ),
                $adapter->quoteInto(
                    '((simple_stock_item.use_config_manage_stock = 1 and simple_stock_item.is_in_stock = 1) OR simple_stock_item.use_config_manage_stock = 0 OR ? = 0)',
                    $configManageStock ? 1 : 0
                )
            ), []
            );
        }

        $select->joinLeft(['status_admin' => $status->getBackendTable()],
                          $adapter->quoteInto(
                              'status_admin.entity_id = e.entity_id AND status_admin.attribute_id = ? AND status_admin.store_id = 0',
                              $status->getAttributeId()
                          ),
                          []);

        $select->joinLeft(['status_store' => $status->getBackendTable()],
                          sprintf(
                              '%s AND %s',
                              $adapter->quoteInto(
                                  'status_store.entity_id = e.entity_id AND status_store.attribute_id = ?',
                                  $status->getAttributeId()
                              ),
                              $adapter->quoteInto('status_store.store_id = ?', $storeId)
                          ),
                          []);

        $select->joinLeft(['visibility_admin' => $visibility->getBackendTable()],
                          $adapter->quoteInto(
                              'visibility_admin.entity_id = e.entity_id AND visibility_admin.attribute_id = ? AND visibility_admin.store_id = 0',
                              $visibility->getAttributeId()
                          ),
                          []);

        $select->joinLeft(['visibility_store' => $visibility->getBackendTable()],
                          sprintf(
                              '%s AND %s',
                              $adapter->quoteInto(
                                  'visibility_store.entity_id = e.entity_id AND visibility_store.attribute_id = ?',
                                  $visibility->getAttributeId()
                              ),
                              $adapter->quoteInto('visibility_store.store_id = ?', $storeId)
                          ),
                          []);

        if (!empty($productIds)) {
            $select->where('`e`.`entity_id` IN( ? )', $productIds);
        }

        $select->where('`e`.`entity_id` > ?', $lastProductId);

        if ($onlyWithStock) {
            $select->where(
                $adapter->quoteInto(
                    '(`e`.`type_id` <> "configurable" AND ((stock_item.is_in_stock = 1 AND stock_item.use_config_manage_stock = 1) OR stock_item.use_config_manage_stock = 0 or ? = 0)) OR (`e`.`type_id` = "configurable")',
                    $configManageStock ? 1 : 0
                )
            );
        }

        $select->where(
            '(status_admin.value = ? AND status_store.value IS NULL) OR status_store.value = ?',
            $this->productStatus->getVisibleStatusIds()
        );

        $select->where(
            '(visibility_admin.value IN (?) AND visibility_store.value IS NULL) OR visibility_store.value IN (?)',
            $this->productVisible->getVisibleInSiteIds()
        );

        $select->group('e.entity_id');

        if ($storeId > 0) {
            $select->having(
                '`e`.`type_id` <> "configurable" OR (`e`.`type_id` = "configurable" AND count(simple_website.product_id) > 0)'
            );
        }

        if ($onlyWithStock) {
            $select->having(
                '`e`.`type_id` <> "configurable" OR (`e`.`type_id` = "configurable" AND count(simple_stock_item.product_id) > 0)'
            );
        }

        $select->limit($limit);

        $select->order('e.entity_id');

        return $select;
    }

    /**
     * @param int $storeId
     *
     * @return int
     * @throws NoSuchEntityException
     */
    protected function getStockId(int $storeId): int
    {
        $this->storeHelper->getStore($storeId)->getWebsiteId();

        return 1;
    }

    /**
     * Retrieve searchable attributes
     *
     * @param string|null $backendType
     * @param array       $excludeAttributeCodes
     * @param array       $attributeConditions
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     * @throws LocalizedException
     */
    public function getSearchableAttributes(
        string $backendType = null,
        array $excludeAttributeCodes = [],
        array $attributeConditions = []
    ): array {
        if (!$this->variables->isEmpty($backendType)) {
            return $this->collectSearchableAttributesByType($backendType, $excludeAttributeCodes, $attributeConditions);
        } else {
            return $this->collectSearchableAttributes($attributeConditions);
        }
    }

    /**
     * @param array $attributeConditions
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     * @throws LocalizedException
     */
    private function collectSearchableAttributes(array $attributeConditions = []): array
    {
        $key = md5(json_encode($attributeConditions));

        if (!array_key_exists($key, $this->searchableAttributes)) {
            $productAttributeCollection = $this->attributeHelper->getProductAttributeCollection();

            $conditions = [
                'additional_table.is_searchable = 1',
                'additional_table.used_in_product_listing = 1',
                'additional_table.used_for_sort_by = 1'
            ];

            foreach ($attributeConditions as $attributeCondition) {
                $conditions[] = sprintf('additional_table.%s', $attributeCondition);
            }

            $conditions[] =
                $productAttributeCollection->getConnection()->quoteInto('main_table.attribute_code IN (?)', [
                    'status',
                    'visibility',
                    'created_at'
                ]);

            $productAttributeCollection->getSelect()->where(sprintf('(%s)', implode(' OR ', $conditions)));

            $attributes = $productAttributeCollection->getItems();

            /** @var AbstractEntity $entity */
            $entity = $this->eavConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY)->getEntity();

            /** @var Attribute $attribute */
            foreach ($attributes as $attribute) {
                $attribute->setEntity($entity);
            }

            $this->searchableAttributes[$key] = $attributes;
        }

        return $this->searchableAttributes[$key];
    }

    /**
     * @param string|null $backendType
     * @param array       $excludeAttributeCodes
     * @param array       $attributeConditions
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     * @throws LocalizedException
     */
    private function collectSearchableAttributesByType(
        string $backendType = null,
        array $excludeAttributeCodes = [],
        array $attributeConditions = []
    ): array {
        $key = md5(json_encode([$backendType, $excludeAttributeCodes, $attributeConditions]));

        if (!array_key_exists($key, $this->searchableAttributesByType)) {
            $searchableAttributes = $this->collectSearchableAttributes($attributeConditions);

            $attributes = [];

            foreach ($searchableAttributes as $attributeId => $attribute) {
                if (in_array($attribute->getAttributeCode(), $excludeAttributeCodes)) {
                    continue;
                }
                if ($attribute->getBackendType() == $backendType) {
                    $attributes[$attributeId] = $attribute;
                }
            }

            $this->searchableAttributesByType[$key] = $attributes;
        }

        return $this->searchableAttributesByType[$key];
    }

    /**
     * @param bool  $onlyAttributeIds
     * @param array $excludeAttributeCodes
     * @param array $attributeConditions
     *
     * @return array
     * @throws LocalizedException
     */
    public function getSearchableAttributesByTypes(
        bool $onlyAttributeIds = true,
        array $excludeAttributeCodes = [],
        array $attributeConditions = []
    ): array {
        $result = [];

        foreach (['int', 'varchar', 'text', 'decimal', 'datetime'] as $backendType) {
            $result[$backendType] = $this->getSearchableAttributesByType(
                $backendType,
                $onlyAttributeIds,
                $excludeAttributeCodes,
                $attributeConditions
            );
        }

        return $result;
    }

    /**
     * @param string $useBackendType
     * @param bool   $onlyAttributeIds
     * @param array  $excludeAttributeCodes
     * @param array  $attributeConditions
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     * @throws LocalizedException
     */
    private function getSearchableAttributesByType(
        string $useBackendType,
        bool $onlyAttributeIds = true,
        array $excludeAttributeCodes = [],
        array $attributeConditions = []
    ): array {
        $attributes = $this->getSearchableAttributes($useBackendType, $excludeAttributeCodes, $attributeConditions);

        return $onlyAttributeIds ? array_keys($attributes) : $attributes;
    }

    /**
     * Determine the index required data from the current block of products.
     *
     * @param AdapterInterface $dbAdapter
     * @param int[]            $productIds
     * @param int              $storeId
     * @param array            $attributeConditions
     * @param array            $requiredEavAttributeCodes
     * @param bool             $limitActiveCategoriesToStore
     *
     * @return array
     * @throws Exception
     */
    public function getProductsData(
        AdapterInterface $dbAdapter,
        array $productIds,
        int $storeId,
        array $attributeConditions = [],
        array $requiredEavAttributeCodes = [],
        bool $limitActiveCategoriesToStore = true
    ): array {
        $searchableAttributes = $this->getExportableAttributes($attributeConditions, $requiredEavAttributeCodes);

        $attributeValues = $this->getCurrentAttributeValues(
            $this->databaseHelper->getDefaultConnection(),
            \Magento\Catalog\Model\Product::ENTITY,
            $storeId,
            array_keys($searchableAttributes),
            $productIds,
            ['entity_id' => true, 'store_id' => true]
        );

        $bestsellerRatings = $this->getBestsellerRatings($dbAdapter, $productIds, $storeId);
        $categoryPaths = $this->getCategoriesPaths($dbAdapter, $productIds, $storeId, $limitActiveCategoriesToStore);
        $urlRewrites = $this->getUrlRewrites($dbAdapter, $productIds, $storeId);
        $galleryImages = $this->getGalleryImages($dbAdapter, $productIds, $storeId, false);
        $indexedPrices = $this->getIndexedPrices($dbAdapter, $productIds, $storeId);
        $stockItems = $this->getStockItems($dbAdapter, $productIds, $storeId);
        $reviewSummary = $this->getReviewSummary($dbAdapter, $productIds, $storeId);

        $productsData = [];

        $configurableProductIds = [];
        $bundleProductIds = [];
        $groupProductIds = [];

        foreach ($attributeValues as $productId => $productAttributeValues) {
            foreach ($productAttributeValues as $attributeCode => $attributeValue) {
                if (!$this->variables->isEmpty($attributeValue)) {
                    $attribute = $this->arrays->getValue($searchableAttributes, $attributeCode);

                    if ($attribute instanceof Attribute && $attribute->usesSource()) {
                        $attributeValue = [
                            'id'    => $attributeValue,
                            'value' => $this->attributeHelper->getAttributeOptionValue(
                                \Magento\Catalog\Model\Product::ENTITY,
                                $attributeCode,
                                $storeId,
                                $attributeValue
                            )
                        ];
                    }

                    $productsData[$productId][$attributeCode] = $attributeValue;
                }
            }

            $productsData[$productId]['bestseller_rating'] = $this->arrays->getValue($bestsellerRatings, $productId);

            $productsData[$productId]['category_paths'] = $this->arrays->getValue($categoryPaths, $productId, []);

            $productsData[$productId]['url_rewrites'] = $this->arrays->getValue($urlRewrites, $productId, []);

            $productsData[$productId]['gallery_images'] = $this->arrays->getValue($galleryImages, $productId, []);

            $productsData[$productId]['indexed_prices'] = $this->arrays->getValue($indexedPrices, $productId, []);

            $productsData[$productId]['stock_item'] = $this->arrays->getValue($stockItems, $productId, []);

            $productsData[$productId]['review_summary'] = $this->arrays->getValue($reviewSummary, $productId, []);

            $typeId = $this->arrays->getValue($productAttributeValues, 'type_id');

            if ($typeId === 'configurable') {
                $configurableProductIds[] = $productId;
            } elseif ($typeId === 'bundle') {
                $bundleProductIds[] = $productId;
            } elseif ($typeId === 'grouped') {
                $groupProductIds[] = $productId;
            }
        }

        $showOutOfStock = $this->storeHelper->getStoreConfig('cataloginventory/options/show_out_of_stock', false, true);

        if (count($configurableProductIds) > 0) {
            $childIds = $this->productHelper->getChildIds(
                $this->databaseHelper->getDefaultConnection(),
                $configurableProductIds,
                true,
                !$showOutOfStock,
                true,
                true,
                false,
                $storeId
            );

            if (count($childIds) > 0) {
                $this->logging->debug(sprintf('Found %d child product(s)', count($childIds)));

                $childProductsData = $this->getProductsData(
                    $dbAdapter,
                    array_keys($childIds),
                    $storeId,
                    $attributeConditions,
                    $requiredEavAttributeCodes,
                    $limitActiveCategoriesToStore
                );

                foreach ($childIds as $childId => $parentId) {
                    $productsData[$parentId]['children'][] = $childProductsData[$childId];
                }
            }
        }

        if (count($bundleProductIds) > 0) {
            $bundledIds = $this->productHelper->getBundledIds(
                $this->databaseHelper->getDefaultConnection(),
                $bundleProductIds,
                true,
                !$showOutOfStock,
                true,
                $storeId
            );

            if (count($bundledIds) > 0) {
                $this->logging->debug(sprintf('Found %d bundled product(s)', count($bundledIds)));

                $bundledProductsData = $this->getProductsData(
                    $dbAdapter,
                    array_keys($bundledIds),
                    $storeId,
                    $attributeConditions,
                    $requiredEavAttributeCodes,
                    $limitActiveCategoriesToStore
                );

                foreach ($bundledIds as $bundledId => $parentId) {
                    $productsData[$parentId]['bundled'][] = $bundledProductsData[$bundledId];
                }
            }
        }

        if (count($groupProductIds) > 0) {
            $groupedIds = $this->productHelper->getGroupedIds(
                $this->databaseHelper->getDefaultConnection(),
                $groupProductIds,
                true,
                !$showOutOfStock,
                true,
                false,
                $storeId
            );

            if (count($groupedIds) > 0) {
                $this->logging->debug(sprintf('Found %d grouped product(s)', count($groupedIds)));

                $groupedProductsData = $this->getProductsData(
                    $dbAdapter,
                    array_keys($groupedIds),
                    $storeId,
                    $attributeConditions,
                    $requiredEavAttributeCodes,
                    $limitActiveCategoriesToStore
                );

                foreach ($groupedIds as $groupedId => $parentId) {
                    $productsData[$parentId]['grouped'][] = $groupedProductsData[$groupedId];
                }
            }
        }

        return $productsData;
    }

    /**
     * @param array $attributeConditions
     * @param array $requiredEavAttributeCodes
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     * @throws Exception
     */
    public function getExportableAttributes(
        array $attributeConditions = [],
        array $requiredEavAttributeCodes = []
    ): array {
        $key = md5(json_encode([$attributeConditions, $requiredEavAttributeCodes]));

        if (!array_key_exists($key, $this->exportableAttributes)) {
            $this->exportableAttributes[$key] = [];

            foreach ($this->getSearchableAttributes(null, [], $attributeConditions) as $attribute) {
                $this->exportableAttributes[$key][$attribute->getAttributeCode()] = $attribute;
            }

            $this->exportableAttributes[$key]['updated_at'] =
                $this->attributeHelper->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'updated_at');

            foreach ($requiredEavAttributeCodes as $requiredEavAttributeCode) {
                $this->exportableAttributes[$key][$requiredEavAttributeCode] = $this->attributeHelper->getAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $requiredEavAttributeCode
                );
            }
        }

        return $this->exportableAttributes[$key];
    }

    /**
     * get rating position for a product from the bestseller table
     *
     * @param AdapterInterface $dbAdapter
     * @param array            $productIds
     * @param int              $storeId
     *
     * @return array
     */
    public function getBestsellerRatings(AdapterInterface $dbAdapter, array $productIds, int $storeId): array
    {
        $bestseller = $dbAdapter->select()->from(
            $this->databaseHelper->getTableName('sales_bestsellers_aggregated_monthly'), ['product_id', 'rating_pos']
        );

        $bestseller->where(
            $dbAdapter->prepareSqlCondition('product_id', ['in' => $productIds]),
            null,
            Select::TYPE_CONDITION
        );

        $bestseller->where(
            $dbAdapter->prepareSqlCondition('period', ['eq' => sprintf('%s-01', date('Y-m'))]),
            null,
            Select::TYPE_CONDITION
        );

        $bestseller->where(
            $dbAdapter->prepareSqlCondition('store_id', ['eq' => $storeId]),
            null,
            Select::TYPE_CONDITION
        );

        $bestseller->order(['id DESC']);

        $queryResult = $this->databaseHelper->fetchPairs($bestseller, $dbAdapter);

        foreach ($productIds as $productId) {
            $bestseller = $this->arrays->getValue($queryResult, $productId);

            $queryResult[$productId] =
                !empty($bestseller) ? (int) $bestseller : $this->getMaxBestsellerRating($dbAdapter, $storeId);
        }

        return $queryResult;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param int              $storeId
     *
     * @return int
     */
    protected function getMaxBestsellerRating(AdapterInterface $dbAdapter, int $storeId): int
    {
        if (empty($this->maxBestsellerRating)) {
            $tableName = $this->databaseHelper->getTableName('sales_bestsellers_aggregated_monthly');
            $tableLabel = 'bestsellers_aggregated_monthly';

            $bestseller = $dbAdapter->select()->from([$tableLabel => $tableName], ['max(rating_pos)']);

            $bestseller->where(
                $dbAdapter->prepareSqlCondition(
                    $tableLabel.'.period', ['eq' => sprintf('%s-01', date('Y-m'))]
                ),
                null,
                Select::TYPE_CONDITION
            );

            $bestseller->where(
                $dbAdapter->prepareSqlCondition($tableLabel.'.store_id', ['eq' => $storeId]),
                null,
                Select::TYPE_CONDITION
            );

            $bestseller = $this->databaseHelper->fetchOne($bestseller, $dbAdapter);

            $this->maxBestsellerRating = empty($bestseller) ? 1 : (((int) $bestseller) + 1);
        }

        return $this->maxBestsellerRating;
    }

    /**
     * returns an array of the url paths of the categories a product is member of
     * language is given through store, defaults to store 0 if no name is found
     *
     * @param AdapterInterface $dbAdapter
     * @param array            $productIds
     * @param int              $storeId
     * @param bool             $limitActiveCategoriesToStore
     *
     * @return array
     * @throws Exception
     */
    public function getCategoriesPaths(
        AdapterInterface $dbAdapter,
        array $productIds,
        int $storeId,
        bool $limitActiveCategoriesToStore = true
    ): array {
        $storeCategoryIds =
            $this->categoryHelper->getActiveCategoryIds($dbAdapter, $storeId, $limitActiveCategoriesToStore);
        $storeCategoryIds = array_flip($storeCategoryIds);

        $categoryIds = $this->categoryHelper->getEntityIds($dbAdapter, $productIds, true, false, true);

        $categoryPaths = [];

        foreach ($categoryIds as $productId => $productCategoryIds) {
            foreach ($productCategoryIds as $productCategoryId) {
                if (!array_key_exists($productCategoryId, $storeCategoryIds)) {
                    continue;
                }

                $categoryName = $this->categoryHelper->getCategoryName($dbAdapter, $productCategoryId, $storeId);

                if ($this->variables->isEmpty($categoryName)) {
                    $categoryName = $this->categoryHelper->getCategoryName($dbAdapter, $productCategoryId, 0);
                }

                $categoryUrl = $this->categoryHelper->getCategoryUrlPath($dbAdapter, $productCategoryId, $storeId);

                $parentCategoryIds =
                    $this->categoryHelper->getParentEntityIds($dbAdapter, [$productCategoryId], 0, true);

                $categoryPaths[$productId][$productCategoryId] =
                    ['name' => $categoryName, 'url' => $categoryUrl, 'parent_id' => reset($parentCategoryIds)];
            }
        }

        return $categoryPaths;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $productIds
     * @param int              $storeId
     *
     * @return array|null
     */
    public function getUrlRewrites(AdapterInterface $dbAdapter, array $productIds, int $storeId): ?array
    {
        $urlRewriteQuery = $dbAdapter->select()->from($this->databaseHelper->getTableName('url_rewrite'), [
            'entity_id',
            'request_path'
        ]);

        $urlRewriteQuery->where('entity_type = ?', 'product', Select::TYPE_CONDITION);
        $urlRewriteQuery->where('entity_id IN (?)', $productIds, Select::TYPE_CONDITION);
        $urlRewriteQuery->where('store_id = ?', $storeId, Select::TYPE_CONDITION);
        $urlRewriteQuery->where('is_autogenerated = ?', 1);

        $urlRewriteQuery->joinLeft(
            $this->databaseHelper->getTableName('catalog_url_rewrite_product_category'),
            'catalog_url_rewrite_product_category.url_rewrite_id = url_rewrite.url_rewrite_id',
            ['category_id']
        );

        $queryResult = $this->databaseHelper->fetchAll($urlRewriteQuery);

        $result = [];

        foreach ($queryResult as $queryRow) {
            $productId = $this->arrays->getValue($queryRow, 'entity_id');
            $requestPath = $this->arrays->getValue($queryRow, 'request_path');
            $categoryId = (int) $this->arrays->getValue($queryRow, 'category_id');

            $result[$productId][$categoryId] = $requestPath;
        }

        return $result;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $productIds
     * @param int              $storeId
     * @param bool             $includeDisabled
     *
     * @return array
     * @throws Exception
     */
    public function getGalleryImages(
        AdapterInterface $dbAdapter,
        array $productIds,
        int $storeId,
        bool $includeDisabled = true
    ): array {
        $galleryTableName = $this->databaseHelper->getTableName('catalog_product_entity_media_gallery');
        $galleryValueTableName = $this->databaseHelper->getTableName('catalog_product_entity_media_gallery_value');
        $galleryValueToEntityTableName =
            $this->databaseHelper->getTableName('catalog_product_entity_media_gallery_value_to_entity');

        $galleryQuery = $dbAdapter->select()->from(['gallery_value' => $galleryTableName], [
            'value_id',
            'value',
            sprintf(
                'IF(%s IS NOT NULL, %s, %s) as position',
                $dbAdapter->quoteIdentifier('gallery_value_store.position'),
                $dbAdapter->quoteIdentifier('gallery_value_store.position'),
                $dbAdapter->quoteIdentifier('gallery_value_default.position')
            ),
            sprintf(
                'IF(%s IS NOT NULL, %s, %s) as disabled',
                $dbAdapter->quoteIdentifier('gallery_value_store.disabled'),
                $dbAdapter->quoteIdentifier('gallery_value_store.disabled'),
                $dbAdapter->quoteIdentifier('gallery_value_default.disabled')
            ),
            sprintf(
                'IF(%s IS NOT NULL, %s, %s) as label',
                $dbAdapter->quoteIdentifier('gallery_value_store.label'),
                $dbAdapter->quoteIdentifier('gallery_value_store.label'),
                $dbAdapter->quoteIdentifier('gallery_value_default.label')
            )
        ]);

        $galleryQuery->joinLeft(['gallery_value_default' => $galleryValueTableName],
                                sprintf(
                                    '%s = %s AND %s = %d',
                                    $dbAdapter->quoteIdentifier('gallery_value.value_id'),
                                    $dbAdapter->quoteIdentifier('gallery_value_default.value_id'),
                                    $dbAdapter->quoteIdentifier('gallery_value_default.store_id'),
                                    0
                                ),
                                []);

        $galleryQuery->joinLeft(['gallery_value_store' => $galleryValueTableName],
                                sprintf(
                                    '%s = %s AND %s = %d',
                                    $dbAdapter->quoteIdentifier('gallery_value.value_id'),
                                    $dbAdapter->quoteIdentifier('gallery_value_store.value_id'),
                                    $dbAdapter->quoteIdentifier('gallery_value_store.value_id'),
                                    $storeId
                                ),
                                []);

        $galleryQuery->join(['gallery_value_to_entity' => $galleryValueToEntityTableName],
                            sprintf(
                                '%s = %s',
                                $dbAdapter->quoteIdentifier('gallery_value.value_id'),
                                $dbAdapter->quoteIdentifier('gallery_value_to_entity.value_id')
                            ),
                            ['gallery_value_to_entity.entity_id']);

        $attribute = $this->attributeHelper->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'media_gallery');

        $galleryQuery->where(
            $dbAdapter->prepareSqlCondition(
                'gallery_value.attribute_id', ['eq' => $attribute->getId()]
            ),
            null,
            Select::TYPE_CONDITION
        );

        $galleryQuery->where(
            $dbAdapter->prepareSqlCondition(
                'gallery_value_to_entity.entity_id', ['in' => $productIds]
            ),
            null,
            Select::TYPE_CONDITION
        );

        $galleryQuery->where(
            sprintf(
                '%s IS NOT NULL OR %s IS NOT NULL',
                $dbAdapter->quoteIdentifier('gallery_value_default.value_id'),
                $dbAdapter->quoteIdentifier('gallery_value_store.value_id')
            )
        );

        if (!$includeDisabled) {
            $galleryQuery->having('disabled = 0');
        }

        $queryResult = $this->databaseHelper->fetchAssoc($galleryQuery);

        $result = [];

        foreach ($queryResult as $queryRow) {
            $productId = $this->arrays->getValue($queryRow, 'entity_id');
            $valueId = $this->arrays->getValue($queryRow, 'value_id');

            $result[$productId][$valueId] = $queryRow;
        }

        return $result;
    }

    /**
     * Gets the product prices from the catalog price indexer table
     *
     * @param AdapterInterface $dbAdapter
     * @param array            $productIds
     * @param int              $storeId
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getIndexedPrices(AdapterInterface $dbAdapter, array $productIds, int $storeId): array
    {
        $categoryQuery =
            $dbAdapter->select()->from([$this->databaseHelper->getTableName('catalog_product_index_price')], [
                'entity_id',
                'price',
                'final_price',
                'min_price',
                'max_price'
            ]);

        $categoryQuery->where(
            $dbAdapter->prepareSqlCondition('entity_id', ['in' => $productIds]),
            null,
            Select::TYPE_CONDITION
        );

        $categoryQuery->where(
            $dbAdapter->prepareSqlCondition('customer_group_id', ['eq' => '0']),
            null,
            Select::TYPE_CONDITION
        );

        $store = $this->storeHelper->getStore($storeId);

        $categoryQuery->where(
            $dbAdapter->prepareSqlCondition('website_id', ['eq' => $store->getWebsite()->getId()]),
            null,
            Select::TYPE_CONDITION
        );

        $queryResult = $this->databaseHelper->fetchAssoc($categoryQuery);

        foreach ($productIds as $productId) {
            if (!array_key_exists($productId, $queryResult)) {
                $product = $this->productHelper->loadProduct($productId, $storeId);

                if ($product->getTypeId() === 'bundle') {
                    /** @var Price $priceModel */
                    $priceModel = $product->getPriceModel();

                    if ($this->taxHelper->displayPriceIncludingTax()) {
                        [$minimalPrice, $maximalPrice] = $priceModel->getTotalPrices($product, null, true, false);
                    } else {
                        [$minimalPrice, $maximalPrice] = $priceModel->getTotalPrices($product, null, null, false);
                    }

                    $queryResult[$productId] = [
                        'price'       => $product->getPrice(),
                        'min_price'   => $minimalPrice,
                        'max_price'   => $maximalPrice,
                        'final_price' => $minimalPrice
                    ];
                } else {
                    $queryResult[$productId] = [
                        'price'       => $product->getPrice(),
                        'min_price'   => $product->getFinalPrice(),
                        'max_price'   => $product->getFinalPrice(),
                        'final_price' => $product->getFinalPrice()
                    ];
                }
            }
        }

        return $queryResult;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $productIds
     * @param int              $storeId
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getStockItems(AdapterInterface $dbAdapter, array $productIds, int $storeId): array
    {
        $stockStatusFields = [
            'product_id'   => 'stock_status.product_id',
            'stock_id'     => 'stock_status.stock_id',
            'qty'          => 'stock_status.qty',
            'stock_status' => 'stock_status.stock_status'
        ];

        $stockStatusTable = $this->databaseHelper->getTableName('cataloginventory_stock_status');

        $select = $dbAdapter->select()->from(['stock_status' => $stockStatusTable], array_values($stockStatusFields));

        $stockItemTable = $this->databaseHelper->getTableName('cataloginventory_stock_item');

        $stockItemFields = [];

        foreach ($this->getStockItemFields() as $stockItemField) {
            if (!array_key_exists($stockItemField, $stockStatusFields)) {
                $stockItemFields[] = sprintf('stock_item.%s', $stockItemField);
            }
        }

        $select->join(['stock_item' => $stockItemTable],
                      'stock_status.product_id = stock_item.product_id AND stock_status.stock_id = stock_item.stock_id',
                      $stockItemFields);

        $select->where('stock_status.product_id IN (?)', $productIds);
        $select->where('stock_status.stock_id = ?', $this->getStockId($storeId));

        return $this->databaseHelper->fetchAssoc($select);
    }

    /**
     * @return array
     */
    protected function getStockItemFields(): array
    {
        return ['qty', 'is_in_stock', 'manage_stock'];
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $productIds
     * @param int              $storeId
     *
     * @return array
     */
    public function getReviewSummary(AdapterInterface $dbAdapter, array $productIds, int $storeId): array
    {
        $entityIdSelect =
            $dbAdapter->select()->from($this->databaseHelper->getTableName('review_entity'), ['entity_id']);

        $entityIdSelect->where('entity_code = ?', 'product');

        $entityId = (int) $this->databaseHelper->fetchOne($entityIdSelect);

        $select = $dbAdapter->select()->from(
            $this->databaseHelper->getTableName('review_entity_summary'),
            ['entity_pk_value', 'reviews_count', 'rating_summary']
        );

        $select->where('entity_pk_value IN (?)', $productIds);
        $select->where('entity_type = ?', $entityId);
        $select->where('store_id = ?', $storeId);

        return $this->databaseHelper->fetchAssoc($select);
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param string           $entityTypeCode
     * @param int              $storeId
     * @param array            $attributeCodes
     * @param array            $entityIds
     * @param array            $specialAttributes
     *
     * @return array
     * @throws Exception
     * @throws Zend_Db_Select_Exception
     */
    public function getCurrentAttributeValues(
        AdapterInterface $dbAdapter,
        string $entityTypeCode,
        int $storeId,
        array $attributeCodes,
        array $entityIds,
        array $specialAttributes
    ): array {
        if (empty($attributeCodes) || empty($entityIds)) {
            return [];
        }

        $currentValues = [];

        $attributeCodeChunks = array_chunk($attributeCodes, 25, true);

        foreach ($attributeCodeChunks as $attributeChunk) {
            if ($entityTypeCode == \Magento\Catalog\Model\Product::ENTITY) {
                $collection = $this->productHelper->getProductCollection();
                $collection->setStoreId($storeId);
            } elseif ($entityTypeCode == \Magento\Catalog\Model\Category::ENTITY) {
                $collection = $this->categoryHelper->getCategoryCollection();
                $collection->setStoreId($storeId);
            } elseif ($entityTypeCode == 'customer') {
                $collection = $this->customerHelper->getCustomerCollection();
            } elseif ($entityTypeCode == 'customer_address') {
                $collection = $this->addressHelper->getAddressCollection();
            } else {
                throw new Exception(sprintf('Entity type: %s not implemented yet', $entityTypeCode));
            }

            foreach ($attributeChunk as $attributeCode) {
                $collection->addAttributeToSelect(
                    $attributeCode,
                    in_array($attributeCode, array_keys($specialAttributes)) ? false : 'left'
                );
            }

            if (count($entityIds) == 1) {
                $entityId = reset($entityIds);

                $collection->addAttributeToFilter('entity_id', ['eq' => $entityId]);
            } else {
                $collection->addAttributeToFilter('entity_id', ['in' => $entityIds]);
            }

            $columns = $collection->getSelect()->getPart(Zend_Db_Select::COLUMNS);

            foreach ($columns as $key => $column) {
                if (array_key_exists(1, $column) && $column[1] == 'entity_id') {
                    unset($columns[$key]);
                }
            }

            array_unshift($columns, ['0' => 'e', 1 => 'entity_id', 2 => 'entity_id']);

            $collection->getSelect()->setPart(Zend_Db_Select::COLUMNS, $columns);

            $queryResult = $this->databaseHelper->fetchAssoc($collection->getSelect(), $dbAdapter);

            foreach ($queryResult as $key => $row) {
                $currentValues[$key] =
                    array_key_exists($key, $currentValues) ? array_merge($currentValues[$key], $row) : $row;
            }
        }

        return $currentValues;
    }
}
