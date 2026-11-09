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
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\Format;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Product
{
    /** @var Arrays */
    protected Arrays $arrays;

    /** @var Attribute */
    protected Attribute $attributeHelper;

    /** @var Database */
    protected Database $databaseHelper;

    /** @var Data */
    protected Data $catalogHelper;

    /** @var LoggerInterface */
    protected LoggerInterface $logging;

    /** @var ProductFactory */
    protected ProductFactory $productFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\ProductFactory */
    protected \Magento\Catalog\Model\ResourceModel\ProductFactory $productResourceFactory;

    /** @var CollectionFactory */
    protected CollectionFactory $productCollectionFactory;

    /** @var Config */
    protected Config $productMediaConfig;

    /** @var Instances */
    protected Instances $instanceHelper;

    /** @var ConfigInterface */
    protected ConfigInterface $config;

    /** @var Variables */
    protected Variables $variables;

    /** @var Format */
    protected Format $localeFormat;

    /** @var \Magento\Tax\Helper\Data */
    protected \Magento\Tax\Helper\Data $taxHelper;

    /** @var PriceCurrencyInterface */
    protected PriceCurrencyInterface $priceCurrency;

    /** @var array<int|string, string> SKUs by entity id */
    private array $entitySkus = [];

    /** @var array<int, array<int|string>> website ids by product id */
    private array $websiteIds = [];

    /** @var array<string, array<string, mixed>>|null */
    private ?array $types = null;

    /** @var array<int|string, array<int, mixed>> category ids by product id */
    private array $categoryIds = [];

    /** @var array<string, \Magento\Catalog\Model\Product[]> */
    private array $usedProducts = [];

    /** @var array<int|string, int[]> */
    private array $usedProductAttributeIds = [];

    /** @var array<string, array<int, array<int, int>>> */
    private array $usedProductAttributes = [];

    /**
     * @param Arrays $arrays
     * @param Attribute $attributeHelper
     * @param Database $databaseHelper
     * @param Data $catalogHelper
     * @param LoggerInterface $logging
     * @param ProductFactory $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\ProductFactory $productResourceFactory
     * @param CollectionFactory $productCollectionFactory
     * @param Config $productMediaConfig
     * @param Instances $instanceHelper
     * @param ConfigInterface $config
     * @param Variables $variables
     * @param Format $localeFormat
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param PriceCurrencyInterface $priceCurrency
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
        Instances $instanceHelper,
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
        $this->instanceHelper = $instanceHelper;
        $this->config = $config;
        $this->variables = $variables;
        $this->localeFormat = $localeFormat;
        $this->taxHelper = $taxHelper;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Creates a new product.
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function newProduct(): \Magento\Catalog\Model\Product
    {
        return $this->productFactory->create();
    }

    /**
     * Loads the product.
     *
     * @param int $productId
     * @param int|null $storeId
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function loadProduct(int $productId, ?int $storeId = null): \Magento\Catalog\Model\Product
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

    /**
     * Loads the product with the SKU.
     *
     * @param string $productSku
     * @param int|null $storeId
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function loadProductBySku(string $productSku, ?int $storeId = null): \Magento\Catalog\Model\Product
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
     * Saves the product.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return void
     * @throws Exception
     */
    public function saveProduct(\Magento\Catalog\Model\Product $product): void
    {
        $this->productResourceFactory->create()->save($product);
    }

    /**
     * Returns a new product collection.
     *
     * @return Collection
     */
    public function getProductCollection(): Collection
    {
        return $this->productCollectionFactory->create();
    }

    /**
     * Returns the media configuration of products.
     *
     * @return Config
     */
    public function getProductMediaConfig(): Config
    {
        return $this->productMediaConfig;
    }

    /**
     * Returns the ids of the child products (configurable or all relations) of the products.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $parentIds
     * @param bool $excludeInactive
     * @param bool $excludeOutOfStock
     * @param bool $maintainAssociation
     * @param bool $useSuperLink
     * @param bool $includeParents
     * @param int|null $storeId
     *
     * @phpstan-param array<int|string> $parentIds
     *
     * @return array
     * @phpstan-return array<int, int> child ids, or parent ids by child id if $maintainAssociation is true
     * @throws LocalizedException
     */
    public function getChildIds(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        bool $excludeOutOfStock = false,
        bool $maintainAssociation = false,
        bool $useSuperLink = true,
        bool $includeParents = false,
        ?int $storeId = null
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
                $childIds[ (int)$childId ] = $this->idValue(
                    $this->arrays->getValue($queryRow, 'parent_id')
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
     * Returns the query for the ids of the child products of the products.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $parentIds
     * @param bool $excludeInactive
     * @param bool $useSuperLink
     * @param bool $includeParents
     * @param int|null $storeId
     *
     * @phpstan-param array<int|string> $parentIds
     *
     * @return Select
     * @throws LocalizedException
     */
    public function getChildIdQuery(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        bool $useSuperLink = true,
        bool $includeParents = false,
        ?int $storeId = null
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
     * Returns the ids of the products in the options of the bundle products.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $parentIds
     * @param bool $excludeInactive
     * @param bool $excludeOutOfStock
     * @param bool $maintainAssociation
     * @param int|null $storeId
     *
     * @phpstan-param array<int|string> $parentIds
     *
     * @return array
     * @phpstan-return array<int, int> child ids, or parent ids by child id if $maintainAssociation is true
     * @throws LocalizedException
     */
    public function getBundledIds(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        bool $excludeOutOfStock = false,
        bool $maintainAssociation = false,
        ?int $storeId = null
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
                $childIds[ (int)$childId ] = $this->idValue(
                    $this->arrays->getValue($queryRow, 'parent_product_id')
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
     * Returns the query for the ids of the products in the options of the bundle products.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $parentIds
     * @param bool $excludeInactive
     * @param int|null $storeId
     *
     * @phpstan-param array<int|string> $parentIds
     *
     * @return Select
     * @throws LocalizedException
     */
    public function getBundledIdQuery(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        ?int $storeId = null
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
     * Returns the ids of the products of the grouped products.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $parentIds
     * @param bool $excludeInactive
     * @param bool $excludeOutOfStock
     * @param bool $maintainAssociation
     * @param bool $includeParents
     * @param int|null $storeId
     *
     * @phpstan-param array<int|string> $parentIds
     *
     * @return array
     * @phpstan-return array<int, int> child ids, or parent ids by child id if $maintainAssociation is true
     * @throws LocalizedException
     */
    public function getGroupedIds(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $excludeInactive = false,
        bool $excludeOutOfStock = false,
        bool $maintainAssociation = false,
        bool $includeParents = false,
        ?int $storeId = null
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
     * Returns a new collection of the configurable attributes of configurable products.
     *
     * Requires the optional module Magento_ConfigurableProduct, which is only loaded when this method is called.
     *
     * @return ConfigurableResource\Attribute\Collection
     * @throws RuntimeException if Magento_ConfigurableProduct is not installed
     */
    public function getConfigurableAttributeCollection(): ConfigurableResource\Attribute\Collection
    {
        return $this->instanceHelper->createInstance(ConfigurableResource\Attribute\Collection::class);
    }

    /**
     * Determines the SKUs of the products with the entity ids, cached.
     *
     * @param array $entityIds
     * @param bool $keepAssociation use the entity ids as keys instead of the keys of $entityIds
     *
     * @phpstan-param array<int|string> $entityIds
     *
     * @return array
     * @phpstan-return array<int|string, string>
     * @throws LocalizedException
     */
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

                $productCollection->getSelect()->reset(Select::COLUMNS);
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
                        $sku = $productData[ $entityId ][ 'sku' ] ?? null;

                        if (is_string($sku)) {
                            $skus[ $keepAssociation ? $entityId : $elementNumber ] = $sku;

                            $this->entitySkus[ $entityId ] = $sku;
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

    /**
     * Determines the entity ids of the products with the SKUs, cached.
     *
     * @param array $skus
     *
     * @phpstan-param array<int|string, string> $skus
     *
     * @return array
     * @phpstan-return array<int|string, int|string> entity ids, with the keys of $skus
     * @throws LocalizedException
     */
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

                $productCollection->getSelect()->reset(Select::COLUMNS);
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
                        $entityId = $productData[ $sku ][ 'entity_id' ] ?? null;

                        if (is_int($entityId) || is_string($entityId)) {
                            $entityIds[ $elementNumber ] = $entityId;

                            $this->entitySkus[ $entityId ] = $sku;
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

    /**
     * Returns the ids of the parent products of the products.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $childIds
     * @param bool $maintainAssociation return the parent ids by child id
     * @param bool $useSuperLink use the configurable links instead of all product relations
     * @param bool $includeChildren also search the child ids as parent ids
     *
     * @phpstan-param array<int|string> $childIds
     *
     * @return array
     * @phpstan-return array<int, int>
     */
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

        if ($maintainAssociation) {
            $parentIds = [];

            foreach ($this->databaseHelper->fetchAll($superLinkQuery, $dbAdapter) as $row) {
                if (is_array($row)) {
                    $parentIds[ $this->idValue($row[ $childColumnName ] ?? null) ] =
                        $this->idValue($row[ 'parent_id' ] ?? null);
                }
            }
        } else {
            $parentIds = array_map(
                'intval',
                array_keys($this->databaseHelper->fetchAssoc($superLinkQuery, $dbAdapter))
            );
        }

        $this->logging->debug(
            sprintf(
                'Found %d parent id(s)',
                count($parentIds)
            )
        );

        return $parentIds;
    }

    /**
     * Returns the ids of the websites of the product, cached.
     *
     * @param AdapterInterface $dbAdapter
     * @param int $productId
     *
     * @return array
     * @phpstan-return array<int|string>
     */
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

            $queryResult = $this->databaseHelper->fetchAssoc($websiteQuery, $dbAdapter);

            $this->websiteIds[ $productId ] = array_keys($queryResult);
        }

        return $this->websiteIds[ $productId ];
    }

    /**
     * Returns the configuration of all product types, with translated labels.
     *
     * @return array
     * @phpstan-return array<string, array<string, mixed>>
     */
    public function getTypes(): array
    {
        if ($this->types === null) {
            $productTypes = $this->config->getAll();

            foreach ($productTypes as $productTypeKey => $productTypeConfig) {
                $label = $productTypeConfig[ 'label' ] ?? $productTypeKey;

                $productTypes[ $productTypeKey ][ 'label' ] = __(is_scalar($label) ? (string)$label : $productTypeKey);
            }

            $this->types = $productTypes;
        }

        return $this->types;
    }

    /**
     * Returns the ids of the categories of the products, cached.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $productIds
     *
     * @phpstan-param array<int|string> $productIds
     *
     * @return array
     * @phpstan-return array<int|string, array<int, mixed>> category ids by product id
     */
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
     * Returns the price of the product including or excluding tax.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param float $price
     * @param bool|null $includingTax
     * @param AbstractAddress|null $shippingAddress
     * @param AbstractAddress|null $billingAddress
     * @param int|null $ctc
     * @param null|string|bool|int|Store $store
     * @param bool|null $priceIncludesTax
     * @param bool|null $roundPrice
     *
     * @return float
     */
    public function getTaxPrice(
        \Magento\Catalog\Model\Product $product,
        float $price,
        ?bool $includingTax = null,
        ?AbstractAddress $shippingAddress = null,
        ?AbstractAddress $billingAddress = null,
        ?int $ctc = null,
        Store|int|string|bool|null $store = null,
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
            $roundPrice ?? true
        );
    }

    /**
     * Returns the simple products of the configurable product, cached.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $onlyEnabled
     *
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

    /**
     * Returns the ids of the configurable attributes of the configurable product, cached.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return int[]
     * @throws LocalizedException
     */
    public function getUsedProductAttributeIds(\Magento\Catalog\Model\Product $product): array
    {
        if ($product->getTypeId() !== 'configurable') {
            return [];
        }

        if (! array_key_exists(
            $product->getId(),
            $this->usedProductAttributeIds
        )) {
            $this->usedProductAttributeIds[ $product->getId() ] = [];

            $typeInstance = $product->getTypeInstance();

            if ($typeInstance instanceof Configurable) {
                $attributes = $typeInstance->getConfigurableAttributes($product);

                foreach ($attributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();

                    try {
                        $attributeId = $this->variables->intValue($productAttribute->getAttributeId());

                        $this->usedProductAttributeIds[ $product->getId() ][] = $attributeId;
                    } catch (Exception $exception) {
                        $this->logging->debug($exception->getMessage());
                    }
                }
            }
        }

        return $this->usedProductAttributeIds[ $product->getId() ];
    }

    /**
     * Returns the option ids of the configurable attributes of the simple products of the configurable product.
     *
     * Simple products without a value for one of the attributes are left out.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $onlyEnabled
     *
     * @return array
     * @phpstan-return array<int, array<int, int>> option ids by attribute id by simple product id
     */
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
            $this->usedProductAttributes[ $cacheKey ] = [];

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
                        $this->logging->debug($exception->getMessage());
                    }
                }
            }
        }

        return $this->usedProductAttributes[ $cacheKey ];
    }

    /**
     * Returns the prices of the simple products of the configurable product, as the configurable product block does.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $onlyEnabled
     *
     * @return array
     * @phpstan-return array<int|string, array<string, mixed>> prices by simple product id
     */
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
                'tierPrices'   => $this->getTierPricesByProduct($usedProduct),
                'msrpPrice'    => [
                    'amount' => $this->localeFormat->getNumber(
                        $this->priceCurrency->convertAndRound(
                            $this->floatValue($usedProduct->getDataUsingMethod('msrp'))
                        )
                    ),
                ],
            ];
        }

        return $prices;
    }

    /**
     * Returns the SKUs of the simple products of the configurable product.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $onlyEnabled
     *
     * @return array
     * @phpstan-return array<int|string, string|null> SKUs by simple product id
     */
    public function getUsedProductsSkus(\Magento\Catalog\Model\Product $product, bool $onlyEnabled = true): array
    {
        $skus = [];

        foreach ($this->getUsedProducts(
            $product,
            $onlyEnabled
        ) as $usedProduct) {
            $skus[ $usedProduct->getId() ] = $usedProduct->getSku();
        }

        return $skus;
    }

    /**
     * Returns the tier prices of the product for the customer group.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $customerGroupId
     *
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

    /**
     * Returns the tier prices of the product, as the configurable product block does.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     * @phpstan-return array<int, array<string, mixed>>
     */
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

    /**
     * Converts a numeric value into an id, other values into 0.
     *
     * @param mixed $value
     *
     * @return int
     */
    private function idValue(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }

    /**
     * Converts a numeric value into a float, other values into 0.
     *
     * @param mixed $value
     *
     * @return float
     */
    private function floatValue(mixed $value): float
    {
        return is_numeric($value) ? (float)$value : 0.0;
    }
}
