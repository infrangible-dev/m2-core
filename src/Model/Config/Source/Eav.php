<?php

namespace Infrangible\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Eav
    implements OptionSourceInterface
{
    /** @var bool */
    private $customer = false;

    /** @var bool */
    private $address = false;

    /** @var bool */
    private $category = false;

    /** @var bool */
    private $product = true;

    /**
     * @return bool
     */
    public function isCustomer(): bool
    {
        return $this->customer;
    }

    /**
     * @param bool $customer
     */
    public function setCustomer(bool $customer = true)
    {
        $this->customer = $customer;
    }

    /**
     * @return bool
     */
    public function isAddress(): bool
    {
        return $this->address;
    }

    /**
     * @param bool $address
     */
    public function setAddress(bool $address = true)
    {
        $this->address = $address;
    }

    /**
     * @return bool
     */
    public function isCategory(): bool
    {
        return $this->category;
    }

    /**
     * @param bool $category
     */
    public function setCategory(bool $category = true)
    {
        $this->category = $category;
    }

    /**
     * @return bool
     */
    public function isProduct(): bool
    {
        return $this->product;
    }

    /**
     * @param bool $product
     */
    public function setProduct(bool $product = true)
    {
        $this->product = $product;
    }

    /**
     * @param bool $customer
     * @param bool $address
     * @param bool $category
     * @param bool $product
     *
     * @return array
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
     * @return array
     */
    abstract public function toOptionArray(): array;

    /**
     * @param bool $customer
     * @param bool $address
     * @param bool $category
     * @param bool $product
     *
     * @return array
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
     * @return array
     */
    abstract public function toOptions(): array;
}
