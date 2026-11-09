<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source;

use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Base class of source models with options of the EAV entity types customer, address, category and product. By
 * default only the product options are listed.
 */
abstract class Eav implements OptionSourceInterface
{
    /** @var bool */
    private bool $customer = false;

    /** @var bool */
    private bool $address = false;

    /** @var bool */
    private bool $category = false;

    /** @var bool */
    private bool $product = true;

    /**
     * Checks whether the options of the customer entity type are listed.
     *
     * @return bool
     */
    public function isCustomer(): bool
    {
        return $this->customer;
    }

    /**
     * Sets whether the options of the customer entity type are listed.
     *
     * @param bool $customer
     *
     * @return void
     */
    public function setCustomer(bool $customer = true): void
    {
        $this->customer = $customer;
    }

    /**
     * Checks whether the options of the address entity type are listed.
     *
     * @return bool
     */
    public function isAddress(): bool
    {
        return $this->address;
    }

    /**
     * Sets whether the options of the address entity type are listed.
     *
     * @param bool $address
     *
     * @return void
     */
    public function setAddress(bool $address = true): void
    {
        $this->address = $address;
    }

    /**
     * Checks whether the options of the category entity type are listed.
     *
     * @return bool
     */
    public function isCategory(): bool
    {
        return $this->category;
    }

    /**
     * Sets whether the options of the category entity type are listed.
     *
     * @param bool $category
     *
     * @return void
     */
    public function setCategory(bool $category = true): void
    {
        $this->category = $category;
    }

    /**
     * Checks whether the options of the product entity type are listed.
     *
     * @return bool
     */
    public function isProduct(): bool
    {
        return $this->product;
    }

    /**
     * Sets whether the options of the product entity type are listed.
     *
     * @param bool $product
     *
     * @return void
     */
    public function setProduct(bool $product = true): void
    {
        $this->product = $product;
    }

    /**
     * Returns the options for a select field for the given entity types.
     *
     * @param bool $customer
     * @param bool $address
     * @param bool $category
     * @param bool $product
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionArrayWithEntities(bool $customer, bool $address, bool $category, bool $product): array
    {
        $oldCustomer = $this->isCustomer();
        $oldAddress = $this->isAddress();
        $oldCategory = $this->isCategory();
        $oldProduct = $this->isProduct();

        $this->setCustomer($customer);
        $this->setAddress($address);
        $this->setCategory($category);
        $this->setProduct($product);

        $result = $this->toOptionArray();

        $this->setCustomer($oldCustomer);
        $this->setAddress($oldAddress);
        $this->setCategory($oldCategory);
        $this->setProduct($oldProduct);

        return $result;
    }

    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    abstract public function toOptionArray(): array;

    /**
     * Returns the options as value => label for the given entity types.
     *
     * @param bool $customer
     * @param bool $address
     * @param bool $category
     * @param bool $product
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionsWithEntities(bool $customer, bool $address, bool $category, bool $product): array
    {
        $oldCustomer = $this->isCustomer();
        $oldAddress = $this->isAddress();
        $oldCategory = $this->isCategory();
        $oldProduct = $this->isProduct();

        $this->setCustomer($customer);
        $this->setAddress($address);
        $this->setCategory($category);
        $this->setProduct($product);

        $result = $this->toOptions();

        $this->setCustomer($oldCustomer);
        $this->setAddress($oldAddress);
        $this->setCategory($oldCategory);
        $this->setProduct($oldProduct);

        return $result;
    }

    /**
     * Returns the options as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    abstract public function toOptions(): array;

    /**
     * Returns the id of the entity type, or an empty string if it does not exist.
     *
     * @param Type|null $entityType
     *
     * @return string
     */
    protected function getEntityTypeId(?Type $entityType): string
    {
        $entityTypeId = $entityType?->getId();

        return is_scalar($entityTypeId) ? (string)$entityTypeId : '';
    }
}
