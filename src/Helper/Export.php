<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use InvalidArgumentException;
use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Helper\Data;
use Psr\Log\LoggerInterface;

class Export
{
    /** @var Variables */
    protected Variables $variables;

    /** @var Arrays */
    protected Arrays $arrays;

    /** @var Database */
    protected Database $databaseHelper;

    /** @var Stores */
    protected Stores $storeHelper;

    /** @var \Infrangible\Core\Helper\Attribute */
    protected \Infrangible\Core\Helper\Attribute $attributeHelper;

    /** @var Product */
    protected Product $productHelper;

    /** @var Category */
    protected Category $categoryHelper;

    /** @var Customer */
    protected Customer $customerHelper;

    /** @var Address */
    protected Address $addressHelper;

    /** @var Data */
    protected Data $taxHelper;

    /** @var LoggerInterface */
    protected LoggerInterface $logging;

    /** @var Visibility */
    protected Visibility $productVisible;

    /** @var Status */
    protected Status $productStatus;

    /** @var Config */
    protected Config $eavConfig;

    /** @var array<string, array<int|string, \Magento\Catalog\Model\ResourceModel\Eav\Attribute>> */
    private array $searchableAttributes = [];

    /** @var array<string, array<int|string, \Magento\Catalog\Model\ResourceModel\Eav\Attribute>> */
    private array $searchableAttributesByType = [];

    /** @var array<string, array<string, Attribute>> */
    private array $exportableAttributes = [];

    /** @var array<int, int> maximum bestseller rating (plus one) by store id */
    private array $maxBestsellerRatings = [];

    /**
     * @param Variables $variables
     * @param Arrays $arrays
     * @param Database $databaseHelper
     * @param Stores $storeHelper
     * @param \Infrangible\Core\Helper\Attribute $attributeHelper
     * @param Product $productHelper
     * @param Category $categoryHelper
     * @param Customer $customerHelper
     * @param Address $addressHelper
     * @param Data $taxHelper
     * @param LoggerInterface $logging
     * @param Visibility $productVisible
     * @param Status $productStatus
     * @param Config $eavConfig
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
     * Returns the ids of the products that are enabled and visible in the store, optionally only with stock.
     *
     * The ids are returned in batches: up to the limit, starting after the last product id.
     *
     * @param int $storeId
     * @param bool $onlyWithStock
     * @param array $productIds limit the result to these products
     * @param int $lastProductId
     * @param int $limit
     *
     * @phpstan-param array<int, int|string> $productIds
     *
     * @return int[]
     * @throws Exception
     */
    public function getExportableProductIds(
        int $storeId,
        bool $onlyWithStock = true,
        array $productIds = [],
        int $lastProductId = 0,
        int $limit = 100
    ): array {
        $select = $this->getExportableProductsSelect(
            $storeId,
            $onlyWithStock,
            $productIds,
            $lastProductId,
            $limit
        );

        $queryResult = $this->databaseHelper->fetchAssoc(
            $select,
            $this->databaseHelper->getDefaultConnection()
        );

        if (count($queryResult) > 0) {
            $this->logging->info(
                sprintf(
                    'Found %d searchable product(s) in store with id: %d',
                    count($queryResult),
                    $storeId
                )
            );
        } elseif ($lastProductId === 0) {
            $this->logging->info(
                sprintf(
                    'Found no searchable products in store with id: %d',
                    $storeId
                )
            );
        }

        return array_map(
            fn($productId) => $this->variables->intValue($productId),
            array_keys($queryResult)
        );
    }

