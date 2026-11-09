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
use Magento\Catalog\Api\CategoryAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Eav\Model\ConfigFactory;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory as AttributeGroupCollectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

class Attribute
{
    /** @var Arrays */
    protected Arrays $arrays;

    /** @var Variables */
    protected Variables $variables;

    /** @var Database */
    protected Database $databaseHelper;

    /** @var EntityType */
    protected EntityType $entityTypeHelper;

    /** @var LoggerInterface */
    protected LoggerInterface $logging;

    /** @var AttributeFactory */
    protected AttributeFactory $attributeFactory;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory */
    protected \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory $attributeResourceFactory;

    /** @var CollectionFactory */
    protected CollectionFactory $attributeCollectionFactory;

    /** @var SetFactory */
    protected SetFactory $attributeSetFactory;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\SetFactory */
    protected \Magento\Eav\Model\ResourceModel\Entity\Attribute\SetFactory $attributeSetResourceFactory;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory */
    protected \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollectionFactory;

    /** @var AttributeGroupCollectionFactory */
    protected AttributeGroupCollectionFactory $attributeGroupCollectionFactory;

    /** @var ProductAttributeRepositoryInterface */
    protected ProductAttributeRepositoryInterface $productAttributeRepository;

    /** @var CategoryAttributeRepositoryInterface */
    protected CategoryAttributeRepositoryInterface $categoryAttributeRepository;

    /** @var ProductAttributeCollectionFactory */
    protected ProductAttributeCollectionFactory $productAttributeCollectionFactory;

    /** @var ConfigFactory */
    protected ConfigFactory $configFactory;

    /** @var array<string, Entity\Attribute|null> */
    private array $attributes = [];

    /** @var int[] */
    private array $attributeIds = [];

    /** @var array<string, mixed> */
    private array $attributeValues = [];

    /** @var array<string, array<int|string, mixed>> */
    private array $attributeOptionValues = [];

    /** @var array<string, int|null> */
    private array $attributeOptionIds = [];

    /** @var array<string, \Magento\Catalog\Model\ResourceModel\Eav\Attribute> */
    private array $categoryAttributes = [];

    /** @var array<string, \Magento\Catalog\Model\ResourceModel\Eav\Attribute> */
    private array $productAttributes = [];

    /** @var Set[] */
    private array $attributeSetsById = [];

    /** @var Set[] */
    private array $attributeSetsByName = [];

    /**
     * @param Arrays $arrays
     * @param Variables $variables
     * @param Database $databaseHelper
     * @param EntityType $entityTypeHelper
     * @param LoggerInterface $logging
     * @param AttributeFactory $attributeFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory $attributeResourceFactory
     * @param CollectionFactory $attributeCollectionFactory
     * @param SetFactory $attributeSetFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\SetFactory $attributeSetResourceFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollectionFactory
     * @param AttributeGroupCollectionFactory $attributeGroupCollectionFactory
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param CategoryAttributeRepositoryInterface $categoryAttributeRepository
     * @param ProductAttributeCollectionFactory $productAttributeCollectionFactory
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        Arrays $arrays,
        Variables $variables,
        Database $databaseHelper,
        EntityType $entityTypeHelper,
        LoggerInterface $logging,
        AttributeFactory $attributeFactory,
        \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory $attributeResourceFactory,
        CollectionFactory $attributeCollectionFactory,
        SetFactory $attributeSetFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\SetFactory $attributeSetResourceFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollectionFactory,
        AttributeGroupCollectionFactory $attributeGroupCollectionFactory,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        CategoryAttributeRepositoryInterface $categoryAttributeRepository,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        ConfigFactory $configFactory
    ) {
        $this->arrays = $arrays;
        $this->variables = $variables;
        $this->databaseHelper = $databaseHelper;
        $this->entityTypeHelper = $entityTypeHelper;
        $this->logging = $logging;
        $this->attributeFactory = $attributeFactory;
        $this->attributeResourceFactory = $attributeResourceFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeSetResourceFactory = $attributeSetResourceFactory;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
        $this->attributeGroupCollectionFactory = $attributeGroupCollectionFactory;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->categoryAttributeRepository = $categoryAttributeRepository;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->configFactory = $configFactory;
    }

    /**
     * Returns the attribute collection.
     *
     * @return Collection
     */
    public function getAttributeCollection(): Collection
    {
        return $this->attributeCollectionFactory->create();
    }

    /**
     * Creates a new attribute.
     *
     * @return Entity\Attribute
     */
    public function newAttribute(): Entity\Attribute
    {
        return $this->attributeFactory->create();
    }

    /**
     * Loads the attribute.
     *
     * @param int $attributeId
     *
     * @return Entity\Attribute
     */
    public function loadAttribute(int $attributeId): Entity\Attribute
    {
        $attribute = $this->newAttribute();

        $this->attributeResourceFactory->create()->load(
            $attribute,
            $attributeId,
            'attribute_id'
        );

        return $attribute;
    }

