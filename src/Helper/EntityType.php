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

    /**
     * @param Config   $eavConfig
     * @param Database $databaseHelper
     */
    public function __construct(Config $eavConfig, Database $databaseHelper)
    {
        $this->eavConfig = $eavConfig;
        $this->databaseHelper = $databaseHelper;
    }

    /**
     * @param string $entityTypeCode
     *
     * @return Type|null
     * @throws LocalizedException
     */
    public function getEntityType(string $entityTypeCode): ?Type
    {
        if (array_key_exists($entityTypeCode, $this->entityTypes)) {
            return $this->entityTypes[$entityTypeCode];
        }

        $entityType = $this->eavConfig->getEntityType($entityTypeCode);

        if (!empty($entityType)) {
            $this->entityTypes[$entityTypeCode] = $entityType;

            return $entityType;
        }

        return null;
    }

    /**
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
     * @param Type $entityType
     *
     * @return string
     */
    public function getEntityTypeTableByEntityType(Type $entityType): string
    {
        return $this->databaseHelper->getTableName($entityType->getEntityTable());
    }

    /**
     * @return Type
     * @throws LocalizedException
     */
    public function getCategoryEntityType(): ?Type
    {
        return $this->getEntityType(Category::ENTITY);
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function getCategoryEntityTypeId(): ?int
    {
        $categoryEntityType = $this->getCategoryEntityType();

        return empty($categoryEntityType) ? null : $categoryEntityType->getId();
    }

    /**
     * @return string|null
     * @throws LocalizedException
     */
    public function getCategoryEntityTypeTable(): ?string
    {
        return $this->getEntityTypeTableByEntityType($this->getCategoryEntityType());
    }

    /**
     * @return Type
     * @throws LocalizedException
     */
    public function getProductEntityType(): ?Type
    {
        return $this->getEntityType(Product::ENTITY);
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function getProductEntityTypeId(): ?int
    {
        $productEntityType = $this->getProductEntityType();

        return empty($productEntityType) ? null : $productEntityType->getId();
    }

    /**
     * @return string|null
     * @throws LocalizedException
     */
    public function getProductEntityTypeTable(): ?string
    {
        return $this->getEntityTypeTableByEntityType($this->getProductEntityType());
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function getProductEntityTypeDefaultAttributeSetId(): ?int
    {
        $productEntityType = $this->getProductEntityType();

        return empty($productEntityType) ? null : (int) $productEntityType->getDefaultAttributeSetId();
    }

    /**
     * @return Type
     * @throws LocalizedException
     */
    public function getCustomerEntityType(): ?Type
    {
        return $this->getEntityType(Customer::ENTITY);
    }

    /**
     * @return Type
     * @throws LocalizedException
     */
    public function getCustomerAddressEntityType(): ?Type
    {
        return $this->getEntityType('customer_address');
    }
}