    /**
     * Returns the query for getExportableProductIds().
     *
     * @param int $storeId
     * @param bool $onlyWithStock
     * @param array $productIds
     * @param int $lastProductId
     * @param int $limit
     *
     * @phpstan-param array<int, int|string> $productIds
     *
     * @return Select
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
        $visibility = $this->attributeHelper->getAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'visibility'
        );
        $status = $this->attributeHelper->getAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'status'
        );

        $configManageStock = (bool)$this->storeHelper->getStoreConfig(
            Configuration::XML_PATH_MANAGE_STOCK,
            false,
            true
        );

        $adapter = $this->databaseHelper->getDefaultConnection();

        $select = $adapter->select();

        $select->useStraightJoin();

        $select->from(
            ['e' => $this->databaseHelper->getTableName('catalog_product_entity')],
            ['entity_id', 'type_id']
        );

        if ($onlyWithStock || $storeId > 0) {
            $select->joinLeft(
                ['super_link' => $this->databaseHelper->getTableName('catalog_product_super_link')],
                'super_link.parent_id = e.entity_id',
                []
            );
        }

        if ($storeId > 0) {
            $websiteId = $this->storeHelper->getStore($storeId)->getWebsiteId();

            $select->join(
                ['website' => $this->databaseHelper->getTableName('catalog_product_website')],
                $adapter->quoteInto(
                    'website.product_id = e.entity_id AND website.website_id = ?',
                    $websiteId
                ),
                []
            );

            $select->joinLeft(
                ['simple_website' => $this->databaseHelper->getTableName('catalog_product_website')],
                $adapter->quoteInto(
                    'simple_website.product_id = super_link.product_id AND simple_website.website_id = ?',
                    $websiteId
                ),
                []
            );
        }

        if ($onlyWithStock) {
            $select->join(
                ['stock_item' => $this->databaseHelper->getTableName('cataloginventory_stock_item')],
                $adapter->quoteInto(
                    'stock_item.product_id = e.entity_id AND stock_item.stock_id = ?',
                    $this->getStockId($storeId)
                ),
                []
            );

            $select->joinLeft(
                ['simple_stock_item' => $this->databaseHelper->getTableName('cataloginventory_stock_item')],
                sprintf(
                    '%s AND %s',
                    $adapter->quoteInto(
                        'simple_stock_item.product_id = super_link.product_id AND simple_stock_item.stock_id = ?',
                        $this->getStockId(
                            $storeId
                        )
                    ),
                    $adapter->quoteInto(
                        '((simple_stock_item.use_config_manage_stock = 1 and simple_stock_item.is_in_stock = 1)' .
                        ' OR simple_stock_item.use_config_manage_stock = 0 OR ? = 0)',
                        $configManageStock ? 1 : 0
                    )
                ),
                []
            );
        }

        $select->joinLeft(
            ['status_admin' => $status->getBackendTable()],
            $adapter->quoteInto(
                'status_admin.entity_id = e.entity_id AND status_admin.attribute_id = ? AND status_admin.store_id = 0',
                $status->getAttributeId()
            ),
            []
        );

        $select->joinLeft(
            ['status_store' => $status->getBackendTable()],
            sprintf(
                '%s AND %s',
                $adapter->quoteInto(
                    'status_store.entity_id = e.entity_id AND status_store.attribute_id = ?',
                    $status->getAttributeId()
                ),
                $adapter->quoteInto(
                    'status_store.store_id = ?',
                    $storeId
                )
            ),
            []
        );

        $select->joinLeft(
            ['visibility_admin' => $visibility->getBackendTable()],
            $adapter->quoteInto(
                'visibility_admin.entity_id = e.entity_id AND visibility_admin.attribute_id = ?' .
                ' AND visibility_admin.store_id = 0',
                $visibility->getAttributeId()
            ),
            []
        );

        $select->joinLeft(
            ['visibility_store' => $visibility->getBackendTable()],
            sprintf(
                '%s AND %s',
                $adapter->quoteInto(
                    'visibility_store.entity_id = e.entity_id AND visibility_store.attribute_id = ?',
                    $visibility->getAttributeId()
                ),
                $adapter->quoteInto(
                    'visibility_store.store_id = ?',
                    $storeId
                )
            ),
            []
        );

        if (! empty($productIds)) {
            $select->where(
                '`e`.`entity_id` IN( ? )',
                $productIds
            );
        }

        $select->where(
            '`e`.`entity_id` > ?',
            $lastProductId
        );

        if ($onlyWithStock) {
            $select->where(
                $adapter->quoteInto(
                    '(`e`.`type_id` <> "configurable" AND ((stock_item.is_in_stock = 1' .
                    ' AND stock_item.use_config_manage_stock = 1) OR stock_item.use_config_manage_stock = 0' .
                    ' or ? = 0)) OR (`e`.`type_id` = "configurable")',
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
                '`e`.`type_id` <> "configurable"' .
                ' OR (`e`.`type_id` = "configurable" AND count(simple_website.product_id) > 0)'
            );
        }

        if ($onlyWithStock) {
            $select->having(
                '`e`.`type_id` <> "configurable"' .
                ' OR (`e`.`type_id` = "configurable" AND count(simple_stock_item.product_id) > 0)'
            );
        }

        $select->limit($limit);

        $select->order('e.entity_id');

        return $select;
    }

    /**
     * Returns the id of the stock used for the store (always the default stock).
     *
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
     * Returns the product attributes that are searchable, used in product listing or for sorting.
     *
     * With a backend type, only the attributes of this type are returned.
     *
     * @param string|null $backendType
     * @param array $excludeAttributeCodes
     * @param array $attributeConditions additional conditions on catalog_eav_attribute, combined with OR
     *
     * @phpstan-param array<int, string> $excludeAttributeCodes
     * @phpstan-param array<int, string> $attributeConditions
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     * @phpstan-return array<int|string, \Magento\Catalog\Model\ResourceModel\Eav\Attribute>
     * @throws LocalizedException
     */
    public function getSearchableAttributes(
        ?string $backendType = null,
        array $excludeAttributeCodes = [],
        array $attributeConditions = []
    ): array {
        if (! $this->variables->isEmpty($backendType)) {
            return $this->collectSearchableAttributesByType(
                $backendType,
                $excludeAttributeCodes,
                $attributeConditions
            );
        } else {
            return $this->collectSearchableAttributes($attributeConditions);
        }
    }

    /**
     * Loads the searchable product attributes (cached by conditions).
     *
     * @param array $attributeConditions
     *
     * @phpstan-param array<int, string> $attributeConditions
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     * @phpstan-return array<int|string, \Magento\Catalog\Model\ResourceModel\Eav\Attribute>
     * @throws LocalizedException
     */
    private function collectSearchableAttributes(array $attributeConditions = []): array
    {
        $key = hash('sha256', (string)json_encode($attributeConditions));

        if (! array_key_exists(
            $key,
            $this->searchableAttributes
        )) {
            $productAttributeCollection = $this->attributeHelper->getProductAttributeCollection();

            $conditions = [
                'additional_table.is_searchable = 1',
                'additional_table.used_in_product_listing = 1',
                'additional_table.used_for_sort_by = 1'
            ];

            foreach ($attributeConditions as $attributeCondition) {
                $conditions[] = sprintf(
                    'additional_table.%s',
                    $attributeCondition
                );
            }

            $conditions[] = $productAttributeCollection->getConnection()->quoteInto(
                'main_table.attribute_code IN (?)',
                [
                    'status',
                    'visibility',
                    'created_at'
                ]
            );

            $productAttributeCollection->getSelect()->where(
                sprintf(
                    '(%s)',
                    implode(
                        ' OR ',
                        $conditions
                    )
                )
            );

            /** @var array<int|string, \Magento\Catalog\Model\ResourceModel\Eav\Attribute> $attributes */
            $attributes = $productAttributeCollection->getItems();

            /** @var AbstractEntity $entity */
            $entity = $this->eavConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY)->getEntity();

            foreach ($attributes as $attribute) {
                $attribute->setEntity($entity);
            }

            $this->searchableAttributes[ $key ] = $attributes;
        }