    /**
     * Deletes the attribute.
     *
     * @param Entity\Attribute $attribute
     *
     * @return void
     * @throws Exception
     */
    public function deleteAttribute(Entity\Attribute $attribute): void
    {
        $this->attributeResourceFactory->create()->delete($attribute);
    }

    /**
     * Returns the attribute set collection.
     *
     * @return \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection
     */
    public function getAttributeSetCollection(): \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection
    {
        return $this->attributeSetCollectionFactory->create();
    }

    /**
     * Creates a new attribute set.
     *
     * @return Set
     */
    public function newAttributeSet(): Set
    {
        return $this->attributeSetFactory->create();
    }

    /**
     * Loads the attribute set.
     *
     * @param int $attributeSetId
     *
     * @return Set
     */
    public function loadAttributeSet(int $attributeSetId): Set
    {
        $attributeSet = $this->newAttributeSet();

        $this->attributeSetResourceFactory->create()->load(
            $attributeSet,
            $attributeSetId,
            'attribute_set_id'
        );

        return $attributeSet;
    }

    /**
     * Deletes the attribute set.
     *
     * @param Set $attributeSet
     *
     * @return void
     * @throws Exception
     */
    public function deleteAttributeSet(Set $attributeSet): void
    {
        $this->attributeSetResourceFactory->create()->delete($attributeSet);
    }

    /**
     * Returns the attribute group collection.
     *
     * @return \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\Collection
     */
    public function getAttributeGroupCollection(): \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\Collection
    {
        return $this->attributeGroupCollectionFactory->create();
    }

    /**
     * Loads the catalog attribute of a product or category attribute, with the catalog specific settings.
     *
     * @param Entity\Attribute $attribute
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute|null
     * @throws NoSuchEntityException
     */
    public function loadCatalogEavAttribute(
        Entity\Attribute $attribute
    ): ?\Magento\Catalog\Model\ResourceModel\Eav\Attribute {
        $entityTypeCode = $attribute->getEntityType()->getEntityTypeCode();

        $catalogEavAttribute = null;

        if ($entityTypeCode == 'catalog_product') {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $catalogEavAttribute */
            $catalogEavAttribute = $this->productAttributeRepository->get($attribute->getAttributeCode());
        } else {
            if ($entityTypeCode == 'catalog_category') {
                /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $catalogEavAttribute */
                $catalogEavAttribute = $this->categoryAttributeRepository->get($attribute->getAttributeCode());
            }
        }

        return $catalogEavAttribute;
    }

    /**
     * Returns the product attribute collection.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getProductAttributeCollection(): \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
    {
        return $this->productAttributeCollectionFactory->create();
    }

    /**
     * Returns the attribute of the entity type with the code from the EAV configuration.
     *
     * @param string $entityTypeCode
     * @param string $attributeCode
     *
     * @return Entity\Attribute
     * @throws LocalizedException
     * @throws NoSuchEntityException if the attribute does not exist
     */
    public function getAttribute(string $entityTypeCode, string $attributeCode): Entity\Attribute
    {
        $key = sprintf(
            '%s_%s',
            $entityTypeCode,
            $attributeCode
        );

        if (! array_key_exists(
            $key,
            $this->attributes
        )) {
            $attribute = $this->configFactory->create()->getAttribute(
                $entityTypeCode,
                $attributeCode
            );

            $this->attributes[ $key ] =
                $attribute instanceof Entity\Attribute && $attribute->getId() ? $attribute : null;
        }

        $attribute = $this->attributes[ $key ];

        if ($attribute === null) {
            throw new NoSuchEntityException(
                __(
                    'Could not load attribute with entity: %1 and code: %2',
                    $entityTypeCode,
                    $attributeCode
                )
            );
        }

        return $attribute;
    }

    /**
     * Returns the id of the attribute of the entity type with the code, read from the database.
     *
     * @param AdapterInterface $dbAdapter
     * @param string $entityTypeCode
     * @param string $attributeCode
     *
     * @return int|null
     */
    public function getAttributeId(AdapterInterface $dbAdapter, string $entityTypeCode, string $attributeCode): ?int
    {
        $key = $entityTypeCode . '_' . $attributeCode;

        if (array_key_exists(
            $key,
            $this->attributeIds
        )) {
            return $this->attributeIds[ $key ];
        }

        $eavAttributeTableName = $this->databaseHelper->getTableName('eav_attribute');
        $entityTypeTableName = $this->databaseHelper->getTableName('eav_entity_type');

        $attributeQuery = $dbAdapter->select()->from(
            ['eav_attribute' => $eavAttributeTableName],
            ['attribute_id']
        );

        $attributeQuery->joinLeft(
            ['eav_entity_type' => $entityTypeTableName],
            'eav_entity_type.entity_type_id = eav_attribute.entity_type_id',
            'entity_type_code'
        );

        $attributeQuery->where(
            $dbAdapter->prepareSqlCondition(
                'eav_attribute.attribute_code',
                ['eq' => $attributeCode]
            ),
            null,
            Select::TYPE_CONDITION
        );
        $attributeQuery->where(
            $dbAdapter->prepareSqlCondition(
                'eav_entity_type.entity_type_code',
                ['eq' => $entityTypeCode]
            ),
            null,
            Select::TYPE_CONDITION
        );

        $attributeId = $this->databaseHelper->fetchOne(
            $attributeQuery,
            $dbAdapter
        );

        if (! empty($attributeId)) {
            $this->attributeIds[ $key ] = (int)$attributeId;

            return $this->attributeIds[ $key ];
        }

        return null;
    }

