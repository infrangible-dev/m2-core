<?php

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\ResourceModel\Address\Collection;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Address
{
    /** @var AddressFactory */
    protected $addressFactory;

    /** @var \Magento\Customer\Model\ResourceModel\AddressFactory */
    protected $addressResourceFactory;

    /** @var CollectionFactory */
    private $addressCollectionFactory;

    /** @var CountryFactory */
    protected $countryFactory;

    /** @var \Magento\Directory\Model\ResourceModel\CountryFactory */
    protected $countryResourceFactory;

    /** @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory */
    protected $countryCollectionFactory;

    /**
     * @param AddressFactory                                                   $addressFactory
     * @param \Magento\Customer\Model\ResourceModel\AddressFactory             $addressResourceFactory
     * @param CollectionFactory                                                $addressCollectionFactory
     * @param CountryFactory                                                   $countryFactory
     * @param \Magento\Directory\Model\ResourceModel\CountryFactory            $countryResourceFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     */
    public function __construct(
        AddressFactory $addressFactory,
        \Magento\Customer\Model\ResourceModel\AddressFactory $addressResourceFactory,
        CollectionFactory $addressCollectionFactory,
        CountryFactory $countryFactory,
        \Magento\Directory\Model\ResourceModel\CountryFactory $countryResourceFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory)
    {
        $this->addressFactory = $addressFactory;
        $this->addressResourceFactory = $addressResourceFactory;
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->countryFactory = $countryFactory;
        $this->countryResourceFactory = $countryResourceFactory;
        $this->countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * @return \Magento\Customer\Model\Address
     */
    public function newAddress(): \Magento\Customer\Model\Address
    {
        return $this->addressFactory->create();
    }

    /**
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
     * @param \Magento\Customer\Model\Address $address
     *
     * @throws Exception
     */
    public function saveAddress(\Magento\Customer\Model\Address $address)
    {
        $this->addressResourceFactory->create()->save($address);
    }

    /**
     * @return  Collection
     */
    public function getAddressCollection(): Collection
    {
        return $this->addressCollectionFactory->create();
    }

    /**
     * @return Country
     */
    public function newCountry(): Country
    {
        return $this->countryFactory->create();
    }

    /**
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
     * @param Country $country
     *
     * @throws Exception
     */
    public function saveCountry(Country $country)
    {
        $this->countryResourceFactory->create()->save($country);
    }

    /**
     * @return \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    public function getCountryCollection(): \Magento\Directory\Model\ResourceModel\Country\Collection
    {
        return $this->countryCollectionFactory->create();
    }
}
