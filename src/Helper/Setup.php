<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Arrays;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\SetupInterface;
use Magento\Framework\Validator\ValidateException;
use Zend_Db_Exception;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Setup
{
    public const ATTRIBUTE_TYPE_DATETIME = 'datetime';
    public const ATTRIBUTE_TYPE_DECIMAL = 'decimal';
    public const ATTRIBUTE_TYPE_INT = 'int';
    public const ATTRIBUTE_TYPE_TEXT = 'text';
    public const ATTRIBUTE_TYPE_VARCHAR = 'varchar';

    /** @var Arrays */
    protected $arrays;

    /** @var EavSetupFactory */
    protected $eavSetupFactory;

    public function __construct(Arrays $arrayHelper, EavSetupFactory $eavSetupFactory)
    {
        $this->arrays = $arrayHelper;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function getEavSetup(SetupInterface $setup)
    {
        $this->eavSetupFactory->create(['setup' => $setup]);
    }

    public function addEntityType(
        EavSetup $setup,
        string $entityTypeName,
        string $entityTypeModel,
        ?string $attributeModel,
        ?string $attributeCollectionModel,
        string $entityTypeTableName
    ): void {
        $entityType = $setup->getEntityType($entityTypeName);

        if (! $entityType) {
            $setup->addEntityType(
                $entityTypeName,
                [
                    'entity_model'                => $entityTypeModel,
                    'attribute_model'             => $attributeModel,
                    'entity_attribute_collection' => $attributeCollectionModel,
                    'table'                       => $entityTypeTableName
                ]
            );
        }
    }

    /**
     * @throws Zend_Db_Exception
     */
    public function addEntityTypeTables(
        SetupInterface $setup,
        string $entityTypeTableName
    ): void {
        $connection = $setup->getConnection();

        $entityTypeTableName = $setup->getTable($entityTypeTableName);

        if (! $connection->isTableExists($entityTypeTableName)) {
            $entityTypeTable = $connection->newTable($entityTypeTableName);

            $entityTypeTable->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true,
                ],
                'Entity ID'
            );
            $entityTypeTable->addColumn(
                'created_at',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Creation Time'
            );
            $entityTypeTable->addColumn(
                'updated_at',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Update Time'
            );

            $connection->createTable($entityTypeTable);
        }

        $this->addEntityAttributeType(
            $setup,
            $entityTypeTableName,
            static::ATTRIBUTE_TYPE_DATETIME
        );
        $this->addEntityAttributeType(
            $setup,
            $entityTypeTableName,
            static::ATTRIBUTE_TYPE_DECIMAL
        );
        $this->addEntityAttributeType(
            $setup,
            $entityTypeTableName,
            static::ATTRIBUTE_TYPE_INT
        );
        $this->addEntityAttributeType(
            $setup,
            $entityTypeTableName,
            static::ATTRIBUTE_TYPE_TEXT
        );
        $this->addEntityAttributeType(
            $setup,
            $entityTypeTableName,
            static::ATTRIBUTE_TYPE_VARCHAR
        );
    }

    /**
     * @throws Zend_Db_Exception
     */
    protected function addEntityAttributeType(
        SetupInterface $setup,
        string $entityTypeTableName,
        string $attributeType
    ): void {
        $connection = $setup->getConnection();

        $eavAttributeTableName = $setup->getTable('eav_attribute');
        $magentoStoreTableName = $setup->getTable('store');
        $entityTypeTableName = $setup->getTable($entityTypeTableName);
        $entityAttributeTypeTableName = $setup->getTable(
            sprintf(
                '%s_%s',
                $entityTypeTableName,
                $attributeType
            )
        );

        if (! $connection->isTableExists($entityAttributeTypeTableName)) {
            $entityAttributeTypeTable = $connection->newTable($entityAttributeTypeTableName);

            $entityAttributeTypeTable->addColumn(
                'value_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary'  => true,
                ],
                'Value Id'
            );
            $entityAttributeTypeTable->addColumn(
                'entity_type_id',
                Table::TYPE_SMALLINT,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                    'default'  => '0',
                ],
                'Entity Type Id'
            );
            $entityAttributeTypeTable->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                    'default'  => '0',
                ],
                'Entity Id'
            );
            $entityAttributeTypeTable->addColumn(
                'attribute_id',
                Table::TYPE_SMALLINT,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                    'default'  => '0',
                ],
                'Attribute Id'
            );
            $entityAttributeTypeTable->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                    'default'  => '0',
                ],
                'Store ID'
            );

            if ($attributeType == static::ATTRIBUTE_TYPE_DATETIME) {
                $entityAttributeTypeTable->addColumn(
                    'value',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => true, 'default' => '0000-00-00 00:00:00'],
                    'Value'
                );
            } elseif ($attributeType == static::ATTRIBUTE_TYPE_DECIMAL) {
                $entityAttributeTypeTable->addColumn(
                    'value',
                    Table::TYPE_DECIMAL,
                    [12, 4],
                    ['nullable' => true, 'default' => '0.0000'],
                    'Value'
                );
            } elseif ($attributeType == static::ATTRIBUTE_TYPE_INT) {
                $entityAttributeTypeTable->addColumn(
                    'value',
                    Table::TYPE_INTEGER,
                    11,
                    ['nullable' => true, 'default' => 0],
                    'Value'
                );
            } elseif ($attributeType == static::ATTRIBUTE_TYPE_TEXT) {
                $entityAttributeTypeTable->addColumn(
                    'value',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => true],
                    'Value'
                );
            } elseif ($attributeType == static::ATTRIBUTE_TYPE_VARCHAR) {
                $entityAttributeTypeTable->addColumn(
                    'value',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'Value'
                );
            }

            if ($setup instanceof SchemaSetupInterface) {
                $entityAttributeTypeTable->addIndex(
                    $setup->getIdxName(
                        $entityAttributeTypeTableName,
                        ['entity_id', 'attribute_id', 'store_id'],
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    ['entity_id', 'attribute_id', 'store_id'],
                    ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                );
                $entityAttributeTypeTable->addIndex(
                    $setup->getIdxName(
                        $entityAttributeTypeTableName,
                        ['entity_id']
                    ),
                    ['entity_id']
                );
                $entityAttributeTypeTable->addIndex(
                    $setup->getIdxName(
                        $entityAttributeTypeTableName,
                        ['attribute_id']
                    ),
                    ['attribute_id']
                );
                $entityAttributeTypeTable->addIndex(
                    $setup->getIdxName(
                        $entityAttributeTypeTableName,
                        ['store_id']
                    ),
                    ['store_id']
                );

                $entityAttributeTypeTable->addForeignKey(
                    $setup->getFkName(
                        $entityAttributeTypeTableName,
                        'entity_id',
                        $entityTypeTableName,
                        'entity_id'
                    ),
                    'entity_id',
                    $entityTypeTableName,
                    'entity_id',
                    Table::ACTION_CASCADE
                );
                $entityAttributeTypeTable->addForeignKey(
                    $setup->getFkName(
                        $entityAttributeTypeTableName,
                        'attribute_id',
                        $eavAttributeTableName,
                        'attribute_id'
                    ),
                    'attribute_id',
                    $eavAttributeTableName,
                    'attribute_id',
                    Table::ACTION_CASCADE
                );
                $entityAttributeTypeTable->addForeignKey(
                    $setup->getFkName(
                        $entityAttributeTypeTableName,
                        'store_id',
                        $magentoStoreTableName,
                        'store_id'
                    ),
                    'store_id',
                    $magentoStoreTableName,
                    'store_id',
                    Table::ACTION_CASCADE
                );
            }

            $connection->createTable($entityAttributeTypeTable);
        }
    }

    public function addProductAttributeToSetAndGroup(
        EavSetup $setup,
        string $attributeCode,
        string $attributeSetName,
        string $attributeGroupName,
        ?int $attributeSortOrder = null
    ): bool {
        return $this->addAttributeToSetAndGroup(
            $setup,
            Product::ENTITY,
            $attributeCode,
            $attributeSetName,
            $attributeGroupName,
            $attributeSortOrder
        );
    }

    public function addAttributeToSetAndGroup(
        EavSetup $setup,
        string $entityType,
        string $attributeCode,
        string $attributeSetName,
        string $attributeGroupName,
        ?int $attributeSortOrder = null
    ): bool {
        /** @var array $entityTypeData */
        $entityTypeData = $setup->getEntityType($entityType);

        $entityTypeId = (int)$this->arrays->getValue(
            $entityTypeData,
            'entity_type_id'
        );

        /** @var array $attributeData */
        $attributeData = $setup->getAttribute(
            $entityTypeId,
            $attributeCode
        );

        if (is_array($attributeData)) {
            $attributeId = (int)$this->arrays->getValue(
                $attributeData,
                'attribute_id'
            );

            if ($attributeId) {
                $this->addAttributeIdToSetAndGroup(
                    $setup,
                    $entityTypeId,
                    $attributeId,
                    $attributeSetName,
                    $attributeGroupName,
                    $attributeSortOrder
                );
            }
        }

        return false;
    }

    public function addAttributeIdToSetAndGroup(
        EavSetup $setup,
        int $entityTypeId,
        int $attributeId,
        string $attributeSetName,
        string $attributeGroupName,
        ?int $attributeSortOrder = null
    ): bool {
        /** @var array $attributeSetData */
        $attributeSetData = $setup->getAttributeSet(
            $entityTypeId,
            $attributeSetName
        );

        if (is_array($attributeSetData)) {
            $attributeSetId = $this->arrays->getValue(
                $attributeSetData,
                'attribute_set_id'
            );

            if ($attributeSetId) {
                /** @var array $attributeGroupData */
                $attributeGroupData = $setup->getAttributeGroup(
                    $entityTypeId,
                    $attributeSetId,
                    $attributeGroupName
                );

                if (is_array($attributeGroupData)) {
                    $attributeGroupId = $this->arrays->getValue(
                        $attributeGroupData,
                        'attribute_group_id'
                    );

                    if ($attributeGroupId) {
                        $setup->addAttributeToGroup(
                            $entityTypeId,
                            $attributeSetId,
                            $attributeGroupId,
                            $attributeId,
                            $attributeSortOrder
                        );

                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function addEavEntityAttribute(
        EavSetup $setup,
        string $entityTypeId,
        string $attributeCode,
        string $label,
        int $scope,
        string $type,
        string $input,
        int $sortOrder,
        ?string $default = null,
        bool $userDefined = false,
        bool $required = false,
        bool $usedInProductListing = false,
        bool $searchable = false,
        bool $filterable = false,
        bool $comparable = false,
        bool $visible = true,
        bool $visibleOnFront = false,
        bool $unique = false,
        string $backendModel = null,
        string $sourceModel = null
    ): void {
        $setup->addAttribute(
            $entityTypeId,
            $attributeCode,
            [
                'label'                   => $label,
                'global'                  => $scope,
                'type'                    => $type,
                'input'                   => $input,
                'sort_order'              => $sortOrder,
                'default'                 => $default,
                'user_defined'            => $userDefined,
                'required'                => $required,
                'used_in_product_listing' => $usedInProductListing,
                'searchable'              => $searchable,
                'filterable'              => $filterable,
                'comparable'              => $comparable,
                'visible'                 => $visible,
                'visible_on_front'        => $visibleOnFront,
                'unique'                  => $unique,
                'backend'                 => $backendModel,
                'source'                  => $sourceModel
            ]
        );
    }
}