    /**
     * Returns the value of the attribute of the entity in the store.
     *
     * @param AdapterInterface $dbAdapter
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param int $entityId
     * @param int $storeId
     * @param bool $useOptionValue
     * @param bool $strToLower
     *
     * @return mixed the label instead of the option id if $useOptionValue is true
     * @throws LocalizedException
     */
    public function getAttributeValue(
        AdapterInterface $dbAdapter,
        string $entityTypeCode,
        string $attributeCode,
        int $entityId,
        int $storeId,
        bool $useOptionValue = true,
        bool $strToLower = false
    ): mixed {
        $attributeValues = $this->getAttributeValues(
            $dbAdapter,
            $entityTypeCode,
            $attributeCode,
            [$entityId],
            $storeId,
            $useOptionValue,
            $strToLower
        );

        return $attributeValues[ $entityId ] ?? null;
    }

    /**
     * Returns the values of the attribute of the entities in the store.
     *
     * The values are cached; values of the store fall back to the default (admin) store.
     *
     * @param AdapterInterface $dbAdapter
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param array $entityIds
     * @param int $storeId
     * @param bool $useOptionValue return the labels instead of the option ids
     * @param bool $strToLower
     *
     * @phpstan-param array<int|string> $entityIds
     *
     * @return array
     * @phpstan-return array<int|string, mixed> values by entity id
     * @throws LocalizedException
     */
    public function getAttributeValues(
        AdapterInterface $dbAdapter,
        string $entityTypeCode,
        string $attributeCode,
        array $entityIds,
        int $storeId,
        bool $useOptionValue = true,
        bool $strToLower = false
    ): array {
        $attributeValues = [];

        foreach ($entityIds as $index => $entityId) {
            $key = $this->getAttributeValueKey($entityId, $entityTypeCode, $attributeCode, $storeId, $useOptionValue);

            if (array_key_exists(
                $key,
                $this->attributeValues
            )) {
                $attributeValues[ $entityId ] = $this->attributeValues[ $key ];

                unset($entityIds[ $index ]);
            }
        }

        if (count($entityIds) > 0) {
            $entityType = $this->entityTypeHelper->getEntityType($entityTypeCode);

            if ($entityType === null) {
                throw new NoSuchEntityException(__('Could not load entity type with code: %1', $entityTypeCode));
            }

            $entityIdField = $entityType->getEntityIdField();

            if (empty($entityIdField)) {
                $entityIdField = Entity::DEFAULT_ENTITY_ID_FIELD;
            }

            if (strcasecmp($entityIdField, $attributeCode) === 0 ||
                ($entityTypeCode === Product::ENTITY && $attributeCode === 'type_id')) {
                $attributeValues += $this->loadStaticAttributeValues(
                    $dbAdapter,
                    $this->entityTypeHelper->getEntityTypeTableByEntityType($entityType),
                    $entityIdField,
                    $entityTypeCode,
                    $attributeCode,
                    $entityIds,
                    $storeId,
                    $useOptionValue
                );
            } else {
                $attribute = $this->getAttribute(
                    $entityTypeCode,
                    $attributeCode
                );

                $attributeValues += $attribute->isStatic() ? $this->loadStaticAttributeValues(
                    $dbAdapter,
                    $attribute->getBackend()->getTable(),
                    $entityIdField,
                    $entityTypeCode,
                    $attributeCode,
                    $entityIds,
                    $storeId,
                    $useOptionValue
                ) : $this->loadEavAttributeValues(
                    $dbAdapter,
                    $attribute,
                    $entityTypeCode,
                    $entityIds,
                    $storeId,
                    $useOptionValue
                );
            }
        }

        return $strToLower ? $this->lowerValues($attributeValues) : $attributeValues;
    }

