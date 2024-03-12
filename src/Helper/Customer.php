<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\GroupFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Customer
{
    /** @var LoggerInterface */
    protected $logging;

    /** @var CustomerFactory */
    protected $customerFactory;

    /** @var \Magento\Customer\Model\ResourceModel\CustomerFactory */
    protected $customerResourceFactory;

    /** @var CollectionFactory */
    protected $customerCollectionFactory;

    /** @var GroupFactory */
    protected $customerGroupFactory;

    /** @var \Magento\Customer\Model\ResourceModel\GroupFactory */
    protected $customerGroupResourceFactory;

    /** @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory */
    protected $customerGroupCollectionFactory;

    /**
     * @param LoggerInterface                                               $logging
     * @param CustomerFactory                                               $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\CustomerFactory         $customerResourceFactory
     * @param CollectionFactory                                             $customerCollectionFactory
     * @param GroupFactory                                                  $customerGroupFactory
     * @param \Magento\Customer\Model\ResourceModel\GroupFactory            $customerGroupResourceFactory
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupCollectionFactory
     */
    public function __construct(
        LoggerInterface $logging,
        CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory,
        CollectionFactory $customerCollectionFactory,
        GroupFactory $customerGroupFactory,
        \Magento\Customer\Model\ResourceModel\GroupFactory $customerGroupResourceFactory,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupCollectionFactory)
    {
        $this->logging = $logging;
        $this->customerFactory = $customerFactory;
        $this->customerResourceFactory = $customerResourceFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->customerGroupFactory = $customerGroupFactory;
        $this->customerGroupResourceFactory = $customerGroupResourceFactory;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    public function newCustomer(): \Magento\Customer\Model\Customer
    {
        return $this->customerFactory->create();
    }

    /**
     * @param int $customerId
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function loadCustomer(int $customerId): \Magento\Customer\Model\Customer
    {
        $customer = $this->newCustomer();

        $this->customerResourceFactory->create()->load($customer, $customerId);

        return $customer;
    }

    /**
     * @param string $customerEmail
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function loadCustomerByEmail(string $customerEmail): \Magento\Customer\Model\Customer
    {
        $customer = $this->customerFactory->create();

        try {
            $customer->loadByEmail($customerEmail);
        } /** @noinspection PhpRedundantCatchClauseInspection,RedundantSuppression */ catch (LocalizedException $exception) {
            $this->logging->error($exception);
        }

        return $customer;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @throws AlreadyExistsException
     */
    public function saveCustomer(\Magento\Customer\Model\Customer $customer)
    {
        $this->customerResourceFactory->create()->save($customer);
    }

    /**
     * @return Collection
     */
    public function getCustomerCollection(): Collection
    {
        return $this->customerCollectionFactory->create();
    }

    /**
     * @return Group
     */
    public function newCustomerGroup(): Group
    {
        return $this->customerGroupFactory->create();
    }

    /**
     * @param int $customerGroupId
     *
     * @return Group
     */
    public function loadCustomerGroup(int $customerGroupId): Group
    {
        $group = $this->newCustomerGroup();

        $this->customerResourceFactory->create()->load($group, $customerGroupId);

        return $group;
    }

    /**
     * @param Group $group
     *
     * @throws Exception
     */
    public function saveCustomerGroup(Group $group)
    {
        $this->customerResourceFactory->create()->save($group);
    }

    /**
     * @return \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    public function getCustomerGroupCollection(): \Magento\Customer\Model\ResourceModel\Group\Collection
    {
        return $this->customerGroupCollectionFactory->create();
    }
}