        return $this->searchableAttributes[ $key ];
    }

    /**
     * Returns the searchable product attributes of the backend type (cached by arguments).
     *
     * @param string|null $backendType
     * @param array $excludeAttributeCodes
     * @param array $attributeConditions
     *
     * @phpstan-param array<int, string> $excludeAttributeCodes
     * @phpstan-param array<int, string> $attributeConditions
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     * @phpstan-return array<int|string, \Magento\Catalog\Model\ResourceModel\Eav\Attribute>
     * @throws LocalizedException
     */
    private function collectSearchableAttributesByType(
        ?string $backendType = null,
        array $excludeAttributeCodes = [],
        array $attributeConditions = []
    ): array {
        $key = hash('sha256', (string)json_encode([$backendType, $excludeAttributeCodes, $attributeConditions]));

        if (! array_key_exists(
            $key,
            $this->searchableAttributesByType
        )) {
            $searchableAttributes = $this->collectSearchableAttributes($attributeConditions);

            $attributes = [];

            foreach ($searchableAttributes as $attributeId => $attribute) {
                if (in_array(
                    $attribute->getAttributeCode(),
                    $excludeAttributeCodes
                )) {
                    continue;
                }
                if ($attribute->getBackendType() == $backendType) {
                    $attributes[ $attributeId ] = $attribute;
                }
            }

            $this->searchableAttributesByType[ $key ] = $attributes;
        }

        return $this->searchableAttributesByType[ $key ];
    }

    /**
     * Returns the searchable product attributes (or only their ids) grouped by backend type.
     *
     * @param bool $onlyAttributeIds
     * @param array $excludeAttributeCodes
     * @param array $attributeConditions
     *
     * @phpstan-param array<int, string> $excludeAttributeCodes
     * @phpstan-param array<int, string> $attributeConditions
     *
     * @return array backend type => attributes or attribute ids
     * @phpstan-return array<string, array<int|string, int|string|\Magento\Catalog\Model\ResourceModel\Eav\Attribute>>
     * @throws LocalizedException
     */
    public function getSearchableAttributesByTypes(
        bool $onlyAttributeIds = true,
        array $excludeAttributeCodes = [],
        array $attributeConditions = []
    ): array {
        $result = [];

        foreach (['int', 'varchar', 'text', 'decimal', 'datetime'] as $backendType) {
            $result[ $backendType ] = $this->getSearchableAttributesByType(
                $backendType,
                $onlyAttributeIds,
                $excludeAttributeCodes,
                $attributeConditions
            );
        }

        return $result;
    }

    /**
     * Returns the searchable product attributes (or only their ids) of the backend type.
     *
     * @param string $useBackendType
     * @param bool $onlyAttributeIds
     * @param array $excludeAttributeCodes
     * @param array $attributeConditions
     *
     * @phpstan-param array<int, string> $excludeAttributeCodes
     * @phpstan-param array<int, string> $attributeConditions
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     * @phpstan-return array<int|string, int|string|\Magento\Catalog\Model\ResourceModel\Eav\Attribute>
     * @throws LocalizedException
     */
    private function getSearchableAttributesByType(
        string $useBackendType,
        bool $onlyAttributeIds = true,
        array $excludeAttributeCodes = [],
        array $attributeConditions = []
    ): array {
        $attributes = $this->getSearchableAttributes(
            $useBackendType,
            $excludeAttributeCodes,
            $attributeConditions
        );

        return $onlyAttributeIds ? array_keys($attributes) : $attributes;
    }

    /**
     * Returns the attribute values (option values with id and label) and URL rewrites of the categories in the store.
     *
     * @param AdapterInterface $dbAdapter
     * @param int[] $categoryIds
     * @param int $storeId
     * @param array $requiredEavAttributeCodes
     *
     * @phpstan-param array<int, string> $requiredEavAttributeCodes
     *
     * @return array category id => data
     * @phpstan-return array<int|string, array<string, mixed>>
     * @throws Exception
     */
    public function getCategoriesData(
        AdapterInterface $dbAdapter,
        array $categoryIds,
        int $storeId,
        array $requiredEavAttributeCodes = []
    ): array {
        $attributeValues = $this->getCurrentAttributeValues(
            $dbAdapter,
            \Magento\Catalog\Model\Category::ENTITY,
            $storeId,
            $requiredEavAttributeCodes,
            $categoryIds,
            ['entity_id' => true, 'store_id' => true]
        );

        $urlRewrites = $this->getUrlRewrites(
            $dbAdapter,
            $categoryIds,
            $storeId,
            'category'
        );

        $specialAttributes = ['entity_id', 'attribute_set_id', 'parent_id', 'created_at', 'updated_at'];

        $categoryData = [];

        foreach ($attributeValues as $categoryId => $categoryAttributeValues) {
            foreach ($categoryAttributeValues as $attributeCode => $attributeValue) {
                if (! $this->variables->isEmpty($attributeValue)) {
                    if (! in_array(
                        $attributeCode,
                        $specialAttributes
                    )) {
                        $attribute = $this->attributeHelper->getAttribute(
                            \Magento\Catalog\Model\Category::ENTITY,
                            $attributeCode
                        );

                        if ($attribute->usesSource()) {
                            $attributeValue = [
                                'id'    => $attributeValue,
                                'value' => $this->attributeHelper->getAttributeOptionValue(
                                    \Magento\Catalog\Model\Category::ENTITY,
                                    $attributeCode,
                                    $storeId,
                                    $this->variables->stringValue($attributeValue)
                                )
                            ];
                        }
                    }

                    $categoryData[ $categoryId ][ $attributeCode ] = $attributeValue;
                }
            }

            $categoryData[ $categoryId ][ 'url_rewrites' ] = $this->arrays->getValue(
                $urlRewrites,
                $this->variables->stringValue($categoryId),
                []
            );
        }

        return $categoryData;
    }

    /**
     * Returns the export data of the products in the store.
     *
     * The data contains the attribute values, bestseller rating, category paths, URL rewrites, gallery images, indexed
     * prices, stock item and review summary, and the data of the children of configurable, bundle and grouped
     * products.
     *
     * @param AdapterInterface $dbAdapter
     * @param int[] $productIds
     * @param int $storeId
     * @param array $attributeConditions
     * @param array $requiredEavAttributeCodes
     * @param bool $limitActiveCategoriesToStore
     *
     * @phpstan-param array<int, string> $attributeConditions
     * @phpstan-param array<int, string> $requiredEavAttributeCodes
     *
     * @return array product id => data
     * @phpstan-return array<int|string, array<string, mixed>>
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
        $searchableAttributes = $this->getExportableAttributes(
            $attributeConditions,
            $requiredEavAttributeCodes
        );

        $attributeValues = $this->getCurrentAttributeValues(
            $dbAdapter,
            \Magento\Catalog\Model\Product::ENTITY,
            $storeId,
            array_keys($searchableAttributes),
            $productIds,
            ['entity_id' => true, 'store_id' => true]
        );

        $bestsellerRatings = $this->getBestsellerRatings(
            $dbAdapter,
            $productIds,
            $storeId
        );
        $categoryPaths = $this->getCategoriesPaths(
            $dbAdapter,
            $productIds,
            $storeId,
            $limitActiveCategoriesToStore
        );
        $urlRewrites = $this->getUrlRewrites(
            $dbAdapter,
            $productIds,
            $storeId
        );
        $galleryImages = $this->getGalleryImages(
            $dbAdapter,
            $productIds,
            $storeId,
            false
        );
        $indexedPrices = $this->getIndexedPrices(
            $dbAdapter,
            $productIds,
            $storeId
        );
        $stockItems = $this->getStockItems(
            $dbAdapter,
            $productIds,
            $storeId
        );
        $reviewSummary = $this->getReviewSummary(
            $dbAdapter,
            $productIds,
            $storeId
        );

        $productsData = [];

        $configurableProductIds = [];
        $bundleProductIds = [];
        $groupProductIds = [];

        foreach ($attributeValues as $productId => $productAttributeValues) {
            foreach ($productAttributeValues as $attributeCode => $attributeValue) {
                if (! $this->variables->isEmpty($attributeValue)) {
                    $attribute = $this->arrays->getValue(
                        $searchableAttributes,
                        $attributeCode
                    );

                    if ($attribute instanceof Attribute && $attribute->usesSource()) {
                        $attributeValue = [
                            'id'    => $attributeValue,
                            'value' => $this->attributeHelper->getAttributeOptionValue(
                                \Magento\Catalog\Model\Product::ENTITY,
                                $attributeCode,
                                $storeId,
                                $this->variables->stringValue($attributeValue)
                            )
                        ];
                    }

                    $productsData[ $productId ][ $attributeCode ] = $attributeValue;
                }
            }

            $productsData[ $productId ][ 'bestseller_rating' ] = $this->arrays->getValue(
                $bestsellerRatings,
                $this->variables->stringValue($productId)
            );

            $productsData[ $productId ][ 'category_paths' ] = $this->arrays->getValue(
                $categoryPaths,
                $this->variables->stringValue($productId),
                []
            );

            $productsData[ $productId ][ 'url_rewrites' ] = $this->arrays->getValue(
                $urlRewrites,
                $this->variables->stringValue($productId),
                []
            );

            $productsData[ $productId ][ 'gallery_images' ] = $this->arrays->getValue(
                $galleryImages,
                $this->variables->stringValue($productId),
                []
            );

            $productsData[ $productId ][ 'indexed_prices' ] = $this->arrays->getValue(
                $indexedPrices,
                $this->variables->stringValue($productId),
                []
            );

            $productsData[ $productId ][ 'stock_item' ] = $this->arrays->getValue(
                $stockItems,
                $this->variables->stringValue($productId),
                []
            );

            $productsData[ $productId ][ 'review_summary' ] = $this->arrays->getValue(
                $reviewSummary,
                $this->variables->stringValue($productId),
                []
            );

            $typeId = $this->arrays->getValue(
                $productAttributeValues,
                'type_id'
            );

            if ($typeId === 'configurable') {
                $configurableProductIds[] = $productId;
            } elseif ($typeId === 'bundle') {
                $bundleProductIds[] = $productId;
            } elseif ($typeId === 'grouped') {
                $groupProductIds[] = $productId;
            }
        }

        $showOutOfStock = $this->storeHelper->getStoreConfig(
            'cataloginventory/options/show_out_of_stock',
            false,
            true
        );

        if (count($configurableProductIds) > 0) {
            $childIds = $this->productHelper->getChildIds(
                $dbAdapter,
                $configurableProductIds,
                true,
                ! $showOutOfStock,
                true,
                true,
                false,
                $storeId
            );

            if (count($childIds) > 0) {
                $this->logging->debug(
                    sprintf(
                        'Found %d child product(s)',
                        count($childIds)
                    )
                );

                $childProductsData = $this->getProductsData(
                    $dbAdapter,
                    array_keys($childIds),
                    $storeId,
                    $attributeConditions,
                    $requiredEavAttributeCodes,
                    $limitActiveCategoriesToStore
                );

                $parentsData = [];

                foreach ($childIds as $childId => $parentId) {
                    if (array_key_exists($childId, $childProductsData)) {
                        $parentsData[ $parentId ][] = $childProductsData[ $childId ];
                    }
                }

                foreach ($parentsData as $parentId => $parentData) {
                    $productsData[ $parentId ][ 'children' ] = $parentData;
                }
            }
        }

        if (count($bundleProductIds) > 0) {
            $bundledIds = $this->productHelper->getBundledIds(
                $dbAdapter,
                $bundleProductIds,
                true,
                ! $showOutOfStock,
                true,
                $storeId
            );

            if (count($bundledIds) > 0) {
                $this->logging->debug(
                    sprintf(
                        'Found %d bundled product(s)',
                        count($bundledIds)
                    )
                );

                $bundledProductsData = $this->getProductsData(
                    $dbAdapter,
                    array_keys($bundledIds),
                    $storeId,
                    $attributeConditions,
                    $requiredEavAttributeCodes,
                    $limitActiveCategoriesToStore
                );

                $parentsData = [];

                foreach ($bundledIds as $bundledId => $parentId) {
                    if (array_key_exists($bundledId, $bundledProductsData)) {
                        $parentsData[ $parentId ][] = $bundledProductsData[ $bundledId ];
                    }
                }

                foreach ($parentsData as $parentId => $parentData) {
                    $productsData[ $parentId ][ 'bundled' ] = $parentData;
                }
            }
        }

        if (count($groupProductIds) > 0) {
            $groupedIds = $this->productHelper->getGroupedIds(
                $dbAdapter,
                $groupProductIds,
                true,
                ! $showOutOfStock,
                true,
                false,
                $storeId
            );

            if (count($groupedIds) > 0) {
                $this->logging->debug(
                    sprintf(
                        'Found %d grouped product(s)',
                        count($groupedIds)
                    )
                );

                $groupedProductsData = $this->getProductsData(
                    $dbAdapter,
                    array_keys($groupedIds),
                    $storeId,
                    $attributeConditions,
                    $requiredEavAttributeCodes,
                    $limitActiveCategoriesToStore
                );

                $parentsData = [];

                foreach ($groupedIds as $groupedId => $parentId) {
                    if (array_key_exists($groupedId, $groupedProductsData)) {
                        $parentsData[ $parentId ][] = $groupedProductsData[ $groupedId ];
                    }
                }

                foreach ($parentsData as $parentId => $parentData) {
                    $productsData[ $parentId ][ 'grouped' ] = $parentData;
                }
            }
        }

        return $productsData;
    }

    /**
     * Returns the searchable product attributes, updated_at and the required attributes by attribute code.
     *
     * @param array $attributeConditions
     * @param array $requiredEavAttributeCodes
     *
     * @phpstan-param array<int, string> $attributeConditions
     * @phpstan-param array<int, string> $requiredEavAttributeCodes
     *
     * @return Attribute[]
     * @phpstan-return array<string, Attribute>
     * @throws Exception
     */
    public function getExportableAttributes(
        array $attributeConditions = [],
        array $requiredEavAttributeCodes = []
    ): array {
        $key = hash('sha256', (string)json_encode([$attributeConditions, $requiredEavAttributeCodes]));

        if (! array_key_exists(
            $key,
            $this->exportableAttributes
        )) {
            $this->exportableAttributes[ $key ] = [];

            foreach ($this->getSearchableAttributes(
                null,
                [],
                $attributeConditions
            ) as $attribute) {
                $this->exportableAttributes[ $key ][ $attribute->getAttributeCode() ] = $attribute;
            }

            $this->exportableAttributes[ $key ][ 'updated_at' ] = $this->attributeHelper->getAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'updated_at'
            );

            foreach ($requiredEavAttributeCodes as $requiredEavAttributeCode) {
                $this->exportableAttributes[ $key ][ $requiredEavAttributeCode ] = $this->attributeHelper->getAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $requiredEavAttributeCode
                );
            }
        }

        return $this->exportableAttributes[ $key ];
    }

    /**
     * Returns the bestseller rating of the products in the current month.
     *
     * Products without rating get the highest rating of the month plus one.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $productIds
     * @param int $storeId
     *
     * @phpstan-param array<int, int|string> $productIds
     *
     * @return array product id => rating position
     * @phpstan-return array<int|string, mixed>
     */
    public function getBestsellerRatings(AdapterInterface $dbAdapter, array $productIds, int $storeId): array
    {
        $bestseller = $dbAdapter->select()->from(
            $this->databaseHelper->getTableName('sales_bestsellers_aggregated_monthly'),
            ['product_id', 'rating_pos']
        );

        $bestseller->where(
            $dbAdapter->prepareSqlCondition(
                'product_id',
                ['in' => $productIds]
            ),
            null,
            Select::TYPE_CONDITION
        );

        $bestseller->where(
            $dbAdapter->prepareSqlCondition(
                'period',
                [
                    'eq' => sprintf(
                        '%s-01',
                        date('Y-m')
                    )
                ]
            ),
            null,
            Select::TYPE_CONDITION
        );

        $bestseller->where(
            $dbAdapter->prepareSqlCondition(
                'store_id',
                ['eq' => $storeId]
            ),
            null,
            Select::TYPE_CONDITION
        );

        $bestseller->order(['id DESC']);

        $queryResult = $this->databaseHelper->fetchPairs(
            $bestseller,
            $dbAdapter
        );

        foreach ($productIds as $productId) {
            $bestseller = $this->arrays->getValue(
                $queryResult,
                $this->variables->stringValue($productId)
            );

            $queryResult[ $productId ] = ! empty($bestseller) ? $this->variables->intValue($bestseller) :
                $this->getMaxBestsellerRating(
                    $dbAdapter,
                    $storeId
                );
        }

        return $queryResult;
    }

    /**
     * Returns the highest bestseller rating position of the current month in the store plus one.
     *
     * @param AdapterInterface $dbAdapter
     * @param int $storeId
     *
     * @return int
     */
    protected function getMaxBestsellerRating(AdapterInterface $dbAdapter, int $storeId): int
    {
        if (! array_key_exists($storeId, $this->maxBestsellerRatings)) {
            $tableName = $this->databaseHelper->getTableName('sales_bestsellers_aggregated_monthly');
            $tableLabel = 'bestsellers_aggregated_monthly';

            $bestseller = $dbAdapter->select()->from(
                [$tableLabel => $tableName],
                ['max(rating_pos)']
            );

            $bestseller->where(
                $dbAdapter->prepareSqlCondition(
                    $tableLabel . '.period',
                    [
                        'eq' => sprintf(
                            '%s-01',
                            date('Y-m')
                        )
                    ]
                ),
                null,
                Select::TYPE_CONDITION
            );

            $bestseller->where(
                $dbAdapter->prepareSqlCondition(
                    $tableLabel . '.store_id',
                    ['eq' => $storeId]
                ),
                null,
                Select::TYPE_CONDITION
            );

            $bestseller = $this->databaseHelper->fetchOne(
                $bestseller,
                $dbAdapter
            );

            $this->maxBestsellerRatings[ $storeId ] =
                empty($bestseller) ? 1 : ($this->variables->intValue($bestseller) + 1);
        }

        return $this->maxBestsellerRatings[ $storeId ];
    }

    /**
     * Returns name, URL path and parent id of the active categories of the products in the store.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $productIds
     * @param int $storeId
     * @param bool $limitActiveCategoriesToStore
     *
     * @phpstan-param array<int, int|string> $productIds
     *
     * @return array product id => category id => category data
     * @phpstan-return array<int|string, array<int|string, array<string, mixed>>>
     * @throws Exception
     */
    public function getCategoriesPaths(
        AdapterInterface $dbAdapter,
        array $productIds,
        int $storeId,
        bool $limitActiveCategoriesToStore = true
    ): array {
        /** @var array<int, int|string> $activeCategoryIds */
        $activeCategoryIds = $this->categoryHelper->getActiveCategoryIds(
            $dbAdapter,
            $storeId,
            $limitActiveCategoriesToStore
        );
        $storeCategoryIds = array_flip($activeCategoryIds);

        /** @var array<int|string, array<int, int|string>> $categoryIds */
        $categoryIds = $this->categoryHelper->getEntityIds(
            $dbAdapter,
            $productIds,
            true,
            false,
            true
        );

        $categoryPaths = [];

        foreach ($categoryIds as $productId => $productCategoryIds) {
            foreach ($productCategoryIds as $productCategoryId) {
                if (! array_key_exists(
                    $productCategoryId,
                    $storeCategoryIds
                )) {
                    continue;
                }

                $categoryName = $this->categoryHelper->getCategoryName(
                    $dbAdapter,
                    $this->variables->intValue($productCategoryId),
                    $storeId
                );

                if ($this->variables->isEmpty($categoryName)) {
                    $categoryName = $this->categoryHelper->getCategoryName(
                        $dbAdapter,
                        $this->variables->intValue($productCategoryId),
                        0
                    );
                }

                $categoryUrl = $this->categoryHelper->getCategoryUrlPath(
                    $dbAdapter,
                    $this->variables->intValue($productCategoryId),
                    $storeId
                );

                $parentCategoryIds = $this->categoryHelper->getParentEntityIds(
                    $dbAdapter,
                    [$productCategoryId],
                    0,
                    true
                );

                $categoryPaths[ $productId ][ $productCategoryId ] =
                    ['name' => $categoryName, 'url' => $categoryUrl, 'parent_id' => reset($parentCategoryIds)];
            }
        }

        return $categoryPaths;
    }

    /**
     * Returns the request paths of the generated URL rewrites of the entities in the store.
     *
     * For products, the paths are returned by category id (0 for the path without category).
     *
     * @param AdapterInterface $dbAdapter
     * @param array $entityIds
     * @param int $storeId
     * @param string $entityType
     *
     * @phpstan-param array<int, int|string> $entityIds
     *
     * @return array entity id => (category id =>) request path
     * @phpstan-return array<int|string, array<int, mixed>>
     */
    public function getUrlRewrites(
        AdapterInterface $dbAdapter,
        array $entityIds,
        int $storeId,
        string $entityType = 'product'
    ): array {
        $urlRewriteQuery = $dbAdapter->select()->from(
            $this->databaseHelper->getTableName('url_rewrite'),
            [
                'entity_id',
                'request_path'
            ]
        );

        $urlRewriteQuery->where(
            'entity_type = ?',
            $entityType,
            Select::TYPE_CONDITION
        );
        $urlRewriteQuery->where(
            'entity_id IN (?)',
            $entityIds,
            Select::TYPE_CONDITION
        );
        $urlRewriteQuery->where(
            'store_id = ?',
            $storeId,
            Select::TYPE_CONDITION
        );
        $urlRewriteQuery->where(
            'is_autogenerated = ?',
            1
        );

        if ($entityType === 'product') {
            $urlRewriteQuery->joinLeft(
                $this->databaseHelper->getTableName('catalog_url_rewrite_product_category'),
                'catalog_url_rewrite_product_category.url_rewrite_id = url_rewrite.url_rewrite_id',
                ['category_id']
            );
        }

        $queryResult = $this->databaseHelper->fetchAll($urlRewriteQuery);

        $result = [];

        foreach ($queryResult as $queryRow) {
            $requestPath = $this->arrays->getValue(
                $queryRow,
                'request_path'
            );

            if ($entityType === 'product') {
                $productId = $this->arrays->getValue(
                    $queryRow,
                    'entity_id'
                );
                $categoryId = $this->variables->intValue(
                    $this->arrays->getValue(
                        $queryRow,
                        'category_id',
                        0
                    )
                );

                $result[ $productId ][ $categoryId ] = $requestPath;
            } else {
                $entityId = $this->arrays->getValue(
                    $queryRow,
                    'entity_id'
                );

                $result[ $entityId ][] = $requestPath;
            }
        }

        return $result;
    }

    /**
     * Returns the media gallery images of the products with the store values (falling back to the default values).
     *
     * @param AdapterInterface $dbAdapter
     * @param array $productIds
     * @param int $storeId
     * @param bool $includeDisabled
     *
     * @phpstan-param array<int, int|string> $productIds
     *
     * @return array product id => value id => image data
     * @phpstan-return array<int|string, array<int|string, array<string, mixed>>>
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

        $galleryQuery = $dbAdapter->select()->from(
            ['gallery_value' => $galleryTableName],
            [
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
            ]
        );

        $galleryQuery->joinLeft(
            ['gallery_value_default' => $galleryValueTableName],
            sprintf(
                '%s = %s AND %s = %d',
                $dbAdapter->quoteIdentifier('gallery_value.value_id'),
                $dbAdapter->quoteIdentifier('gallery_value_default.value_id'),
                $dbAdapter->quoteIdentifier('gallery_value_default.store_id'),
                0
            ),
            []
        );

        $galleryQuery->joinLeft(
            ['gallery_value_store' => $galleryValueTableName],
            sprintf(
                '%s = %s AND %s = %d',
                $dbAdapter->quoteIdentifier('gallery_value.value_id'),
                $dbAdapter->quoteIdentifier('gallery_value_store.value_id'),
                $dbAdapter->quoteIdentifier('gallery_value_store.store_id'),
                $storeId
            ),
            []
        );

        $galleryQuery->join(
            ['gallery_value_to_entity' => $galleryValueToEntityTableName],
            sprintf(
                '%s = %s',
                $dbAdapter->quoteIdentifier('gallery_value.value_id'),
                $dbAdapter->quoteIdentifier('gallery_value_to_entity.value_id')
            ),
            ['gallery_value_to_entity.entity_id']
        );

        $attribute = $this->attributeHelper->getAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'media_gallery'
        );

        $galleryQuery->where(
            $dbAdapter->prepareSqlCondition(
                'gallery_value.attribute_id',
                ['eq' => $attribute->getId()]
            ),
            null,
            Select::TYPE_CONDITION
        );

        $galleryQuery->where(
            $dbAdapter->prepareSqlCondition(
                'gallery_value_to_entity.entity_id',
                ['in' => $productIds]
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

        if (! $includeDisabled) {
            $galleryQuery->having('disabled = 0');
        }

        $queryResult = $this->databaseHelper->fetchAssoc($galleryQuery);

        $result = [];

        foreach ($queryResult as $queryRow) {
            $productId = $this->arrays->getValue(
                $queryRow,
                'entity_id'
            );
            $valueId = $this->arrays->getValue(
                $queryRow,
                'value_id'
            );

            $result[ $productId ][ $valueId ] = $queryRow;
        }

        return $result;
    }

    /**
     * Returns the prices of the products from the price index (NOT LOGGED IN group, website of the store).
     *
     * Products missing in the index are loaded to calculate the prices.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $productIds
     * @param int $storeId
     *
     * @phpstan-param array<int, int|string> $productIds
     *
     * @return array product id => prices
     * @phpstan-return array<int|string, array<string, mixed>>
     * @throws NoSuchEntityException
     */
    public function getIndexedPrices(AdapterInterface $dbAdapter, array $productIds, int $storeId): array
    {
        $categoryQuery = $dbAdapter->select()->from(
            [$this->databaseHelper->getTableName('catalog_product_index_price')],
            [
                'entity_id',
                'price',
                'final_price',
                'min_price',
                'max_price'
            ]
        );

        $categoryQuery->where(
            $dbAdapter->prepareSqlCondition(
                'entity_id',
                ['in' => $productIds]
            ),
            null,
            Select::TYPE_CONDITION
        );

        $categoryQuery->where(
            $dbAdapter->prepareSqlCondition(
                'customer_group_id',
                ['eq' => '0']
            ),
            null,
            Select::TYPE_CONDITION
        );

        $store = $this->storeHelper->getStore($storeId);

        $categoryQuery->where(
            $dbAdapter->prepareSqlCondition(
                'website_id',
                ['eq' => $store->getWebsiteId()]
            ),
            null,
            Select::TYPE_CONDITION
        );

        $queryResult = $this->databaseHelper->fetchAssoc($categoryQuery);

        foreach ($productIds as $productId) {
            if (! array_key_exists(
                $productId,
                $queryResult
            )) {
                $product = $this->productHelper->loadProduct(
                    $this->variables->intValue($productId),
                    $storeId
                );

                if ($product->getTypeId() === 'bundle') {
                    /** @var Price $priceModel */
                    $priceModel = $product->getPriceModel();

                    $totalPrices = $priceModel->getTotalPrices(
                        $product,
                        null,
                        $this->taxHelper->displayPriceIncludingTax() ? true : null,
                        false
                    );

                    // without a "which" argument, the minimal and maximal price are returned
                    [$minimalPrice, $maximalPrice] = is_array($totalPrices) ? $totalPrices :
                        [$totalPrices, $totalPrices];

                    $queryResult[ $productId ] = [
                        'price'       => $product->getPrice(),
                        'min_price'   => $minimalPrice,
                        'max_price'   => $maximalPrice,
                        'final_price' => $minimalPrice
                    ];
                } else {
                    $queryResult[ $productId ] = [
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
     * Returns stock status and stock item fields of the products.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $productIds
     * @param int $storeId
     *
     * @phpstan-param array<int, int|string> $productIds
     *
     * @return array product id => stock data
     * @phpstan-return array<int|string, array<string, mixed>>
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

        $select = $dbAdapter->select()->from(
            ['stock_status' => $stockStatusTable],
            array_values($stockStatusFields)
        );

        $stockItemTable = $this->databaseHelper->getTableName('cataloginventory_stock_item');

        $stockItemFields = [];

        foreach ($this->getStockItemFields() as $stockItemField) {
            if (! array_key_exists(
                $stockItemField,
                $stockStatusFields
            )) {
                $stockItemFields[] = sprintf(
                    'stock_item.%s',
                    $stockItemField
                );
            }
        }

        $select->join(
            ['stock_item' => $stockItemTable],
            'stock_status.product_id = stock_item.product_id AND stock_status.stock_id = stock_item.stock_id',
            $stockItemFields
        );

        $select->where(
            'stock_status.product_id IN (?)',
            $productIds
        );
        $select->where(
            'stock_status.stock_id = ?',
            $this->getStockId($storeId)
        );

        return $this->databaseHelper->fetchAssoc($select);
    }

    /**
     * Returns the fields of the stock item that getStockItems() returns.
     *
     * @return array
     * @phpstan-return array<int, string>
     */
    protected function getStockItemFields(): array
    {
        return ['qty', 'is_in_stock', 'manage_stock'];
    }

    /**
     * Returns reviews count and rating summary of the products in the store.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $productIds
     * @param int $storeId
     *
     * @phpstan-param array<int, int|string> $productIds
     *
     * @return array product id => review summary
     * @phpstan-return array<int|string, array<string, mixed>>
     */
    public function getReviewSummary(AdapterInterface $dbAdapter, array $productIds, int $storeId): array
    {
        $entityIdSelect = $dbAdapter->select()->from(
            $this->databaseHelper->getTableName('review_entity'),
            ['entity_id']
        );

        $entityIdSelect->where(
            'entity_code = ?',
            'product'
        );

        $entityId = $this->variables->intValue($this->databaseHelper->fetchOne($entityIdSelect) ?? 0);

        $select = $dbAdapter->select()->from(
            $this->databaseHelper->getTableName('review_entity_summary'),
            ['entity_pk_value', 'reviews_count', 'rating_summary']
        );

        $select->where(
            'entity_pk_value IN (?)',
            $productIds
        );
        $select->where(
            'entity_type = ?',
            $entityId
        );
        $select->where(
            'store_id = ?',
            $storeId
        );

        return $this->databaseHelper->fetchAssoc($select);
    }

    /**
     * Loads the values of the attributes of the entities in the store, in chunks of 25 attributes.
     *
     * @param AdapterInterface $dbAdapter
     * @param string $entityTypeCode product, category, customer or customer address
     * @param int $storeId
     * @param array $attributeCodes
     * @param array $entityIds
     * @param array $specialAttributes attribute code => true for static attributes, which are joined with inner join
     * @param AbstractCollection|null $collection
     *
     * @phpstan-param array<int|string, int|string> $attributeCodes
     * @phpstan-param array<int, int|string> $entityIds
     * @phpstan-param array<string, bool> $specialAttributes
     *
     * @return array entity id => attribute code => value
     * @phpstan-return array<int|string, array<string, mixed>>
     * @throws Exception
     */
    public function getCurrentAttributeValues(
        AdapterInterface $dbAdapter,
        string $entityTypeCode,
        int $storeId,
        array $attributeCodes,
        array $entityIds,
        array $specialAttributes,
        ?AbstractCollection $collection = null
    ): array {
        if (empty($attributeCodes) || empty($entityIds)) {
            return [];
        }

        $currentValues = [];

        $attributeCodeChunks = array_chunk(
            $attributeCodes,
            25,
            true
        );

        if ($collection === null) {
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
                throw new InvalidArgumentException(
                    sprintf(
                        'Entity type: %s not implemented yet',
                        $entityTypeCode
                    )
                );
            }
        }

        foreach ($attributeCodeChunks as $attributeChunk) {
            $chunkCollection = clone $collection;
            $chunkCollection->clear();

            foreach ($attributeChunk as $attributeCode) {
                $chunkCollection->addAttributeToSelect(
                    $attributeCode,
                    in_array(
                        $attributeCode,
                        array_keys($specialAttributes)
                    ) ? false : 'left'
                );
            }

            if (count($entityIds) == 1) {
                $entityId = reset($entityIds);

                $chunkCollection->addAttributeToFilter(
                    'entity_id',
                    ['eq' => $entityId]
                );
            } else {
                $chunkCollection->addAttributeToFilter(
                    'entity_id',
                    ['in' => $entityIds]
                );
            }

            $columns = $chunkCollection->getSelect()->getPart(Select::COLUMNS);
            $columns = is_array($columns) ? $columns : [];

            foreach ($columns as $key => $column) {
                if (is_array($column) && array_key_exists(
                    1,
                    $column
                )) {
                    if ($column[ 1 ] == 'entity_id') {
                        unset($columns[ $key ]);
                    }
                }
            }

            array_unshift(
                $columns,
                ['0' => 'e', 1 => 'entity_id', 2 => 'entity_id']
            );

            $chunkCollection->getSelect()->setPart(
                Select::COLUMNS,
                $columns
            );

            $queryResult = $this->databaseHelper->fetchAssoc(
                $chunkCollection->getSelect(),
                $dbAdapter
            );

            foreach ($queryResult as $key => $row) {
                $currentValues[ $key ] = array_key_exists(
                    $key,
                    $currentValues
                ) ? $row + $currentValues[ $key ] : $row;
            }
        }

        return $currentValues;
    }
}
