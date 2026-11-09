<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Variables;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Exception\LocalizedException;
use RuntimeException;

class EntityType
{
    /** @var array<string, Type> */
    private array $entityTypes = [];

    /**
     * @param Config $eavConfig
     * @param Database $databaseHelper
     * @param Variables $variables
     */
    public function __construct(
        protected Config $eavConfig,
        protected Database $databaseHelper,
        protected Variables $variables
    ) {
    }

    /**
     * Returns the EAV entity type with the code, for example catalog_product.
     *
     * @param string $entityTypeCode
     *
     * @return Type|null
     * @throws LocalizedException
     */
    public function getEntityType(string $entityTypeCode): ?Type
    {
        if (array_key_exists(
            $entityTypeCode,
            $this->entityTypes
        )) {
            return $this->entityTypes[ $entityTypeCode ];
        }

        $entityType = $this->eavConfig->getEntityType($entityTypeCode);

        $this->entityTypes[ $entityTypeCode ] = $entityType;

        return $entityType;
    }

    /**
     * Returns the name of the entity table of the entity type with the code.
     *
     * @param string $entityTypeCode
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getEntityTypeTableByEntityTypeCode(string $entityTypeCode): ?string
    {
        $entityType = $this->getEntityType($entityTypeCode);

        return $entityType !== null ? $this->getEntityTypeTableByEntityType($entityType) : null;
    }

    /**
     * Returns the name of the entity table of the entity type.
     *
     * @param Type $entityType
     *
     * @return string
     * @throws RuntimeException if the entity type has no entity table
     */
    public function getEntityTypeTableByEntityType(Type $entityType): string
    {
        $entityTable = $entityType->getEntityTable();

        if ($entityTable === null) {
            throw new RuntimeException(
                sprintf('Entity type has no entity table: %s', $entityType->getEntityTypeCode())
            );
        }

        return $this->databaseHelper->getTableName($entityTable);
    }

    /**
     * Returns the category entity type.
     *
     * @return Type|null
     * @throws LocalizedException
     */
    public function getCategoryEntityType(): ?Type
    {
        return $this->getEntityType(Category::ENTITY);
    }

    /**
     * Returns the category entity type id.
     *
     * @return int|null
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getCategoryEntityTypeId(): ?int
    {
        $categoryEntityType = $this->getCategoryEntityType();

        return empty($categoryEntityType) ? null : $this->variables->intValue($categoryEntityType->getId());
    }

    /**
     * Returns the name of the entity table of categories.
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getCategoryEntityTypeTable(): ?string
    {
        return $this->getEntityTypeTableByEntityTypeCode(Category::ENTITY);
    }

    /**
     * Returns the product entity type.
     *
     * @return Type|null
     * @throws LocalizedException
     */
    public function getProductEntityType(): ?Type
    {
        return $this->getEntityType(Product::ENTITY);
    }

    /**
     * Returns the product entity type id.
     *
     * @return int|null
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getProductEntityTypeId(): ?int
    {
        $productEntityType = $this->getProductEntityType();

        return empty($productEntityType) ? null : $this->variables->intValue($productEntityType->getId());
    }

    /**
     * Returns the name of the entity table of products.
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getProductEntityTypeTable(): ?string
    {
        return $this->getEntityTypeTableByEntityTypeCode(Product::ENTITY);
    }

    /**
     * Returns the id of the default attribute set of products.
     *
     * @return int|null
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getProductEntityTypeDefaultAttributeSetId(): ?int
    {
        $productEntityType = $this->getProductEntityType();

        return empty($productEntityType) ? null :
            $this->variables->intValue($productEntityType->getDefaultAttributeSetId());
    }

    /**
     * Returns the customer entity type.
     *
     * @return Type|null
     * @throws LocalizedException
     */
    public function getCustomerEntityType(): ?Type
    {
        return $this->getEntityType(Customer::ENTITY);
    }

    /**
     * Returns the customer address entity type.
     *
     * @return Type|null
     * @throws LocalizedException
     */
    public function getCustomerAddressEntityType(): ?Type
    {
        return $this->getEntityType('customer_address');
    }
}
