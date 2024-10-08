<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Category extends AbstractHelper
{
    /** @var Arrays */
    protected $arrays;

    /** @var Variables */
    protected $variables;

    /** @var Stores */
    protected $storeHelper;

    /** @var Attribute */
    protected $attributeHelper;

    /** @var Database */
    protected $databaseHelper;

    /** @var Seo */
    protected $seoHelper;

    /** @var EntityType */
    protected $entityTypeHelper;

    /** @var LoggerInterface */
    protected $logging;

    /** @var CategoryFactory */
    protected $categoryFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\CategoryFactory */
    protected $categoryResourceFactory;

    /** @var CollectionFactory */
    protected $categoryCollectionFactory;

    /** @var array */
    private $parentEntityIds = [];

    /** @var array */
    private $pathEntityIds = [];

    /** @var array */
    private $childEntityIds = [];

    /** @var string[] */
    private $categoryNames = [];

    /** @var array */
    private $categoryPaths = [];

    /* @var string[] */
    private $categoryUrlPaths = [];

    /** @var array */
    private $subCategoryIds = [];

    public function __construct(
        Context $context,
        Arrays $arrays,
        Variables $variables,
        Stores $storeHelper,
        Attribute $attributeHelper,
        Database $databaseHelper,
        Seo $seoHelper,
        EntityType $entityTypeHelper,
        LoggerInterface $logging,
        CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\CategoryFactory $categoryResourceFactory,
        CollectionFactory $categoryCollectionFactory
    ) {
        parent::__construct($context);

        $this->arrays = $arrays;
        $this->variables = $variables;
        $this->storeHelper = $storeHelper;
        $this->attributeHelper = $attributeHelper;
        $this->databaseHelper = $databaseHelper;
        $this->seoHelper = $seoHelper;
        $this->entityTypeHelper = $entityTypeHelper;
        $this->logging = $logging;
        $this->categoryFactory = $categoryFactory;
        $this->categoryResourceFactory = $categoryResourceFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    public function newCategory(): \Magento\Catalog\Model\Category
    {
        return $this->categoryFactory->create();
    }

    public function loadCategory(int $categoryId, ?int $storeId = null): \Magento\Catalog\Model\Category
    {
        $category = $this->newCategory();

        if (! empty($storeId)) {
            $category->setStoreId($storeId);
        }

        $this->categoryResourceFactory->create()->load($category, $categoryId);

        return $category;
    }

    /**
     * @throws Exception
     */
    public function saveCategory(\Magento\Catalog\Model\Category $category): void
    {
        $this->categoryResourceFactory->create()->save($category);
    }

    public function getCategoryCollection(): Collection
    {
        return $this->categoryCollectionFactory->create();
    }

    /**
     * @throws Exception
     */
    public function getActiveCategoryIds(AdapterInterface $dbAdapter, int $storeId, bool $limitToStore = true): array
    {
        if ($limitToStore) {
            $store = $this->storeHelper->getStore($storeId);

            $activeCategoryIds =
                $this->getChildEntityIds($dbAdapter, [$store->getRootCategoryId()], false, true, false, true, $storeId);
        } else {
            $rootCategoryQuery =
                $this->databaseHelper->select(
                    $this->databaseHelper->getTableName('catalog_category_entity'),
                    ['entity_id']
                );
            $rootCategoryQuery->where('level = 1');

            $rootCategoryIds = $this->databaseHelper->fetchCol($rootCategoryQuery, $dbAdapter);

            $activeCategoryIds =
                $this->getChildEntityIds($dbAdapter, $rootCategoryIds, false, true, false, true, $storeId);
        }

        return $activeCategoryIds;
    }

    public function getParentEntityIds(
        AdapterInterface $dbAdapter,
        array $childIds,
        int $minLevel = 0,
        bool $useCache = false,
        bool $groupByChild = false
    ): array {
        $this->logging->debug(sprintf('Getting parent entity ids for child id(s): %s', implode(', ', $childIds)));

        $result = [];

        $loadChildIds = [];

        foreach ($childIds as $childId) {
            $key = sprintf('%s_%s', $childId, $minLevel);

            if ($useCache && array_key_exists($key, $this->parentEntityIds)) {
                $result[ $childId ] = $this->parentEntityIds[ $key ];
            } else {
                $loadChildIds[] = $childId;
            }
        }

        if (! empty($loadChildIds)) {
            $this->logging->debug(sprintf(
                'Searching parent entity ids for child id(s): %s',
                implode(', ', $loadChildIds)
            ));

            $tableName = $this->databaseHelper->getTableName('catalog_category_entity');

            $parentQuery = $dbAdapter->select()->from([$tableName], ['entity_id', 'parent_id']);

            $parentQuery->where(
                $dbAdapter->prepareSqlCondition('entity_id', ['in' => $loadChildIds]),
                null,
                Select::TYPE_CONDITION
            );

            $parentQuery->where(
                $dbAdapter->prepareSqlCondition('level', ['gteq' => $minLevel]),
                null,
                Select::TYPE_CONDITION
            );

            $queryResult = $this->databaseHelper->fetchPairs($parentQuery, $dbAdapter);

            $this->logging->debug(sprintf('Found %d parent entity id(s)', count($queryResult)));

            foreach ($queryResult as $entityId => $parentId) {
                $result[ $entityId ] = $parentId;

                $key = sprintf('%s_%s', $entityId, $minLevel);

                $this->parentEntityIds[ $key ] = $parentId;
            }
        }

        if (! $groupByChild) {
            $result = array_values(array_unique($result));
        }

        return $result;
    }

    public function getPathEntityIds(AdapterInterface $dbAdapter, array $childIds, bool $useCache = false): array
    {
        $this->logging->debug(sprintf('Getting path entity ids for child id(s): %s', implode(', ', $childIds)));

        $result = [];

        $loadChildIds = [];

        foreach ($childIds as $childId) {
            if ($useCache && array_key_exists($childId, $this->pathEntityIds)) {
                $result[ $childId ] = $this->pathEntityIds[ $childId ];
            } else {
                $loadChildIds[] = $childId;
            }
        }

        if (! empty($loadChildIds)) {
            $this->logging->debug(sprintf(
                'Searching path entity ids for child id(s): %s',
                implode(', ', $loadChildIds)
            ));

            $tableName = $this->databaseHelper->getTableName('catalog_category_entity');

            $pathQuery = $dbAdapter->select()->from([$tableName], ['entity_id', 'path']);

            $pathQuery->where(
                $dbAdapter->prepareSqlCondition('entity_id', ['in' => $loadChildIds]),
                null,
                Select::TYPE_CONDITION
            );

            $queryResult = $this->databaseHelper->fetchPairs($pathQuery, $dbAdapter);

            $this->logging->debug(sprintf('Found %d path entity id(s)', count($queryResult)));

            foreach ($queryResult as $entityId => $path) {
                $pathIds = array_diff(explode('/', $path), [$entityId]);

                $result[ $entityId ] = $pathIds;

                $this->pathEntityIds[ $entityId ] = $pathIds;
            }
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getChildEntityIds(
        AdapterInterface $dbAdapter,
        array $parentIds,
        bool $orderByPosition = false,
        bool $recursive = false,
        bool $includeInactiveCategories = true,
        bool $useCache = false,
        int $storeId = null
    ): array {
        $this->logging->debug(sprintf('Searching child ids for child id(s): %s', implode(', ', $parentIds)));

        $key = md5(json_encode([$parentIds, $orderByPosition, $recursive, $includeInactiveCategories]));

        if (! array_key_exists($key, $this->childEntityIds) || ! $useCache) {
            $tableName = $this->databaseHelper->getTableName('catalog_category_entity');

            $childQuery = $dbAdapter->select()->from(['category' => $tableName], ['entity_id']);

            $childQuery->where(
                $dbAdapter->prepareSqlCondition('parent_id', ['in' => $parentIds]),
                null,
                Select::TYPE_CONDITION
            );

            if (! $includeInactiveCategories) {
                $isActiveAttribute =
                    $this->attributeHelper->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'is_active');

                if ($storeId === null) {
                    $childQuery->join(
                        ['is_active' => $isActiveAttribute->getBackend()->getTable()],
                        $dbAdapter->quoteInto(
                            sprintf(
                                '%s = %s AND %s = ?',
                                $dbAdapter->quoteIdentifier('is_active.entity_id'),
                                $dbAdapter->quoteIdentifier('category.entity_id'),
                                $dbAdapter->quoteIdentifier('is_active.attribute_id')
                            ),
                            $isActiveAttribute->getAttributeId()
                        ),
                        []
                    );

                    $childQuery->where($dbAdapter->prepareSqlCondition(
                        $dbAdapter->quoteIdentifier('is_active.value'),
                        ['eq' => 1]
                    ), null, Select::TYPE_CONDITION);
                } else {
                    $childQuery->joinLeft(
                        ['is_active_store' => $isActiveAttribute->getBackend()->getTable()],
                        sprintf(
                            '%s = %s AND %s = %d AND %s = %d',
                            $dbAdapter->quoteIdentifier('is_active_store.entity_id'),
                            $dbAdapter->quoteIdentifier('category.entity_id'),
                            $dbAdapter->quoteIdentifier('is_active_store.attribute_id'),
                            $isActiveAttribute->getAttributeId(),
                            $dbAdapter->quoteIdentifier('is_active_store.store_id'),
                            $storeId
                        ),
                        []
                    );

                    $childQuery->joinLeft(
                        ['is_active_admin' => $isActiveAttribute->getBackend()->getTable()],
                        sprintf(
                            '%s = %s AND %s = %d AND %s = %d',
                            $dbAdapter->quoteIdentifier('is_active_admin.entity_id'),
                            $dbAdapter->quoteIdentifier('category.entity_id'),
                            $dbAdapter->quoteIdentifier('is_active_admin.attribute_id'),
                            $isActiveAttribute->getAttributeId(),
                            $dbAdapter->quoteIdentifier('is_active_admin.store_id'),
                            0
                        ),
                        []
                    );

                    $childQuery->where('is_active_store.value = 1 OR (is_active_store.value IS NULL and is_active_admin.value = 1)');
                }
            }

            if ($orderByPosition) {
                $childQuery->order('position ASC');
            }

            $queryResult = $this->databaseHelper->fetchAssoc($childQuery, $dbAdapter);

            $childIds = array_keys($queryResult);

            $this->logging->debug(sprintf('Found %d child id(s)', count($childIds)));

            if ($recursive && ! $this->variables->isEmpty($childIds)) {
                $childIds = array_merge(
                    $childIds,
                    $this->getChildEntityIds(
                        $dbAdapter,
                        $childIds,
                        $orderByPosition,
                        $recursive,
                        $includeInactiveCategories,
                        $useCache,
                        $storeId
                    )
                );
            }

            $this->childEntityIds[ $key ] = $childIds;
        }

        return $this->childEntityIds[ $key ];
    }

    /**
     * @throws LocalizedException
     * @throws Exception
     */
    public function getEntityIds(
        AdapterInterface $dbAdapter,
        array $productEntityIds,
        bool $includeInactiveCategories = true,
        bool $order = false,
        bool $maintainAssociation = false
    ): array {
        $categoryProductTableName = $this->databaseHelper->getTableName('catalog_category_product');

        $categoryQuery = $dbAdapter->select()->from([$categoryProductTableName], ['category_id', 'product_id']);

        if (! $includeInactiveCategories || $order) {
            $categoryTableName = $this->databaseHelper->getTableName('catalog_category_product');

            $categoryQuery->join(
                ['category' => $categoryTableName],
                sprintf(
                    '%s = %s',
                    $dbAdapter->quoteIdentifier('category.entity_id'),
                    $dbAdapter->quoteIdentifier(sprintf('%s.%s', $categoryProductTableName, 'category_id'))
                ),
                []
            );
        }

        if (! $includeInactiveCategories) {
            $isActiveAttribute =
                $this->attributeHelper->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'is_active');

            $categoryQuery->join(
                ['is_active' => $isActiveAttribute->getBackend()->getTable()],
                $dbAdapter->quoteInto(sprintf(
                    '%s = %s AND %s = ?',
                    $dbAdapter->quoteIdentifier('is_active.entity_id'),
                    $dbAdapter->quoteIdentifier('category.entity_id'),
                    $dbAdapter->quoteIdentifier('is_active.attribute_id')
                ), $isActiveAttribute->getAttributeId()),
                []
            );

            $categoryQuery->where($dbAdapter->prepareSqlCondition(
                $dbAdapter->quoteIdentifier('is_active.value'),
                ['eq' => 1]
            ), null, Select::TYPE_CONDITION);
        }

        $categoryQuery->where($dbAdapter->prepareSqlCondition(
            sprintf('%s.%s', $categoryProductTableName, 'product_id'),
            ['in' => $productEntityIds]
        ), null, Select::TYPE_CONDITION);

        if ($order) {
            $categoryQuery->order('category.level ASC');
            $categoryQuery->order('category.position ASC');
        }

        $queryResult = $this->databaseHelper->fetchAll($categoryQuery, $dbAdapter);

        $result = [];

        foreach ($queryResult as $queryRow) {
            if ($maintainAssociation) {
                $result[ $this->arrays->getValue($queryRow, 'product_id') ][] =
                    $this->arrays->getValue($queryRow, 'category_id');
            } else {
                $result[] = $this->arrays->getValue($queryRow, 'category_id');
            }
        }

        $this->logging->debug(sprintf('Found %d entity id(s)', count($result)));

        return $result;
    }

    public function getCategoryName(AdapterInterface $dbAdapter, int $categoryId, int $storeId): ?string
    {
        $key = sprintf('%d_%d', $categoryId, $storeId);

        if (! array_key_exists($key, $this->categoryNames)) {
            $attributeId =
                $this->attributeHelper->getAttributeId($dbAdapter, \Magento\Catalog\Model\Category::ENTITY, 'name');

            $tableName = $this->databaseHelper->getTableName('catalog_category_entity');

            $categoryStoreQuery = $dbAdapter->select()->from(['category' => $tableName], []);

            $categoryStoreQuery->joinLeft(
                ['category_varchar' => sprintf('%s_varchar', $tableName)],
                sprintf(
                    '%s AND %s AND %s',
                    'category_varchar.entity_id = category.entity_id',
                    $dbAdapter->quoteInto('category_varchar.attribute_id = ?', $attributeId),
                    sprintf('category_varchar.store_id = %d', $storeId)
                ),
                ['category_name' => 'value']
            );

            $categoryStoreQuery->where(sprintf('category.entity_id = %d', $categoryId), null, Select::TYPE_CONDITION);

            $categoryName = $this->databaseHelper->fetchOne($categoryStoreQuery, $dbAdapter);

            if ($this->variables->isEmpty($categoryName)) {
                $categoryDefaultQuery = $dbAdapter->select()->from(['category' => $tableName], []);

                $categoryDefaultQuery->joinLeft(
                    ['category_varchar' => sprintf('%s_varchar', $tableName)],
                    sprintf(
                        '%s AND %s AND %s',
                        'category_varchar.entity_id = category.entity_id',
                        $dbAdapter->quoteInto('category_varchar.attribute_id = ?', $attributeId),
                        sprintf('category_varchar.store_id = %d', 0)
                    ),
                    ['category_name' => 'value']
                );

                $categoryDefaultQuery->where(
                    sprintf('category.entity_id = %d', $categoryId),
                    null,
                    Select::TYPE_CONDITION
                );

                $categoryName = $this->databaseHelper->fetchOne($categoryDefaultQuery, $dbAdapter);
            }

            $this->categoryNames[ $key ] = $categoryName;
        }

        return $this->categoryNames[ $key ];
    }

    public function getCategoryPath(AdapterInterface $dbAdapter, int $categoryId): ?string
    {
        if (array_key_exists($categoryId, $this->categoryPaths)) {
            return $this->categoryPaths[ $categoryId ];
        }

        $tableName = $this->databaseHelper->getTableName('catalog_category_entity');

        $categoryQuery = $dbAdapter->select()->from(['category' => $tableName], ['path']);

        $categoryQuery->where(
            $dbAdapter->prepareSqlCondition('category.entity_id', ['eq' => $categoryId]),
            null,
            Select::TYPE_CONDITION
        );

        $categoryPath = $this->databaseHelper->fetchOne($categoryQuery, $dbAdapter);

        if (! empty($categoryPath)) {
            $this->categoryPaths[ $categoryId ] = $categoryPath;

            return $this->categoryPaths[ $categoryId ];
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getCategoryUrlPath(AdapterInterface $dbAdapter, int $categoryId, int $storeId): ?string
    {
        $key = sprintf('%d_%d', $categoryId, $storeId);

        if (array_key_exists($key, $this->categoryUrlPaths)) {
            return $this->categoryUrlPaths[ $key ];
        }

        $urlPathAttribute = $this->attributeHelper->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'url_path');

        $tableName = $this->databaseHelper->getTableName('catalog_category_entity');

        $categoryQuery = $dbAdapter->select()->from(['category' => $tableName], []);

        $categoryQuery->joinLeft(
            ['store_value' => $urlPathAttribute->getBackendTable()],
            sprintf(
                '%s AND %s AND %s',
                'store_value.entity_id = category.entity_id',
                $dbAdapter->quoteInto('store_value.attribute_id = ?', $urlPathAttribute->getAttributeId()),
                $dbAdapter->quoteInto('store_value.store_id = ?', $storeId)
            ),
            ['store_value_value' => 'value']
        );

        $categoryQuery->joinLeft(
            ['admin_value' => $urlPathAttribute->getBackendTable()],
            sprintf(
                '%s AND %s AND %s',
                'admin_value.entity_id = category.entity_id',
                $dbAdapter->quoteInto('admin_value.attribute_id = ?', $urlPathAttribute->getAttributeId()),
                $dbAdapter->quoteInto('admin_value.store_id = ?', 0)
            ),
            ['admin_value_value' => 'value']
        );

        $categoryQuery->where(
            $dbAdapter->prepareSqlCondition('category.entity_id', ['eq' => $categoryId]),
            null,
            Select::TYPE_CONDITION
        );

        $categoryUrlPaths = $this->databaseHelper->fetchRow($categoryQuery, $dbAdapter);

        if (count($categoryUrlPaths)) {
            $categoryUrlPath = $this->arrays->getValue($categoryUrlPaths, 'store_value_value');

            if ($categoryUrlPath === null) {
                $categoryUrlPath = $this->arrays->getValue($categoryUrlPaths, 'admin_value_value');
            }

            if (! empty($categoryUrlPath)) {
                $this->categoryUrlPaths[ $key ] = $categoryUrlPath;

                return $this->categoryUrlPaths[ $key ];
            }
        }

        $urlKeyAttribute = $this->attributeHelper->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'url_key');

        $categoryQuery = $dbAdapter->select()->from(['category' => $tableName], []);

        $categoryQuery->joinLeft(
            ['store_value' => $urlKeyAttribute->getBackendTable()],
            sprintf(
                '%s AND %s AND %s',
                'store_value.entity_id = category.entity_id',
                $dbAdapter->quoteInto('store_value.attribute_id = ?', $urlKeyAttribute->getAttributeId()),
                $dbAdapter->quoteInto('store_value.store_id = ?', $storeId)
            ),
            ['store_value_value' => 'value']
        );

        $categoryQuery->joinLeft(
            ['admin_value' => $urlKeyAttribute->getBackendTable()],
            sprintf(
                '%s AND %s AND %s',
                'admin_value.entity_id = category.entity_id',
                $dbAdapter->quoteInto('admin_value.attribute_id = ?', $urlKeyAttribute->getAttributeId()),
                $dbAdapter->quoteInto('admin_value.store_id = ?', 0)
            ),
            ['admin_value_value' => 'value']
        );

        $categoryQuery->where(
            $dbAdapter->prepareSqlCondition('category.entity_id', ['eq' => $categoryId]),
            null,
            Select::TYPE_CONDITION
        );

        $categoryUrlPaths = $this->databaseHelper->fetchRow($categoryQuery, $dbAdapter);

        if (count($categoryUrlPaths)) {
            $categoryUrlPath = $this->arrays->getValue($categoryUrlPaths, 'store_value_value');

            if ($categoryUrlPath === null) {
                $categoryUrlPath = $this->arrays->getValue($categoryUrlPaths, 'admin_value_value');
            }

            if (! empty($categoryUrlPath)) {
                $category = $this->newCategory();

                $this->categoryUrlPaths[ $key ] =
                    $this->seoHelper->addSeoSuffix($category->formatUrlKey($categoryUrlPath));

                return $this->categoryUrlPaths[ $key ];
            }
        }

        return null;
    }

    /**
     * @throws LocalizedException
     */
    public function getDefaultAttributeSetId(): ?int
    {
        $categoryEntityType = $this->entityTypeHelper->getCategoryEntityType();

        return empty($categoryEntityType) ? null : (int)$categoryEntityType->getDefaultAttributeSetId();
    }

    public function getSubCategoryIds(AdapterInterface $dbAdapter, int $parentCategoryId): array
    {
        if (array_key_exists($parentCategoryId, $this->subCategoryIds)) {
            return $this->subCategoryIds[ $parentCategoryId ];
        }

        $tableName = $this->databaseHelper->getTableName('catalog_category_entity');

        $categoryQuery = $dbAdapter->select()->from(['category' => $tableName], ['entity_id']);

        $categoryQuery->where(
            $dbAdapter->prepareSqlCondition('category.path', ['like' => "%/$parentCategoryId/%"]),
            null,
            Select::TYPE_CONDITION
        );

        $subCategoryIds = $this->databaseHelper->fetchCol($categoryQuery, $dbAdapter);

        if (! empty($subCategoryIds)) {
            $this->subCategoryIds[ $parentCategoryId ] = $subCategoryIds;

            return $this->subCategoryIds[ $parentCategoryId ];
        }

        return [];
    }
}