    /**
     * Loads the values of a static attribute (a column of the entity table) and adds them to the cache.
     *
     * @param AdapterInterface $dbAdapter
     * @param string $attributeTable
     * @param string $entityIdField
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param array $entityIds
     * @param int $storeId
     * @param bool $useOptionValue
     *
     * @phpstan-param array<int|string> $entityIds
     *
     * @return array
     * @phpstan-return array<int|string, mixed> values by entity id
     */
    private function loadStaticAttributeValues(
        AdapterInterface $dbAdapter,
        string $attributeTable,
        string $entityIdField,
        string $entityTypeCode,
        string $attributeCode,
        array $entityIds,
        int $storeId,
        bool $useOptionValue
    ): array {
        $valueQuery = $dbAdapter->select()->from(
            $attributeTable,
            [
                $entityIdField,
                $attributeCode,
            ]
        );

        $valueQuery->where(
            $dbAdapter->prepareSqlCondition(
                $entityIdField,
                ['in' => $entityIds]
            ),
            null,
            Select::TYPE_CONDITION
        );

        $attributeValues = [];

        foreach ($this->databaseHelper->fetchAssoc($valueQuery, $dbAdapter) as $row) {
            if (! is_array($row) || ! array_key_exists($entityIdField, $row) ||
                ! array_key_exists($attributeCode, $row)) {
                continue;
            }

            $entityId = $row[ $entityIdField ];

            if (! is_int($entityId) && ! is_string($entityId)) {
                continue;
            }

            $key = $this->getAttributeValueKey($entityId, $entityTypeCode, $attributeCode, $storeId, $useOptionValue);

            $this->attributeValues[ $key ] = $row[ $attributeCode ];

            $attributeValues[ $entityId ] = $row[ $attributeCode ];
        }

        return $attributeValues;
    }

    /**
     * Loads the values of an EAV attribute (a value table) and adds them to the cache.
     *
     * @param AdapterInterface $dbAdapter
     * @param Entity\Attribute $attribute
     * @param string $entityTypeCode
     * @param array $entityIds
     * @param int $storeId
     * @param bool $useOptionValue
     *
     * @phpstan-param array<int|string> $entityIds
     *
     * @return array
     * @phpstan-return array<int|string, mixed> values by entity id
     * @throws LocalizedException
     */
    private function loadEavAttributeValues(
        AdapterInterface $dbAdapter,
        Entity\Attribute $attribute,
        string $entityTypeCode,
        array $entityIds,
        int $storeId,
        bool $useOptionValue
    ): array {
        $isCatalog = $entityTypeCode === Product::ENTITY || $entityTypeCode === Category::ENTITY;

        $columns = [
            'entity_id',
            'value',
        ];

        if ($isCatalog) {
            $columns[] = 'store_id';
        }

        $valueQuery = $dbAdapter->select()->from(
            $attribute->getBackend()->getTable(),
            $columns
        );

        $valueQuery->where(
            $dbAdapter->prepareSqlCondition(
                'entity_id',
                ['in' => $entityIds]
            ),
            null,
            Select::TYPE_CONDITION
        );
        $valueQuery->where(
            $dbAdapter->prepareSqlCondition(
                'attribute_id',
                ['eq' => $attribute->getAttributeId()]
            ),
            null,
            Select::TYPE_CONDITION
        );

        if ($isCatalog) {
            $valueQuery->where(
                $dbAdapter->prepareSqlCondition(
                    'store_id',
                    [
                        'in' => [
                            0,
                            $storeId,
                        ],
                    ]
                ),
                null,
                Select::TYPE_CONDITION
            );
        }

        $queryResult = $this->databaseHelper->fetchAssoc(
            $valueQuery,
            $dbAdapter
        );

        if (empty($queryResult)) {
            return [];
        }

        /** @var array<int|string, array<int|string, mixed>> $entityValues values by entity id and store id */
        $entityValues = [];

        foreach ($queryResult as $row) {
            if (is_array($row) && array_key_exists('entity_id', $row) && array_key_exists('value', $row)) {
                $rowStoreId = array_key_exists('store_id', $row) ? $row[ 'store_id' ] : $storeId;

                $entityValues[ $this->stringValue($row[ 'entity_id' ]) ][ $this->stringValue($rowStoreId) ] =
                    $row[ 'value' ];
            }
        }

        $attributeValues = [];

        foreach ($entityIds as $entityId) {
            $values = $entityValues[ (string)$entityId ] ?? [];

            $attributeValue = array_key_exists((string)$storeId, $values) ? $values[ (string)$storeId ] :
                ($values[ '0' ] ?? null);

            if ($attributeValue !== null && $useOptionValue === true && $attribute->usesSource()) {
                $attributeValue = $this->getOptionLabel($attribute, $entityTypeCode, $storeId, $attributeValue);
            }

            $key = $this->getAttributeValueKey(
                $entityId,
                $entityTypeCode,
                (string)$attribute->getAttributeCode(),
                $storeId,
                $useOptionValue
            );

            $this->attributeValues[ $key ] = $attributeValue;

            $attributeValues[ $entityId ] = $attributeValue;
        }

        return $attributeValues;
    }

