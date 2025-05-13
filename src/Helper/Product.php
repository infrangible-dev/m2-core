<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Magento\Catalog\Api\Data\ProductTierPriceInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Pricing\Price\TierPrice;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\Collection as AttributeCollection;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Locale\Format;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Zend_Db_Select;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2025 Softwareentwicklung Andreas Knollmann
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

    /** @var Variables */
    protected $variables;

    /** @var Format */
    protected $localeFormat;

    /** @var \Magento\Tax\Helper\Data */
    protected $taxHelper;

    /** @var PriceCurrencyInterface */
    protected $priceCurrency;

    /** @var array */
    private $entitySkus = [];

    /** @var array */
    private $websiteIds = [];

    /** @var array */
    private $types;

    /** @var array */
    private $categoryIds = [];

    /** @var array */
    private $usedProducts = [];

    /** @var array */
    private $usedProductAttributeIds = [];

    /** @var array */
    private $usedProductAttributes = [];

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
        ConfigInterface $config,
        Variables $variables,
        Format $localeFormat,
        \Magento\Tax\Helper\Data $taxHelper,
        PriceCurrencyInterface $priceCurrency
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
        $this->variables = $variables;
        $this->localeFormat = $localeFormat;
        $this->taxHelper = $taxHelper;
        $this->priceCurrency = $priceCurrency;
    }

    public function newProduct(): \Magento\Catalog\Model\Product
    {
        return $this->productFactory->create();
    }

    public function loadProduct(int $productId, int $storeId = null): \Magento\Catalog\Model\Product
    {
        $product = $this->newProduct();

        if (! empty($storeId)) {
            $product->setStoreId($storeId);
        }

        $this->productResourceFactory->create()->load(
            $product,
            $productId
        );

        return $product;
    }

    public function loadProductBySku(string $productSku, int $storeId = null): \Magento\Catalog\Model\Product
    {
        $product = $this->newProduct();

        $productId = $product->getIdBySku($productSku);

        if (! empty($storeId)) {
            $product->setDataUsingMethod(
                'store_id',
                $storeId
            );
        }

        $this->productResourceFactory->create()->load(
            $product,
            $productId
        );

        return $product;
    }

    /**
     * @throws Exception
     */
    public function saveProduct(\Magento\Catalog\Model\Product $product): void
    {
        $this->productResourceFactory->create()->save($product);
    }

    public function getProductCollection(): Collection
    {
        return $this->productCollectionFactory->create();
    }

    public function getProductMediaConfig(): Config
    {
        return $this->productMediaConfig;
    }

    /**
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
        $this->logging->debug(
            sprintf(
                'Searching child ids for parent id(s): %s',
                implode(
                    ', ',
                    $parentIds
                )
            )
        );

        $childIdQuery = $this->getChildIdQuery(
            $dbAdapter,
            $parentIds,
            $excludeInactive,
            $useSuperLink,
            $includeParents,
            $storeId
        );

        if ($excludeOutOfStock) {
            $tableName = $this->databaseHelper->getTableName(
                $useSuperLink ? 'catalog_product_super_link' : 'catalog_product_relation'
            );
            $childColumnName = $useSuperLink ? 'product_id' : 'child_id';

            $childIdQuery->join(
                ['stock_item' => $this->databaseHelper->getTableName('cataloginventory_stock_item')],
                sprintf(
                    '%s = %s',
                    $dbAdapter->quoteIdentifier('stock_item.product_id'),
                    $dbAdapter->quoteIdentifier(
                        sprintf(
                            '%s.%s',
                            $tableName,
                            $childColumnName
                        )
                    )
                ),
                []
            );

            $childIdQuery->where(
                $dbAdapter->prepareSqlCondition(
                    $dbAdapter->quoteIdentifier('stock_item.is_in_stock'),
                    ['eq' => 1]
                ),
                null,
                Select::TYPE_CONDITION
            );
        }

        $queryResult = $this->databaseHelper->fetchAssoc(
            $childIdQuery,
            $dbAdapter
        );

        if ($maintainAssociation) {
            $childIds = [];

            foreach ($queryResult as $childId => $queryRow) {
                $childIds[ (int)$childId ] = (int)$this->arrays->getValue(
                    $queryRow,
                    'parent_id'
                );
            }
        } else {
            $childIds = array_keys($queryResult);

            $childIds = array_map(
                'intval',
                $childIds
            );
        }

        $this->logging->debug(
            sprintf(
                'Found %d child id(s)',
                count($childIds)
            )
        );

        return $childIds;
    }

    /**
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

        $childIdQuery = $dbAdapter->select()->from(
            [$tableName],
            [
                $childColumnName,
                'parent_id'
            ]
        );

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
                    sprintf(
                        '%s.%s',
                        $tableName,
                        $childColumnName
                    ),
                    ['in' => $parentIds]
                ),
                null,
                Select::TYPE_CONDITION
            );
        }

        if ($excludeInactive) {
            $statusAttribute = $this->attributeHelper->getAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'status'
            );

            if (empty($storeId)) {
                $childIdQuery->join(
                    ['status0' => $statusAttribute->getBackend()->getTable()],
                    $dbAdapter->quoteInto(
                        sprintf(
                            '%s = %s AND %s = ? AND %s = 0',
                            $dbAdapter->quoteIdentifier('status0.entity_id'),
                            $dbAdapter->quoteIdentifier(
                                sprintf(
                                    '%s.%s',
                                    $tableName,
                                    $childColumnName
                                )
                            ),
                            $dbAdapter->quoteIdentifier('status0.attribute_id'),
                            $dbAdapter->quoteIdentifier('status0.store_id')
                        ),
                        $statusAttribute->getAttributeId()
                    ),
                    []
                );

                $childIdQuery->where(
                    $dbAdapter->prepareSqlCondition(
                        $dbAdapter->quoteIdentifier('status0.value'),
                        ['eq' => Status::STATUS_ENABLED]
                    ),
                    null,
                    Select::TYPE_CONDITION
                );
            } else {
                $childIdQuery->joinLeft(
                    ['status0' => $statusAttribute->getBackend()->getTable()],
                    $dbAdapter->quoteInto(
                        sprintf(
                            '%s = %s AND %s = ? AND %s = 0',
                            $dbAdapter->quoteIdentifier('status0.entity_id'),
                            $dbAdapter->quoteIdentifier(
                                sprintf(
                                    '%s.%s',
                                    $tableName,
                                    $childColumnName
                                )
                            ),
                            $dbAdapter->quoteIdentifier('status0.attribute_id'),
                            $dbAdapter->quoteIdentifier('status0.store_id')
                        ),
                        $statusAttribute->getAttributeId()
                    ),
                    []
                );

                $tableAlias = sprintf(
                    'status_%d',
                    $storeId
                );

                $childIdQuery->joinLeft(
                    [$tableAlias => $statusAttribute->getBackend()->getTable()],
                    sprintf(
                        '%s = %s AND %s = %d AND %s = %d',
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.entity_id',
                                $tableAlias
                            )
                        ),
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.%s',
                                $tableName,
                                $childColumnName
                            )
                        ),
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.attribute_id',
                                $tableAlias
                            )
                        ),
                        $statusAttribute->getAttributeId(),
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.store_id',
                                $tableAlias
                            )
                        ),
                        $storeId
                    ),
                    []
                );

                $childIdQuery->where(
                    $dbAdapter->getIfNullSql(
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.value',
                                $tableAlias
                            )
                        ),
                        $dbAdapter->quoteIdentifier('status0.value')
                    ) . ' = ?',
                    Status::STATUS_ENABLED
                );
            }
        }

        return $childIdQuery;
    }

    /**
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
        $this->logging->debug(
            sprintf(
                'Searching bundled ids for parent id(s): %s',
                implode(
                    ', ',
                    $parentIds
                )
            )
        );

        $buildIdQuery = $this->getBundledIdQuery(
            $dbAdapter,
            $parentIds,
            $excludeInactive,
            $storeId
        );

        if ($excludeOutOfStock) {
            $tableName = $this->databaseHelper->getTableName('catalog_product_bundle_selection');

            $buildIdQuery->join(
                ['stock_item' => $this->databaseHelper->getTableName('cataloginventory_stock_item')],
                sprintf(
                    '%s = %s',
                    $dbAdapter->quoteIdentifier('stock_item.product_id'),
                    $dbAdapter->quoteIdentifier(
                        sprintf(
                            '%s.%s',
                            $tableName,
                            'product_id'
                        )
                    )
                ),
                []
            );

            $buildIdQuery->where(
                $dbAdapter->prepareSqlCondition(
                    $dbAdapter->quoteIdentifier('stock_item.is_in_stock'),
                    ['eq' => 1]
                ),
                null,
                Select::TYPE_CONDITION
            );
        }

        $queryResult = $this->databaseHelper->fetchAssoc(
            $buildIdQuery,
            $dbAdapter
        );

        if ($maintainAssociation) {
            $childIds = [];

            foreach ($queryResult as $childId => $queryRow) {
                $childIds[ (int)$childId ] = (int)$this->arrays->getValue(
                    $queryRow,
                    'parent_product_id'
                );
            }
        } else {
            $childIds = array_keys($queryResult);

            $childIds = array_map(
                'intval',
                $childIds
            );
        }

        $this->logging->debug(
            sprintf(
                'Found %d bundled id(s)',
                count($childIds)
            )
        );

        return $childIds;
    }

    /**
     * @throws Exception
     */
    public function getBundledIdQuery(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        int $storeId = null
    ): Select {
        $tableName = $this->databaseHelper->getTableName('catalog_product_bundle_selection');

        $bundleIdQuery = $dbAdapter->select()->from(
            [$tableName],
            [
                'product_id',
                'parent_product_id'
            ]
        );

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
            $statusAttribute = $this->attributeHelper->getAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'status'
            );

            if (empty($storeId)) {
                $bundleIdQuery->join(
                    ['status_0' => $statusAttribute->getBackend()->getTable()],
                    $dbAdapter->quoteInto(
                        sprintf(
                            '%s = %s AND %s = ? AND %s = 0',
                            $dbAdapter->quoteIdentifier('status_0.entity_id'),
                            $dbAdapter->quoteIdentifier(
                                sprintf(
                                    '%s.%s',
                                    $tableName,
                                    'product_id'
                                )
                            ),
                            $dbAdapter->quoteIdentifier('status_0.attribute_id'),
                            $dbAdapter->quoteIdentifier('status_0.store_id')
                        ),
                        $statusAttribute->getAttributeId()
                    ),
                    []
                );

                $bundleIdQuery->where(
                    $dbAdapter->prepareSqlCondition(
                        $dbAdapter->quoteIdentifier('status_0.value'),
                        ['eq' => Status::STATUS_ENABLED]
                    ),
                    null,
                    Select::TYPE_CONDITION
                );
            } else {
                $bundleIdQuery->joinLeft(
                    ['status_0' => $statusAttribute->getBackend()->getTable()],
                    $dbAdapter->quoteInto(
                        sprintf(
                            '%s = %s AND %s = ? AND %s = 0',
                            $dbAdapter->quoteIdentifier('status_0.entity_id'),
                            $dbAdapter->quoteIdentifier(
                                sprintf(
                                    '%s.%s',
                                    $tableName,
                                    'product_id'
                                )
                            ),
                            $dbAdapter->quoteIdentifier('status_0.attribute_id'),
                            $dbAdapter->quoteIdentifier('status_0.store_id')
                        ),
                        $statusAttribute->getAttributeId()
                    ),
                    []
                );

                $tableAlias = sprintf(
                    'status_%d',
                    $storeId
                );

                $bundleIdQuery->joinLeft(
                    [$tableAlias => $statusAttribute->getBackend()->getTable()],
                    sprintf(
                        '%s = %s AND %s = %d AND %s = %d',
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.entity_id',
                                $tableAlias
                            )
                        ),
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.%s',
                                $tableName,
                                'product_id'
                            )
                        ),
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.attribute_id',
                                $tableAlias
                            )
                        ),
                        $statusAttribute->getAttributeId(),
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.store_id',
                                $tableAlias
                            )
                        ),
                        $storeId
                    ),
                    []
                );

                $bundleIdQuery->where(
                    $dbAdapter->getIfNullSql(
                        $dbAdapter->quoteIdentifier(
                            sprintf(
                                '%s.value',
                                $tableAlias
                            )
                        ),
                        $dbAdapter->quoteIdentifier('status_0.value')
                    ) . ' = ?',
                    Status::STATUS_ENABLED
                );
            }
        }

        return $bundleIdQuery;
    }

    /**
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

    public function getConfigurableAttributeCollection(): AttributeCollection
    {
        return $this->configurableAttributeCollectionFactory->create();
    }

    public function determineSKUs(array $entityIds, bool $keepAssociation = false): array
    {
        $skus = [];

        $this->logging->debug(
            sprintf(
                'Determining skus for %d entity id(s)',
                count($entityIds)
            )
        );

        foreach ($entityIds as $key => $entityId) {
            if (array_key_exists(
                $entityId,
                $this->entitySkus
            )) {
                $skus[ $key ] = $this->entitySkus[ $entityId ];

                unset($entityIds[ $key ]);
            }
        }

        $this->logging->debug(
            sprintf(
                'Searching skus for %d entity id(s)',
                count($entityIds)
            )
        );

        if (! empty($entityIds)) {
            $entityIdChunks = array_chunk(
                $entityIds,
                1000,
                true
            );

            foreach ($entityIdChunks as $entityIdChunk) {
                $productCollection = $this->getProductCollection();

                $productCollection->getSelect()->reset(Zend_Db_Select::COLUMNS);
                $productCollection->getSelect()->columns(
                    [
                        'entity_id',
                        'sku'
                    ]
                );

                $productCollection->addAttributeToFilter(
                    'entity_id',
                    ['in' => $entityIdChunk]
                );

                $productData = $this->databaseHelper->fetchAssoc($productCollection->getSelect());

                foreach ($entityIdChunk as $elementNumber => $entityId) {
                    if (array_key_exists(
                        $entityId,
                        $productData
                    )) {
                        if (array_key_exists(
                            'sku',
                            $productData[ $entityId ]
                        )) {
                            $skus[ $keepAssociation ? $entityId : $elementNumber ] = $productData[ $entityId ][ 'sku' ];

                            $this->entitySkus[ $entityId ] = $productData[ $entityId ][ 'sku' ];
                        }
                    }
                }
            }
        }

        $this->logging->debug(
            sprintf(
                'Found %d sku(s)',
                count($skus)
            )
        );

        return $skus;
    }

    public function determineEntityIds(array $skus): array
    {
        $entityIds = [];

        $this->logging->debug(
            sprintf(
                'Determining entity ids for %d sku(s)',
                count($skus)
            )
        );

        $entitySkus = array_flip($this->entitySkus);

        foreach ($skus as $key => $sku) {
            $entityId = array_key_exists(
                $sku,
                $entitySkus
            ) ? $entitySkus[ $sku ] : false;

            if ($entityId !== false) {
                $entityIds[ $key ] = $entityId;

                unset($skus[ $key ]);
            }
        }

        $this->logging->debug(
            sprintf(
                'Searching entity ids for %d sku(s)',
                count($skus)
            )
        );

        if (! empty($skus)) {
            $skuChunks = array_chunk(
                $skus,
                1000,
                true
            );

            foreach ($skuChunks as $skuChunk) {
                $productCollection = $this->getProductCollection();

                $productCollection->getSelect()->reset(Zend_Db_Select::COLUMNS);
                $productCollection->getSelect()->columns(
                    [
                        'sku',
                        'entity_id'
                    ]
                );

                $productCollection->addAttributeToFilter(
                    'sku',
                    ['in' => $skuChunk]
                );

                $productData = $this->databaseHelper->fetchAssoc($productCollection->getSelect());

                foreach ($skuChunk as $elementNumber => $sku) {
                    if (array_key_exists(
                        $sku,
                        $productData
                    )) {
                        if (array_key_exists(
                            'entity_id',
                            $productData[ $sku ]
                        )) {
                            $entityIds[ $elementNumber ] = $productData[ $sku ][ 'entity_id' ];

                            $this->entitySkus[ $productData[ $sku ][ 'entity_id' ] ] = $sku;
                        }
                    }
                }
            }
        }

        $this->logging->debug(
            sprintf(
                'Found %d entity id(s)',
                count($entityIds)
            )
        );

        return $entityIds;
    }

    public function getParentIds(
        AdapterInterface $dbAdapter,
        array $childIds,
        bool $maintainAssociation = false,
        bool $useSuperLink = true,
        bool $includeChildren = false
    ): array {
        $this->logging->debug(
            sprintf(
                'Searching parent ids for child id(s): %s',
                implode(
                    ', ',
                    $childIds
                )
            )
        );

        $tableName = $this->databaseHelper->getTableName(
            $useSuperLink ? 'catalog_product_super_link' : 'catalog_product_relation'
        );

        $childColumnName = $useSuperLink ? 'product_id' : 'child_id';

        $superLinkQuery = $dbAdapter->select()->from(
            [$tableName],
            [
                'parent_id',
                $childColumnName
            ]
        );

        $superLinkQuery->where(
            $dbAdapter->prepareSqlCondition(
                $childColumnName,
                ['in' => $childIds]
            ),
            null,
            Select::TYPE_CONDITION
        );

        if ($includeChildren) {
            // in case parent ids are included in the child id list
            $superLinkQuery->orWhere(
                $dbAdapter->prepareSqlCondition(
                    'parent_id',
                    ['in' => $childIds]
                ),
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

        $parentIds = array_map(
            'intval',
            $parentIds
        );

        $this->logging->debug(
            sprintf(
                'Found %d parent id(s)',
                count($parentIds)
            )
        );

        return $parentIds;
    }

    public function getWebsiteIds(AdapterInterface $dbAdapter, int $productId): array
    {
        if (! array_key_exists(
            $productId,
            $this->websiteIds
        )) {
            $tableName = $this->databaseHelper->getTableName('catalog_product_website');

            $websiteQuery = $dbAdapter->select()->from(
                [$tableName],
                ['website_id']
            );

            $websiteQuery->where(
                $dbAdapter->prepareSqlCondition(
                    'product_id',
                    ['eq' => $productId]
                ),
                null,
                Select::TYPE_CONDITION
            );

            $queryResult = $this->databaseHelper->fetchAssoc($websiteQuery);

            $this->websiteIds[ $productId ] = array_keys($queryResult);
        }

        return $this->websiteIds[ $productId ];
    }

    /**
     * Get product types
     */
    public function getTypes(): array
    {
        if ($this->types === null) {
            $productTypes = $this->config->getAll();

            foreach ($productTypes as $productTypeKey => $productTypeConfig) {
                $productTypes[ $productTypeKey ][ 'label' ] = __($productTypeConfig[ 'label' ]);
            }

            $this->types = $productTypes;
        }

        return $this->types;
    }

    public function getCategoryIds(AdapterInterface $dbAdapter, array $productIds): array
    {
        $result = [];

        $loadProductIds = [];

        foreach ($productIds as $productId) {
            if (array_key_exists(
                $productId,
                $this->categoryIds
            )) {
                $result[ $productId ] = $this->categoryIds[ $productId ];
            } else {
                $loadProductIds[] = $productId;
            }
        }

        if (! empty($loadProductIds)) {
            $categoryQuery = $this->databaseHelper->select(
                $this->databaseHelper->getTableName('catalog_category_product'),
                ['product_id', 'category_id']
            );

            $categoryQuery->where(
                'product_id IN (?)',
                $loadProductIds
            );

            $queryResult = $this->databaseHelper->fetchAll(
                $categoryQuery,
                $dbAdapter
            );

            foreach ($queryResult as $row) {
                $productId = $this->arrays->getValue(
                    $row,
                    'product_id'
                );
                $categoryId = $this->arrays->getValue(
                    $row,
                    'category_id'
                );

                $this->categoryIds[ $productId ][] = $categoryId;
                $result[ $productId ][] = $categoryId;
            }
        }

        return $result;
    }

    /**
     * @param null|string|bool|int|Store $store
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

    /**
     * @return \Magento\Catalog\Model\Product[]
     */
    public function getUsedProducts(\Magento\Catalog\Model\Product $product, bool $onlyEnabled = true): array
    {
        if ($product->getTypeId() !== 'configurable') {
            return [];
        }

        $cacheKey = sprintf(
            '%d_%d_%s',
            $product->getId(),
            $product->getStoreId(),
            var_export(
                $onlyEnabled,
                true
            )
        );

        if (! array_key_exists(
            $cacheKey,
            $this->usedProducts
        )) {
            $this->usedProducts[ $cacheKey ] = [];

            $typeInstance = $product->getTypeInstance();

            if ($typeInstance instanceof Configurable) {
                $allProducts = $typeInstance->getUsedProducts($product);

                /** @var \Magento\Catalog\Model\Product $simpleProduct */
                foreach ($allProducts as $simpleProduct) {
                    $add = true;

                    if ($onlyEnabled) {
                        $add = (int)$simpleProduct->getStatus() === Status::STATUS_ENABLED;
                    }

                    if ($add) {
                        $this->usedProducts[ $cacheKey ][] = $simpleProduct;
                    }
                }
            }
        }

        return $this->usedProducts[ $cacheKey ];
    }

    public function getUsedProductAttributeIds(\Magento\Catalog\Model\Product $product): array
    {
        if ($product->getTypeId() !== 'configurable') {
            return [];
        }

        if (! array_key_exists(
            $product->getId(),
            $this->usedProductAttributeIds
        )) {
            $typeInstance = $product->getTypeInstance();

            if ($typeInstance instanceof Configurable) {
                $this->usedProductAttributeIds[ $product->getId() ] = [];

                $attributes = $typeInstance->getConfigurableAttributes($product);

                foreach ($attributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();

                    try {
                        $attributeId = $this->variables->intValue($productAttribute->getAttributeId());

                        $this->usedProductAttributeIds[ $product->getId() ][] = $attributeId;
                    } catch (Exception $exception) {
                    }
                }
            }
        }

        return $this->usedProductAttributeIds[ $product->getId() ];
    }

    public function getUsedProductAttributes(\Magento\Catalog\Model\Product $product, bool $onlyEnabled = true): array
    {
        if ($product->getTypeId() !== 'configurable') {
            return [];
        }

        $cacheKey = sprintf(
            '%d_%d_%s',
            $product->getId(),
            $product->getStoreId(),
            var_export(
                $onlyEnabled,
                true
            )
        );

        if (! array_key_exists(
            $cacheKey,
            $this->usedProductAttributes
        )) {
            $usedProducts = $this->getUsedProducts(
                $product,
                $onlyEnabled
            );

            $typeInstance = $product->getTypeInstance();

            if ($typeInstance instanceof Configurable) {
                $attributes = $typeInstance->getConfigurableAttributes($product);

                foreach ($usedProducts as $usedProduct) {
                    try {
                        $usedProductId = $this->variables->intValue($usedProduct->getEntityId());

                        foreach ($attributes as $attribute) {
                            $productAttribute = $attribute->getProductAttribute();

                            $attributeId = $this->variables->intValue($productAttribute->getAttributeId());
                            $attributCode = $productAttribute->getAttributeCode();

                            $this->usedProductAttributes[ $cacheKey ][ $usedProductId ][ $attributeId ] =
                                $this->variables->intValue($usedProduct->getData($attributCode));
                        }
                    } catch (Exception $exception) {
                    }
                }
            }
        }

        return $this->usedProductAttributes[ $cacheKey ];
    }

    public function getUsedProductsPrices(\Magento\Catalog\Model\Product $product, bool $onlyEnabled = true): array
    {
        $prices = [];

        foreach ($this->getUsedProducts(
            $product,
            $onlyEnabled
        ) as $usedProduct) {
            $priceInfo = $usedProduct->getPriceInfo();

            $prices[ $usedProduct->getId() ] = [
                'baseOldPrice' => [
                    'amount' => $this->localeFormat->getNumber(
                        $priceInfo->getPrice('regular_price')->getAmount()->getBaseAmount()
                    ),
                ],
                'oldPrice'     => [
                    'amount' => $this->localeFormat->getNumber(
                        $priceInfo->getPrice('regular_price')->getAmount()->getValue()
                    ),
                ],
                'basePrice'    => [
                    'amount' => $this->localeFormat->getNumber(
                        $priceInfo->getPrice('final_price')->getAmount()->getBaseAmount()
                    ),
                ],
                'finalPrice'   => [
                    'amount' => $this->localeFormat->getNumber(
                        $priceInfo->getPrice('final_price')->getAmount()->getValue()
                    ),
                ],
                'tierPrices'   => $this->getTierPricesByProduct($product),
                'msrpPrice'    => [
                    'amount' => $this->localeFormat->getNumber(
                        $this->priceCurrency->convertAndRound($product->getDataUsingMethod('msrp'))
                    ),
                ],
            ];
        }

        return $prices;
    }

    public function getUsedProductsSkus(\Magento\Catalog\Model\Product $product, bool $onlyEnabled = true): array
    {
        $skus = [];

        foreach ($this->getUsedProducts($product, $onlyEnabled) as $product) {
            $skus[ $product->getId() ] = $product->getSku();
        }

        return $skus;
    }

    /**
     * @return ProductTierPriceInterface[]
     */
    public function getCustomerGroupTierPrices(\Magento\Catalog\Model\Product $product, int $customerGroupId): array
    {
        $tierPrices = $product->getTierPrices();

        if ($tierPrices === null) {
            return [];
        }

        $customerGroupTierPrices = [];

        foreach ($tierPrices as $tierPrice) {
            if ($tierPrice->getCustomerGroupId() == $customerGroupId) {
                $customerGroupTierPrices[] = $tierPrice;
            }
        }

        return $customerGroupTierPrices;
    }

    public function getTierPricesByProduct(\Magento\Catalog\Model\Product $product): array
    {
        $tierPrices = [];

        /** @var TierPrice $tierPriceModel */
        $tierPriceModel = $product->getPriceInfo()->getPrice('tier_price');

        foreach ($tierPriceModel->getTierPriceList() as $tierPrice) {
            $price = $this->taxHelper->displayPriceExcludingTax() ? $tierPrice[ 'price' ]->getBaseAmount() :
                $tierPrice[ 'price' ]->getValue();

            $tierPriceData = [
                'qty'        => $this->localeFormat->getNumber($tierPrice[ 'price_qty' ]),
                'price'      => $this->localeFormat->getNumber($price),
                'percentage' => $this->localeFormat->getNumber(
                    $tierPriceModel->getSavePercent($tierPrice[ 'price' ])
                ),
            ];

            if ($this->taxHelper->displayBothPrices()) {
                $tierPriceData[ 'basePrice' ] = $this->localeFormat->getNumber(
                    $tierPrice[ 'price' ]->getBaseAmount()
                );
            }

            $tierPrices[] = $tierPriceData;
        }

        return $tierPrices;
    }
}
