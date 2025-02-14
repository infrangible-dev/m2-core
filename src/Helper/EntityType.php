<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Exception\LocalizedException;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class EntityType
{
    /** @var Database */
    protected $databaseHelper;

    /** @var Config */
    private $eavConfig;

    /** @var array */
    private $entityTypes = [];

    public function __construct(Config $eavConfig, Database $databaseHelper)
    {
        $this->eavConfig = $eavConfig;
        $this->databaseHelper = $databaseHelper;
    }

    /**
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
     * @throws LocalizedException
     */
    public function getEntityTypeTableByEntityTypeCode(string $entityTypeCode): ?string
    {
        $entityType = $this->getEntityType($entityTypeCode);

        return $entityType !== null ? $this->getEntityTypeTableByEntityType($entityType) : null;
    }

    public function getEntityTypeTableByEntityType(Type $entityType): string
    {
        return $this->databaseHelper->getTableName($entityType->getEntityTable());
    }

    /**
     * @throws LocalizedException
     */
    public function getCategoryEntityType(): ?Type
    {
        return $this->getEntityType(Category::ENTITY);
    }

    /**
     * @throws LocalizedException
     */
    public function getCategoryEntityTypeId(): ?int
    {
        $categoryEntityType = $this->getCategoryEntityType();

        return empty($categoryEntityType) ? null : $categoryEntityType->getId();
    }

    /**
     * @throws LocalizedException
     */
    public function getCategoryEntityTypeTable(): ?string
    {
        return $this->getEntityTypeTableByEntityType($this->getCategoryEntityType());
    }

    /**
     * @throws LocalizedException
     */
    public function getProductEntityType(): ?Type
    {
        return $this->getEntityType(Product::ENTITY);
    }

    /**
     * @throws LocalizedException
     */
    public function getProductEntityTypeId(): ?int
    {
        $productEntityType = $this->getProductEntityType();

        return empty($productEntityType) ? null : $productEntityType->getId();
    }

    /**
     * @throws LocalizedException
     */
    public function getProductEntityTypeTable(): ?string
    {
        return $this->getEntityTypeTableByEntityType($this->getProductEntityType());
    }

    /**
     * @throws LocalizedException
     */
    public function getProductEntityTypeDefaultAttributeSetId(): ?int
    {
        $productEntityType = $this->getProductEntityType();

        return empty($productEntityType) ? null : (int)$productEntityType->getDefaultAttributeSetId();
    }

    /**
     * @throws LocalizedException
     */
    public function getCustomerEntityType(): ?Type
    {
        return $this->getEntityType(Customer::ENTITY);
    }

    /**
     * @throws LocalizedException
     */
    public function getCustomerAddressEntityType(): ?Type
    {
        return $this->getEntityType('customer_address');
    }
}
