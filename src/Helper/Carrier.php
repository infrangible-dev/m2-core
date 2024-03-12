<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Shipping\Model\Carrier\AbstractCarrierInterface;
use Magento\Shipping\Model\CarrierFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Carrier
{
    /** @var Stores */
    protected $storeHelper;

    /** @var CarrierFactory */
    protected $carrierFactory;

    /**
     * @param Stores         $storeHelper
     * @param CarrierFactory $carrierFactory
     */
    public function __construct(Stores $storeHelper, CarrierFactory $carrierFactory)
    {
        $this->storeHelper = $storeHelper;

        $this->carrierFactory = $carrierFactory;
    }

    /**
     * @return AbstractCarrierInterface[]
     * @throws NoSuchEntityException
     */
    public function getAllCarriers(): array
    {
        $carriers = [];

        $config = $this->storeHelper->getStoreConfig('carriers');

        $store = $this->storeHelper->getStore();

        foreach (array_keys($config) as $carrierCode) {
            $model = $this->carrierFactory->create($carrierCode, $store);

            if ($model) {
                $carriers[ $carrierCode ] = $model;
            }
        }

        return $carriers;
    }

    /**
     * @return AbstractCarrierInterface[]
     */
    public function getActiveCarriers($allStores = false, $withDefault = true): array
    {
        $carriers = [];

        $stores = [];

        if ($allStores) {
            $stores = $this->storeHelper->getStores($withDefault);
        } else {
            try {
                $stores[] = $this->storeHelper->getStore();
            } catch (NoSuchEntityException $exception) {
            }
        }

        foreach ($stores as $store) {
            $config = $this->storeHelper->getStoreConfig('carriers');

            foreach (array_keys($config) as $carrierCode) {
                $model = $this->carrierFactory->create($carrierCode, $store);

                if ($model && $model->getConfigFlag('active')) {
                    $carriers[ $carrierCode ] = $model;
                }
            }
        }

        return $carriers;
    }
}
