<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\GroupFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Customer
{
    /**
     * @param LoggerInterface $logging
     * @param CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory
     * @param CollectionFactory $customerCollectionFactory
     * @param GroupFactory $customerGroupFactory
     * @param \Magento\Customer\Model\ResourceModel\GroupFactory $customerGroupResourceFactory
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupCollectionFactory
     */
    public function __construct(
        protected LoggerInterface $logging,
        protected CustomerFactory $customerFactory,
        protected \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory,
        protected CollectionFactory $customerCollectionFactory,
        protected GroupFactory $customerGroupFactory,
        protected \Magento\Customer\Model\ResourceModel\GroupFactory $customerGroupResourceFactory,
        protected \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupCollectionFactory
    ) {
    }

    /**
     * Creates a new customer.
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function newCustomer(): \Magento\Customer\Model\Customer
    {
        return $this->customerFactory->create();
    }

    /**
     * Loads the customer.
     *
     * @param int $customerId
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function loadCustomer(int $customerId): \Magento\Customer\Model\Customer
    {
        $customer = $this->newCustomer();

        $this->customerResourceFactory->create()->load(
            $customer,
            $customerId
        );

        return $customer;
    }

    /**
     * Loads the customer by email.
     *
     * @param string $customerEmail
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function loadCustomerByEmail(string $customerEmail): \Magento\Customer\Model\Customer
    {
        $customer = $this->customerFactory->create();

        try {
            $customer->loadByEmail($customerEmail);
        } catch (LocalizedException $exception) {
            $this->logging->error($exception);
        }

        return $customer;
    }

    /**
     * Saves the customer.
     *
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return void
     * @throws Exception
     */
    public function saveCustomer(\Magento\Customer\Model\Customer $customer): void
    {
        $this->customerResourceFactory->create()->save($customer);
    }

    /**
     * Returns the customer collection.
     *
     * @return Collection
     */
    public function getCustomerCollection(): Collection
    {
        return $this->customerCollectionFactory->create();
    }

    /**
     * Creates a new customer group.
     *
     * @return Group
     */
    public function newCustomerGroup(): Group
    {
        return $this->customerGroupFactory->create();
    }

    /**
     * Loads the customer group.
     *
     * @param int $customerGroupId
     *
     * @return Group
     */
    public function loadCustomerGroup(int $customerGroupId): Group
    {
        $group = $this->newCustomerGroup();

        $this->customerResourceFactory->create()->load(
            $group,
            $customerGroupId
        );

        return $group;
    }

    /**
     * Saves the customer group.
     *
     * @param Group $group
     *
     * @return void
     * @throws Exception
     */
    public function saveCustomerGroup(Group $group): void
    {
        $this->customerGroupResourceFactory->create()->save($group);
    }

    /**
     * Returns the customer group collection.
     *
     * @return \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    public function getCustomerGroupCollection(): \Magento\Customer\Model\ResourceModel\Group\Collection
    {
        return $this->customerGroupCollectionFactory->create();
    }
}
