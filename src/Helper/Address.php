<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\ResourceModel\Address\Collection;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;

class Address
{
    /** @var AddressFactory */
    protected AddressFactory $addressFactory;

    /** @var \Magento\Customer\Model\ResourceModel\AddressFactory */
    protected \Magento\Customer\Model\ResourceModel\AddressFactory $addressResourceFactory;

    /** @var CollectionFactory */
    private CollectionFactory $addressCollectionFactory;

    /** @var CountryFactory */
    protected CountryFactory $countryFactory;

    /** @var \Magento\Directory\Model\ResourceModel\CountryFactory */
    protected \Magento\Directory\Model\ResourceModel\CountryFactory $countryResourceFactory;

    /** @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory */
    protected \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory;

    /**
     * @param AddressFactory $addressFactory
     * @param \Magento\Customer\Model\ResourceModel\AddressFactory $addressResourceFactory
     * @param CollectionFactory $addressCollectionFactory
     * @param CountryFactory $countryFactory
     * @param \Magento\Directory\Model\ResourceModel\CountryFactory $countryResourceFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     */
    public function __construct(
        AddressFactory $addressFactory,
        \Magento\Customer\Model\ResourceModel\AddressFactory $addressResourceFactory,
        CollectionFactory $addressCollectionFactory,
        CountryFactory $countryFactory,
        \Magento\Directory\Model\ResourceModel\CountryFactory $countryResourceFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
    ) {
        $this->addressFactory = $addressFactory;
        $this->addressResourceFactory = $addressResourceFactory;
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->countryFactory = $countryFactory;
        $this->countryResourceFactory = $countryResourceFactory;
        $this->countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * Creates a new address.
     *
     * @return \Magento\Customer\Model\Address
     */
    public function newAddress(): \Magento\Customer\Model\Address
    {
        return $this->addressFactory->create();
    }

    /**
     * Loads the address.
     *
     * @param int $addressId
     *
     * @return \Magento\Customer\Model\Address
     */
    public function loadAddress(int $addressId): \Magento\Customer\Model\Address
    {
        $address = $this->newAddress();

        $this->addressResourceFactory->create()->load($address, $addressId);

        return $address;
    }

    /**
     * Saves the address.
     *
     * @param \Magento\Customer\Model\Address $address
     *
     * @return void
     * @throws Exception
     */
    public function saveAddress(\Magento\Customer\Model\Address $address): void
    {
        $this->addressResourceFactory->create()->save($address);
    }

    /**
     * Returns the address collection.
     *
     * @return Collection
     */
    public function getAddressCollection(): Collection
    {
        return $this->addressCollectionFactory->create();
    }

    /**
     * Creates a new country.
     *
     * @return Country
     */
    public function newCountry(): Country
    {
        return $this->countryFactory->create();
    }

    /**
     * Loads the country.
     *
     * @param int $countryId
     *
     * @return Country
     */
    public function loadCountry(int $countryId): Country
    {
        $country = $this->newCountry();

        $this->countryResourceFactory->create()->load($country, $countryId);

        return $country;
    }

    /**
     * Saves the country.
     *
     * @param Country $country
     *
     * @return void
     * @throws Exception
     */
    public function saveCountry(Country $country): void
    {
        $this->countryResourceFactory->create()->save($country);
    }

    /**
     * Returns the country collection.
     *
     * @return \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    public function getCountryCollection(): \Magento\Directory\Model\ResourceModel\Country\Collection
    {
        return $this->countryCollectionFactory->create();
    }
}
