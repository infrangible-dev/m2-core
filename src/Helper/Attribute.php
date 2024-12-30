<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Magento\Catalog\Api\CategoryAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\ConfigFactory;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Attribute
{
    /** @var Arrays */
    protected $arrays;

    /** @var Variables */
    protected $variables;

    /** @var Database */
    protected $databaseHelper;

    /** @var EntityType */
    protected $entityTypeHelper;

    /** @var LoggerInterface */
    protected $logging;

    /** @var AttributeFactory */
    protected $attributeFactory;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory */
    protected $attributeResourceFactory;

    /** @var CollectionFactory */
    protected $attributeCollectionFactory;

    /** @var SetFactory */
    protected $attributeSetFactory;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\SetFactory */
    protected $attributeSetResourceFactory;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory */
    protected $attributeSetCollectionFactory;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory */
    protected $attributeGroupCollectionFactory;

    /** @var ProductAttributeRepositoryInterface */
    protected $productAttributeRepository;

    /** @var CategoryAttributeRepositoryInterface */
    protected $categoryAttributeRepository;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory */
    protected $productAttributeCollectionFactory;

    /** @var ConfigFactory */
    protected $configFactory;

    /** @var Entity\Attribute[] */
    private $attributes = [];

    /** @var int[] */
    private $attributeIds = [];

    /** @var array */
    private $attributeValues = [];

    /** @var array */
    private $attributeOptionValues = [];

    /** @var array */
    private $attributeOptionIds = [];

    /** @var array */
    private $categoryAttributes = [];

    /** @var array */
    private $productAttributes = [];

    /** @var Set[] */
    private $attributeSetsById = [];

    /** @var Set[] */
    private $attributeSetsByName = [];

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
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $attributeGroupCollectionFactory,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        CategoryAttributeRepositoryInterface $categoryAttributeRepository,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $productAttributeCollectionFactory,
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

    public function getAttributeCollection(): Collection
    {
        return $this->attributeCollectionFactory->create();
    }

    public function newAttribute(): Entity\Attribute
    {
        return $this->attributeFactory->create();
    }

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
     * @throws Exception
     */
    public function deleteAttribute(Entity\Attribute $attribute)
    {
        $this->attributeResourceFactory->create()->delete($attribute);
    }

    public function getAttributeSetCollection(): \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection
    {
        return $this->attributeSetCollectionFactory->create();
    }

    public function newAttributeSet(): Set
    {
        return $this->attributeSetFactory->create();
    }

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
     * @throws Exception
     */
    public function deleteAttributeSet(Set $attributeSet)
    {
        $this->attributeSetResourceFactory->create()->delete($attributeSet);
    }

    public function getAttributeGroupCollection(): \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\Collection
    {
        return $this->attributeGroupCollectionFactory->create();
    }

    /**
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

    public function getProductAttributeCollection(): \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
    {
        return $this->productAttributeCollectionFactory->create();
    }

    /**
     * @throws Exception
     */
    public function getAttribute(string $entityTypeCode, string $attributeCode): Entity\Attribute
    {
        $key = sprintf(
            '%s_%s',
            $entityTypeCode,
            $attributeCode
        );

        if (array_key_exists(
            $key,
            $this->attributes
        )) {
            if ($this->attributes[ $key ] === null) {
                throw new Exception(
                    sprintf(
                        'Could not load attribute with entity: %s and code: %s',
                        $entityTypeCode,
                        $attributeCode
                    )
                );
            }

            return $this->attributes[ $key ];
        }

        /** @var Entity\Attribute $attribute */
        $attribute = $this->configFactory->create()->getAttribute(
            $entityTypeCode,
            $attributeCode
        );

        if (! $attribute || ! $attribute->getId()) {
            $this->attributes[ $key ] = null;

            throw new Exception(
                sprintf(
                    'Could not load attribute with entity: %s and code: %s',
                    $entityTypeCode,
                    $attributeCode
                )
            );
        }

        $this->attributes[ $key ] = $attribute;

        return $attribute;
    }

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

        $attributeQuery = $dbAdapter->select()->from(['eav_attribute' => $eavAttributeTableName],
            ['attribute_id']);

        $attributeQuery->joinLeft(['eav_entity_type' => $entityTypeTableName],
            'eav_entity_type.entity_type_id = eav_attribute.entity_type_id',
            'entity_type_code');

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
     * @throws Exception
     */
    public function getAttributeValue(
        AdapterInterface $dbAdapter,
        string $entityTypeCode,
        string $attributeCode,
        int $entityId,
        int $storeId,
        bool $useOptionValue = true,
        bool $strToLower = false
    ) {
        $attributeValues = $this->getAttributeValues(
            $dbAdapter,
            $entityTypeCode,
            $attributeCode,
            [$entityId],
            $storeId,
            $useOptionValue,
            $strToLower
        );

        return $this->arrays->getValue(
            $attributeValues,
            strval($entityId)
        );
    }

    /**
     * @throws Exception
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

        foreach ($entityIds as $entityId) {
            $key = sprintf(
                '%d_%s_%s_%d_%s',
                $entityId,
                $entityTypeCode,
                $attributeCode,
                $storeId,
                var_export(
                    $useOptionValue,
                    true
                )
            );

            if (array_key_exists(
                $key,
                $this->attributeValues
            )) {
                $attributeValues[ $entityId ] = $this->attributeValues[ $key ];

                unset($entityIds[ $key ]);
            }
        }

        $entityType = $this->entityTypeHelper->getEntityType($entityTypeCode);

        if (empty($entityType)) {
            throw new Exception(
                sprintf(
                    'Could not load entity type with code: %s',
                    $entityTypeCode
                )
            );
        }

        $entityIdField = $entityType->getEntityIdField();

        if (empty($entityIdField)) {
            $entityIdField = Entity::DEFAULT_ENTITY_ID_FIELD;
        }

        $isEntityIdField = strcasecmp(
                $entityIdField,
                $attributeCode
            ) === 0;

        if ($isEntityIdField || ($entityTypeCode === Product::ENTITY && $attributeCode === 'type_id')) {
            $attributeTable = $this->databaseHelper->getTableName($entityType->getEntityTable());

            $isStatic = true;
        } else {
            $attribute = $this->getAttribute(
                $entityTypeCode,
                $attributeCode
            );

            $attributeTable = $attribute->getBackend()->getTable();

            $isStatic = $attribute->isStatic();
        }

        if ($isStatic) {
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

            $queryResult = $this->databaseHelper->fetchAssoc(
                $valueQuery,
                $dbAdapter
            );

            if (! empty($queryResult)) {
                foreach ($queryResult as $row) {
                    if (array_key_exists(
                            $entityIdField,
                            $row
                        ) && array_key_exists(
                            $attributeCode,
                            $row
                        )) {
                        $key = sprintf(
                            '%d_%s_%s_%d',
                            $row[ $entityIdField ],
                            $entityTypeCode,
                            $attributeCode,
                            $storeId
                        );

                        $this->attributeValues[ $key ] = $row[ $attributeCode ];

                        $attributeValues[ $row[ $entityIdField ] ] = $row[ $attributeCode ];
                    }
                }
            }
        } else {
            $attribute = $this->getAttribute(
                $entityTypeCode,
                $attributeCode
            );

            $columns = [
                'entity_id',
                'value',
            ];

            if ($entityTypeCode === Product::ENTITY || $entityTypeCode === Category::ENTITY) {
                $columns[] = 'store_id';
            }

            $valueQuery = $dbAdapter->select()->from(
                $attributeTable,
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

            if ($entityTypeCode === Product::ENTITY || $entityTypeCode === Category::ENTITY) {
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

            if (! empty($queryResult)) {
                foreach ($entityIds as $entityId) {
                    $entityValues = [];

                    foreach ($queryResult as $key => $row) {
                        if (array_key_exists(
                                'entity_id',
                                $row
                            ) && array_key_exists(
                                'value',
                                $row
                            ) && $row[ 'entity_id' ] == $entityId) {

                            if (array_key_exists(
                                'store_id',
                                $row
                            )) {
                                $entityValues[ $row[ 'store_id' ] ] = $row[ 'value' ];
                            } else {
                                $entityValues[ $storeId ] = $row[ 'value' ];
                            }

                            unset($queryResult[ $key ]);
                        }
                    }

                    $attributeValue = $this->arrays->getValue(
                        $entityValues,
                        strval($storeId),
                        $this->arrays->getValue(
                            $entityValues,
                            '0'
                        )
                    );

                    if ($attributeValue !== null && $useOptionValue === true && $attribute->usesSource()) {
                        $sourceModel = $attribute->getSourceModel();

                        if ($this->variables->isEmpty($sourceModel)) {
                            $attributeValue = $this->getAttributeOptionValue(
                                $entityTypeCode,
                                $attributeCode,
                                $storeId,
                                $attributeValue
                            );
                        } else {
                            $source = $attribute->getSource();

                            $source->setAttribute($attribute);

                            $attribute->setData(
                                'store_id',
                                $entityTypeCode === Product::ENTITY || $entityTypeCode === Category::ENTITY ? $storeId :
                                    0
                            );

                            $optionValue = null;

                            foreach ($source->getAllOptions() as $option) {
                                if (array_key_exists(
                                        'label',
                                        $option
                                    ) && array_key_exists(
                                        'value',
                                        $option
                                    )) {
                                    if (strcasecmp(
                                            strval($option[ 'value' ]),
                                            strval($attributeValue)
                                        ) === 0) {
                                        $optionValue = $option[ 'label' ];
                                        break;
                                    }
                                }
                            }

                            $attributeValue = $optionValue;
                        }
                    }

                    $key = sprintf(
                        '%d_%s_%s_%d_%s',
                        $entityId,
                        $entityTypeCode,
                        $attributeCode,
                        $storeId,
                        var_export(
                            $useOptionValue,
                            true
                        )
                    );

                    $this->attributeValues[ $key ] = $attributeValue;

                    $attributeValues[ $entityId ] = $attributeValue;
                }
            }
        }

        if ($strToLower) {
            $attributeValues = array_map(
                'strtolower',
                $attributeValues
            );
        }

        return $attributeValues;
    }

    /**
     * @return int|array|null
     * @throws Exception
     */
    public function getAttributeOptionValue(
        string $entityTypeCode,
        string $attributeCode,
        int $storeId,
        string $optionId
    ) {
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
     * @throws Exception
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

        $key = md5(json_encode([$entityTypeCode, $attributeCode, $storeId]));

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

        $attributeOptionValues = $this->arrays->getValue(
            $this->attributeOptionValues,
            $key,
            []
        );

        if ($strToLower) {
            $attributeOptionValues = array_map(
                'strtolower',
                $attributeOptionValues
            );
        }

        return $attributeOptionValues;
    }

    /**
     * @throws Exception
     */
    protected function initAttributeOptions(string $entityTypeCode, string $attributeCode, int $storeId)
    {
        $attribute = $this->getAttribute(
            $entityTypeCode,
            $attributeCode
        );

        if (! $attribute->usesSource()) {
            return;
        }

        $key = md5(json_encode([$entityTypeCode, $attributeCode, $storeId]));

        try {
            if (empty($storeId)) {
                $storeId = Store::DEFAULT_STORE_ID;
            }

            // only default (admin) store values used
            $attribute->setData(
                'store_id',
                $storeId
            );

            $values = [];

            $this->logging->debug(
                sprintf(
                    'Loading attribute options for attribute with code: %s in store with id: %d',
                    $attribute->getAttributeCode(),
                    $storeId
                )
            );

            foreach ($attribute->getSource()->getAllOptions() as $option) {
                $value = is_array($option) && array_key_exists(
                    'value',
                    $option
                ) && is_array($option[ 'value' ]) ? $option[ 'value' ] : [$option];

                foreach ($value as $innerOption) {
                    // skip ' -- Please Select -- ' option
                    if (array_key_exists(
                        'value',
                        $innerOption
                    )) {
                        $optionKey = $innerOption[ 'value' ];
                        if (! $this->variables->isEmpty($optionKey)) {
                            $optionValue = array_key_exists(
                                'label',
                                $innerOption
                            ) ? $innerOption[ 'label' ] : $innerOption[ 'value' ];
                            if ($optionValue instanceof Phrase) {
                                $optionValue = $optionValue->getText();
                            }
                            $values[ $optionKey ] = $optionValue;
                        }
                    }
                }
            }

            $this->logging->debug(
                sprintf(
                    'Found %d attribute option(s) for attribute with code: %s in store with id: %d: %s',
                    count($values),
                    $attribute->getAttributeCode(),
                    $storeId,
                    trim(
                        print_r(
                            $values,
                            true
                        )
                    )
                )
            );

            $this->attributeOptionValues[ $key ] = $values;
        } catch (Exception $exception) {
            $this->logging->critical($exception);

            $this->attributeOptionValues[ $key ] = [];
        }
    }

    /**
     * @throws Exception
     */
    public function getAttributeOptionId(
        string $entityTypeCode,
        string $attributeCode,
        int $storeId,
        string $value,
        bool $strToLower = false
    ): ?int {
        $key = md5(json_encode([$entityTypeCode, $attributeCode, $storeId, $value, $strToLower]));

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

            $result = $searchKey !== false ? $searchKey : null;

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
     * @throws Exception
     */
    public function checkAttributeOptionId(
        string $entityTypeCode,
        string $attributeCode,
        int $storeId,
        int $optionId
    ): bool {
        return $this->arrays->getValue(
                $this->getAttributeOptionValues(
                    $entityTypeCode,
                    $attributeCode,
                    $storeId
                ),
                strval($optionId),
                $this->arrays->getValue(
                    $this->getAttributeOptionValues(
                        $entityTypeCode,
                        $attributeCode,
                        0
                    ),
                    strval($optionId)
                )
            ) !== null;
    }

    /**
     * @throws Exception
     */
    public function checkAttributeOptionKey(
        string $entityTypeCode,
        string $attributeCode,
        int $storeId,
        string $optionKey
    ): bool {
        return $this->arrays->getValue(
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
            ) !== null;
    }

    /**
     * @throws Exception
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
     * @throws Exception
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
                    'Could not add option to attribute with code: %s in store with id: %d using value: "%s" because: %s',
                    $attributeCode,
                    $storeId,
                    $value,
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

        $key = md5(json_encode([$entityTypeCode, $attributeCode, $storeId]));

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
            $key = $attribute->getAttributeCode() . '_0';

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
     * @throws Exception
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
                throw new Exception(
                    sprintf(
                        'Could not load catalog category attribute with code: %d',
                        $attributeCode
                    )
                );
            }

            $this->categoryAttributes[ $attributeCode ] = $catalogAttribute;
        }

        return $this->categoryAttributes[ $attributeCode ];
    }

    /**
     * @throws Exception
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
                throw new Exception(
                    sprintf(
                        'Could not load catalog product attribute with code: %d',
                        $attributeCode
                    )
                );
            }

            $this->productAttributes[ $attributeCode ] = $catalogAttribute;
        }

        return $this->productAttributes[ $attributeCode ];
    }

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
            /* @var Set $attributeSet */
            $attributeSet = $attributeSetCollection->getFirstItem();

            $this->attributeSetsById[ $attributeSet->getId() ] = $attributeSet;
            $this->attributeSetsByName[ $attributeSetNameKey ] = $attributeSet;

            return $attributeSet;
        }

        return null;
    }
}
