<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Variables;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Shipping\Model\Carrier\AbstractCarrierInterface;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Store\Api\Data\StoreInterface;

class Carrier
{
    /**
     * @param Stores $storeHelper
     * @param CarrierFactory $carrierFactory
     * @param Variables $variables
     */
    public function __construct(
        protected Stores $storeHelper,
        protected CarrierFactory $carrierFactory,
        protected Variables $variables
    ) {
    }

    /**
     * Returns the models of all configured shipping carriers of the current store.
     *
     * @return AbstractCarrierInterface[] carrier code => carrier
     * @throws NoSuchEntityException
     */
    public function getAllCarriers(): array
    {
        $carriers = [];

        $store = $this->storeHelper->getStore();

        foreach ($this->getStoreCarriers($store) as $carrierCode => $model) {
            $carriers[ $carrierCode ] = $model;
        }

        return $carriers;
    }

    /**
     * Returns the models of the active shipping carriers of the current store or of all stores.
     *
     * @param bool $allStores
     * @param bool $withDefault
     *
     * @return AbstractCarrierInterface[] carrier code => carrier
     */
    public function getActiveCarriers(bool $allStores = false, bool $withDefault = true): array
    {
        $carriers = [];

        if ($allStores) {
            $stores = $this->storeHelper->getStores($withDefault);
        } else {
            try {
                $stores = [$this->storeHelper->getStore()];
            } catch (NoSuchEntityException) {
                $stores = [];
            }
        }

        foreach ($stores as $store) {
            foreach ($this->getStoreCarriers($store) as $carrierCode => $model) {
                if ($model->isActive()) {
                    $carriers[ $carrierCode ] = $model;
                }
            }
        }

        return $carriers;
    }

    /**
     * Creates the models of the carriers configured in the store.
     *
     * @param StoreInterface $store
     *
     * @return array<string, AbstractCarrierInterface> carrier code => carrier
     */
    private function getStoreCarriers(StoreInterface $store): array
    {
        $carriers = [];

        $storeId = $this->variables->intValue($store->getId());

        $carriersConfig = $this->storeHelper->getStoreConfig('carriers', null, false, $storeId);

        foreach ($this->getCarrierCodes($carriersConfig) as $carrierCode) {
            $model = $this->carrierFactory->create($carrierCode, $storeId);

            if ($model instanceof AbstractCarrierInterface) {
                $carriers[ $carrierCode ] = $model;
            }
        }

        return $carriers;
    }

    /**
     * Returns the carrier codes of the carriers config.
     *
     * @param mixed $config
     *
     * @return string[]
     */
    private function getCarrierCodes(mixed $config): array
    {
        return is_array($config) ? array_map(
            'strval',
            array_keys($config)
        ) : [];
    }
}