    /**
     * Returns the label of the option of the attribute with the value.
     *
     * @param Entity\Attribute $attribute
     * @param string $entityTypeCode
     * @param int $storeId
     * @param mixed $attributeValue
     *
     * @return mixed the label, a list of labels for multiselect attributes, or null if there is no such option
     * @throws LocalizedException
     */
    private function getOptionLabel(
        Entity\Attribute $attribute,
        string $entityTypeCode,
        int $storeId,
        mixed $attributeValue
    ): mixed {
        if ($this->variables->isEmpty($attribute->getSourceModel())) {
            return $this->getAttributeOptionValue(
                $entityTypeCode,
                (string)$attribute->getAttributeCode(),
                $storeId,
                $this->stringValue($attributeValue)
            );
        }

        $source = $attribute->getSource();

        $source->setAttribute($attribute);

        $attribute->setData(
            'store_id',
            $entityTypeCode === Product::ENTITY || $entityTypeCode === Category::ENTITY ? $storeId : 0
        );

        foreach ($source->getAllOptions() as $option) {
            if (is_array($option) && array_key_exists('label', $option) && array_key_exists('value', $option) &&
                strcasecmp($this->stringValue($option[ 'value' ]), $this->stringValue($attributeValue)) === 0) {
                return $option[ 'label' ];
            }
        }

        return null;
    }

    /**
     * Returns the key of an attribute value in the cache.
     *
     * @param int|string $entityId
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param int $storeId
     * @param bool $useOptionValue
     *
     * @return string
     */
    private function getAttributeValueKey(
        int|string $entityId,
        string $entityTypeCode,
        string $attributeCode,
        int $storeId,
        bool $useOptionValue
    ): string {
        return sprintf(
            '%s_%s_%s_%d_%s',
            $entityId,
            $entityTypeCode,
            $attributeCode,
            $storeId,
            $useOptionValue ? 'true' : 'false'
        );
    }

    /**
     * Returns the key of the options of an attribute in the cache.
     *
     * @param array $parts
     *
     * @phpstan-param array<int, string|int|bool> $parts
     *
     * @return string
     */
    private function getCacheKey(array $parts): string
    {
        return hash('sha256', (string)json_encode($parts));
    }

    /**
     * Converts a scalar value into a string, other values into an empty string.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }

    /**
     * Converts the string values into lower case, other values stay as they are.
     *
     * @param array $values
     *
     * @phpstan-param array<int|string, mixed> $values
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    private function lowerValues(array $values): array
    {
        return array_map(
            static fn($value) => is_string($value) ? strtolower($value) : $value,
            $values
        );
    }

    /**
     * Returns the label of the option of the attribute in the store.
     *
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param int $storeId
     * @param string $optionId the option id, or comma separated option ids of a multiselect attribute
     *
     * @return mixed the label, a list of labels for multiselect attributes, null if there is no such option, or an
     *      empty array if the attribute has no options
     * @throws LocalizedException
     */
    public function getAttributeOptionValue(
        string $entityTypeCode,
        string $attributeCode,
        int $storeId,
        string $optionId
    ): mixed {
        $attribute = $this->getAttribute(
            $entityTypeCode,
            $attributeCode
        );

        if (! $attribute->usesSource()) {
            return [];
        }

        $attributeOptions = $this->getAttributeOptionValues(
            $entityTypeCode,
            $attributeCode,
            $storeId
        );

        if ($attribute->getData('frontend_input') === 'multiselect') {
            $optionIds = explode(
                ',',
                $optionId
            );

            $result = [];

            foreach ($optionIds as $nextOptionId) {
                $nextOptionId = trim($nextOptionId);

                if (array_key_exists(
                    $nextOptionId,
                    $attributeOptions
                )) {
                    $result[] = $attributeOptions[ $nextOptionId ];
                }
            }

            return $result;
        } else {
            return array_key_exists(
                $optionId,
                $attributeOptions
            ) ? $attributeOptions[ $optionId ] : null;
        }
    }

    /**
     * Returns the labels of the options of the attribute in the store, by option id.
     *
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param int $storeId
     * @param bool $strToLower
     *
     * @return array
     * @phpstan-return array<int|string, mixed> labels by option id
     * @throws LocalizedException
     */
    public function getAttributeOptionValues(
        string $entityTypeCode,
        string $attributeCode,
        int $storeId,
        bool $strToLower = false
    ): array {
        $attribute = $this->getAttribute(
            $entityTypeCode,
            $attributeCode
        );

        if (! $attribute->usesSource()) {
            return [];
        }

        $key = $this->getCacheKey([$entityTypeCode, $attributeCode, $storeId]);

        if (! array_key_exists(
            $key,
            $this->attributeOptionValues
        )) {
            $this->initAttributeOptions(
                $entityTypeCode,
                $attributeCode,
                $storeId
            );
        }

        $attributeOptionValues = $this->attributeOptionValues[ $key ] ?? [];

        return $strToLower ? $this->lowerValues($attributeOptionValues) : $attributeOptionValues;
    }

