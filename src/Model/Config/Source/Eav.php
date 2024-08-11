<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Eav implements OptionSourceInterface
{
    /** @var bool */
    private $customer = false;

    /** @var bool */
    private $address = false;

    /** @var bool */
    private $category = false;

    /** @var bool */
    private $product = true;

    public function isCustomer(): bool
    {
        return $this->customer;
    }

    public function setCustomer(bool $customer = true): void
    {
        $this->customer = $customer;
    }

    public function isAddress(): bool
    {
        return $this->address;
    }

    public function setAddress(bool $address = true): void
    {
        $this->address = $address;
    }

    public function isCategory(): bool
    {
        return $this->category;
    }

    public function setCategory(bool $category = true): void
    {
        $this->category = $category;
    }

    public function isProduct(): bool
    {
        return $this->product;
    }

    public function setProduct(bool $product = true): void
    {
        $this->product = $product;
    }

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

    abstract public function toOptionArray(): array;

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

    abstract public function toOptions(): array;
}
