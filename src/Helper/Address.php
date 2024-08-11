<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\ResourceModel\Address\Collection;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
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

    public function newAddress(): \Magento\Customer\Model\Address
    {
        return $this->addressFactory->create();
    }

    public function loadAddress(int $addressId): \Magento\Customer\Model\Address
    {
        $address = $this->newAddress();

        $this->addressResourceFactory->create()->load($address, $addressId);

        return $address;
    }

    /**
     * @throws Exception
     */
    public function saveAddress(\Magento\Customer\Model\Address $address): void
    {
        $this->addressResourceFactory->create()->save($address);
    }

    public function getAddressCollection(): Collection
    {
        return $this->addressCollectionFactory->create();
    }

    public function newCountry(): Country
    {
        return $this->countryFactory->create();
    }

    public function loadCountry(int $countryId): Country
    {
        $country = $this->newCountry();

        $this->countryResourceFactory->create()->load($country, $countryId);

        return $country;
    }

    /**
     * @throws Exception
     */
    public function saveCountry(Country $country): void
    {
        $this->countryResourceFactory->create()->save($country);
    }

    public function getCountryCollection(): \Magento\Directory\Model\ResourceModel\Country\Collection
    {
        return $this->countryCollectionFactory->create();
    }
}