    /**
     * Loads the values and labels of the options of the attribute in the store into the cache.
     *
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param int $storeId
     *
     * @return void
     * @throws LocalizedException
     */
    protected function initAttributeOptions(string $entityTypeCode, string $attributeCode, int $storeId): void
    {
        $attribute = $this->getAttribute(
            $entityTypeCode,
            $attributeCode
        );

        if (! $attribute->usesSource()) {
            return;
        }

        $key = $this->getCacheKey([$entityTypeCode, $attributeCode, $storeId]);

        try {
            if (empty($storeId)) {
                $storeId = Store::DEFAULT_STORE_ID;
            }

            // only default (admin) store values used
            $attribute->setData(
                'store_id',
                $storeId
            );

            $this->logging->debug(
                sprintf(
                    'Loading attribute options for attribute with code: %s in store with id: %d',
                    $attribute->getAttributeCode(),
                    $storeId
                )
            );

            $values = [];

            foreach ($attribute->getSource()->getAllOptions() as $option) {
                // option groups contain their options in the value
                $innerOptions = is_array($option) && array_key_exists('value', $option) &&
                is_array($option[ 'value' ]) ? $option[ 'value' ] : [$option];

                foreach ($innerOptions as $innerOption) {
                    $values += $this->getOptionValue($innerOption);
                }
            }

            $this->logging->debug(
                sprintf(
                    'Found %d attribute option(s) for attribute with code: %s in store with id: %d: %s',
                    count($values),
                    $attribute->getAttributeCode(),
                    $storeId,
                    json_encode($values)
                )
            );

            $this->attributeOptionValues[ $key ] = $values;
        } catch (Exception $exception) {
            $this->logging->critical($exception);

            $this->attributeOptionValues[ $key ] = [];
        }
    }

    /**
     * Returns the label of the option by its value, or an empty array for options without value.
     *
     * @param mixed $option
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    private function getOptionValue(mixed $option): array
    {
        // skip ' -- Please Select -- ' option
        if (! is_array($option) || ! array_key_exists('value', $option)) {
            return [];
        }

        $optionKey = $option[ 'value' ];

        if ((! is_int($optionKey) && ! is_string($optionKey)) || $this->variables->isEmpty($optionKey)) {
            return [];
        }

        $optionValue = array_key_exists('label', $option) ? $option[ 'label' ] : $optionKey;

        return [$optionKey => $optionValue instanceof Phrase ? $optionValue->getText() : $optionValue];
    }

    /**
     * Returns the id of the option of the attribute with the label, also searched in the default store.
     *
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param int $storeId
     * @param string $value
     * @param bool $strToLower
     *
     * @return int|null
     * @throws LocalizedException
     */
    public function getAttributeOptionId(
        string $entityTypeCode,
        string $attributeCode,
        int $storeId,
        string $value,
        bool $strToLower = false
    ): ?int {
        $key = $this->getCacheKey([$entityTypeCode, $attributeCode, $storeId, $value, $strToLower]);

        if (! array_key_exists(
            $key,
            $this->attributeOptionIds
        )) {
            $attributeOptions = $this->getAttributeOptionValues(
                $entityTypeCode,
                $attributeCode,
                $storeId,
                $strToLower
            );

            $searchKey = array_search(
                $strToLower ? strtolower($value) : $value,
                $attributeOptions
            );

            $result = is_numeric($searchKey) ? (int)$searchKey : null;

            if ($result === null && $storeId !== 0) {
                $result = $this->getAttributeOptionId(
                    $entityTypeCode,
                    $attributeCode,
                    0,
                    $value,
                    $strToLower
                );
            }

            $this->attributeOptionIds[ $key ] = $result;
        }

        return $this->attributeOptionIds[ $key ];
    }

    /**
     * Checks whether the attribute has an option with the id in the store or the default store.
     *
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param int $storeId
     * @param int $optionId
     *
     * @return bool
     * @throws LocalizedException
     */
    public function checkAttributeOptionId(
        string $entityTypeCode,
        string $attributeCode,
        int $storeId,
        int $optionId
    ): bool {
        $value = $this->arrays->getValue(
            $this->getAttributeOptionValues(
                $entityTypeCode,
                $attributeCode,
                $storeId
            ),
            (string)$optionId,
            $this->arrays->getValue(
                $this->getAttributeOptionValues(
                    $entityTypeCode,
                    $attributeCode,
                    0
                ),
                (string)$optionId
            )
        );

        return $value !== null;
    }

    /**
     * Checks whether the attribute has an option with the key in the store or the default store.
     *
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param int $storeId
     * @param string $optionKey
     *
     * @return bool
     * @throws LocalizedException
     */
    public function checkAttributeOptionKey(
        string $entityTypeCode,
        string $attributeCode,
        int $storeId,
        string $optionKey
    ): bool {
        $value = $this->arrays->getValue(
            $this->getAttributeOptionValues(
                $entityTypeCode,
                $attributeCode,
                $storeId
            ),
            $optionKey,
            $this->arrays->getValue(
                $this->getAttributeOptionValues(
                    $entityTypeCode,
                    $attributeCode,
                    0
                ),
                $optionKey
            )
        );

        return $value !== null;
    }

    /**
     * Returns the type of the attribute for validation: select, multiselect, datetime, varchar or the backend type.
     *
     * @param string $entityTypeCode
     * @param string $attributeCode
     *
     * @return string
     * @throws LocalizedException
     */
    public function getAttributeType(string $entityTypeCode, string $attributeCode): string
    {
        $attribute = $this->getAttribute(
            $entityTypeCode,
            $attributeCode
        );

        if ($attribute->usesSource()) {
            return $attribute->getData('frontend_input') == 'multiselect' ? 'multiselect' : 'select';
        } else {
            if ($attribute->isStatic()) {
                return $attribute->getData('frontend_input') == 'date' ? 'datetime' : 'varchar';
            } else {
                return (string)$attribute->getBackendType();
            }
        }
    }

    /**
     * Adds an option with the label in the store (and the default store) to the attribute.
     *
     * @param AdapterInterface $dbAdapter
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param int $sortOrder
     * @param int $storeId
     * @param string|null $value
     * @param bool|null $test only add the option to the cache, with a random id
     *
     * @return int|null the id of the new option, or null if it could not be added
     * @throws LocalizedException
     */
    public function addAttributeOption(
        AdapterInterface $dbAdapter,
        string $entityTypeCode,
        string $attributeCode,
        int $sortOrder,
        int $storeId,
        ?string $value = null,
        ?bool $test = false
    ): ?int {
        $this->logging->info(
            sprintf(
                'Adding option to attribute with code: %s in store with id: %d using value: "%s"',
                $attributeCode,
                $storeId,
                $value
            )
        );

        if ($value === null) {
            $this->logging->error(
                sprintf(
                    'Could not add option to attribute with code: %s in store with id: %d because: %s',
                    $attributeCode,
                    $storeId,
                    'Value cannot be null'
                )
            );

            return null;
        }

        $attribute = $this->getAttribute(
            $entityTypeCode,
            $attributeCode
        );

        $optionTableName = $this->databaseHelper->getTableName('eav_attribute_option');
        $optionValueTableName = $this->databaseHelper->getTableName('eav_attribute_option_value');

        if (! $test) {
            try {
                $dbAdapter->insert(
                    $optionTableName,
                    [
                        'attribute_id' => $attribute->getId(),
                        'sort_order'   => $sortOrder,
                    ]
                );

                /** @var Mysql $dbAdapter */
                $optionId = $dbAdapter->lastInsertId($optionTableName);

                if (empty($optionId)) {
                    $this->logging->error(
                        sprintf(
                            'Could not add option to attribute with code: %s in store with id: %d because: %s',
                            $attributeCode,
                            $storeId,
                            'Could not identify created option id'
                        )
                    );

                    return null;
                } else {
                    $optionId = (int)$optionId;
                }

                if ($storeId !== 0) {
                    $dbAdapter->insert(
                        $optionValueTableName,
                        [
                            'option_id' => $optionId,
                            'store_id'  => 0,
                            'value'     => $value,
                        ]
                    );
                }

                $dbAdapter->insert(
                    $optionValueTableName,
                    [
                        'option_id' => $optionId,
                        'store_id'  => $storeId,
                        'value'     => $value,
                    ]
                );
            } catch (Exception $exception) {
                $this->logging->error(
                    sprintf(
                        'Could not add option to attribute with code: %s in store with id: %d because: %s',
                        $attributeCode,
                        $storeId,
                        $exception->getMessage()
                    )
                );

                return null;
            }
        } else {
            $optionId = rand(
                10000000,
                99999999
            );
        }

        $key = $this->getCacheKey([$entityTypeCode, $attributeCode, $storeId]);

        if (! array_key_exists(
            $key,
            $this->attributeOptionValues
        )) {
            $this->getAttributeOptionValues(
                $entityTypeCode,
                $attributeCode,
                $storeId
            );
        }

        if (array_key_exists(
            $key,
            $this->attributeOptionValues
        )) {
            $this->attributeOptionValues[ $key ][ $optionId ] = $value;
        }

        if ($storeId !== 0) {
            $key = $this->getCacheKey([$entityTypeCode, $attributeCode, 0]);

            if (! array_key_exists(
                $key,
                $this->attributeOptionValues
            )) {
                $this->getAttributeOptionValues(
                    $entityTypeCode,
                    $attributeCode,
                    0
                );
            }

            if (array_key_exists(
                $key,
                $this->attributeOptionValues
            )) {
                $this->attributeOptionValues[ $key ][ $optionId ] = $value;
            }
        }

        return $optionId;
    }

    /**
     * Returns the catalog attribute of the category attribute, with the catalog specific settings.
     *
     * @param Entity\Attribute $attribute
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     * @throws NoSuchEntityException
     */
    public function getCatalogCategoryAttribute(
        Entity\Attribute $attribute
    ): \Magento\Catalog\Model\ResourceModel\Eav\Attribute {
        $attributeCode = $attribute->getAttributeCode();

        if (! array_key_exists(
            $attributeCode,
            $this->categoryAttributes
        )) {
            $this->logging->debug(
                sprintf(
                    'Loading catalog category attribute with code: %s',
                    $attributeCode
                )
            );

            $catalogAttribute = $this->loadCatalogEavAttribute($attribute);

            if (! $catalogAttribute || ! $catalogAttribute->getId()) {
                throw new NoSuchEntityException(
                    __('Could not load catalog category attribute with code: %1', $attributeCode)
                );
            }

            $this->categoryAttributes[ $attributeCode ] = $catalogAttribute;
        }

        return $this->categoryAttributes[ $attributeCode ];
    }

    /**
     * Returns the catalog attribute of the product attribute, with the catalog specific settings.
     *
     * @param Entity\Attribute $attribute
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     * @throws NoSuchEntityException
     */
    public function getCatalogProductAttribute(
        Entity\Attribute $attribute
    ): \Magento\Catalog\Model\ResourceModel\Eav\Attribute {
        $attributeCode = $attribute->getAttributeCode();

        if (! array_key_exists(
            $attributeCode,
            $this->productAttributes
        )) {
            $this->logging->debug(
                sprintf(
                    'Loading catalog product attribute with code: %s',
                    $attributeCode
                )
            );

            $catalogAttribute = $this->loadCatalogEavAttribute($attribute);

            if (! $catalogAttribute || ! $catalogAttribute->getId()) {
                throw new NoSuchEntityException(
                    __('Could not load catalog product attribute with code: %1', $attributeCode)
                );
            }

            $this->productAttributes[ $attributeCode ] = $catalogAttribute;
        }

        return $this->productAttributes[ $attributeCode ];
    }

    /**
     * Returns the attribute set by id.
     *
     * @param int $attributeSetId
     *
     * @return Set|null
     */
    public function getAttributeSetById(int $attributeSetId): ?Set
    {
        if (array_key_exists(
            $attributeSetId,
            $this->attributeSetsById
        )) {
            return $this->attributeSetsById[ $attributeSetId ];
        }

        $attributeSet = $this->attributeSetFactory->create();

        $this->attributeSetResourceFactory->create()->load(
            $attributeSet,
            $attributeSetId
        );

        if ($attributeSet->getId()) {
            $this->attributeSetsById[ $attributeSet->getId() ] = $attributeSet;

            $attributeSetNameKey = sprintf(
                '%d_%s',
                $attributeSet->getEntityTypeId(),
                $attributeSet->getAttributeSetName()
            );

            $this->attributeSetsByName[ $attributeSetNameKey ] = $attributeSet;

            return $attributeSet;
        }

        return null;
    }

    /**
     * Returns the attribute set by name.
     *
     * @param int $entityTypeId
     * @param string $attributeSetName
     *
     * @return Set|null
     * @throws LocalizedException
     */
    public function getAttributeSetByName(int $entityTypeId, string $attributeSetName): ?Set
    {
        $attributeSetNameKey = sprintf(
            '%d_%s',
            $entityTypeId,
            $attributeSetName
        );

        if (array_key_exists(
            $attributeSetNameKey,
            $this->attributeSetsByName
        )) {
            return $this->attributeSetsByName[ $attributeSetNameKey ];
        }

        $attributeSetCollection = $this->attributeSetCollectionFactory->create();

        $attributeSetCollection->setEntityTypeFilter($entityTypeId);
        $attributeSetCollection->addFieldToFilter(
            'attribute_set_name',
            ['eq' => $attributeSetName]
        );

        $attributeSetCollection->load();

        if (count($attributeSetCollection)) {
            /** @var Set $attributeSet */
            $attributeSet = $attributeSetCollection->getFirstItem();

            $this->attributeSetsById[ $attributeSet->getId() ] = $attributeSet;
            $this->attributeSetsByName[ $attributeSetNameKey ] = $attributeSet;

            return $attributeSet;
        }

        return null;
    }

    /**
     * Returns the value of the product attribute of the product in the store.
     *
     * @param int $entityId
     * @param string $attributeCode
     * @param int $storeId
     * @param bool $useOptionValue
     * @param bool $strToLower
     *
     * @return mixed the label instead of the option id if $useOptionValue is true
     * @throws LocalizedException
     */
    public function getProductAttributeValue(
        int $entityId,
        string $attributeCode,
        int $storeId,
        bool $useOptionValue = true,
        bool $strToLower = false
    ): mixed {
        return $this->getAttributeValue(
            $this->databaseHelper->getDefaultConnection(),
            Product::ENTITY,
            $attributeCode,
            $entityId,
            $storeId,
            $useOptionValue,
            $strToLower
        );
    }
}
