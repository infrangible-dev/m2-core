<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Helper;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Carrier;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\OfflineShipping\Model\Carrier\Flatrate;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * Tests that the carriers are created with the configuration of each store.
 */
class CarrierTest extends TestCase
{
    /**
     * Returns the helper for two stores: store 1 has the carriers flatrate (active) and tablerate (inactive), store 2
     * the carriers flatrate (inactive) and freeshipping (active). The carrier "broken" has no model.
     *
     * @param Stores&Stub $storeHelper
     *
     * @return Carrier
     */
    private function createCarrier(Stub $storeHelper): Carrier
    {
        $storeHelper->method('getStoreConfig')->willReturnMap([
            ['carriers', null, false, 1, ['flatrate' => [], 'tablerate' => [], 'broken' => []]],
            ['carriers', null, false, 2, ['flatrate' => [], 'freeshipping' => []]]
        ]);

        $carrierFactory = $this->createStub(CarrierFactory::class);
        $carrierFactory->method('create')->willReturnCallback(
            function (string $carrierCode, ?int $storeId) {
                $active = [
                    '1_flatrate' => true,
                    '1_tablerate' => false,
                    '2_flatrate' => false,
                    '2_freeshipping' => true
                ];

                $key = sprintf('%d_%s', $storeId, $carrierCode);

                if (! array_key_exists($key, $active)) {
                    return false;
                }

                $carrier = $this->createStub(Flatrate::class);
                $carrier->method('isActive')->willReturn($active[ $key ]);
                $carrier->method('getCarrierCode')->willReturn($key);

                return $carrier;
            }
        );

        return new Carrier($storeHelper, $carrierFactory, new Variables());
    }

    /**
     * Only the carriers of the current store are created, with the id of that store.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetAllCarriers(): void
    {
        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStore')->willReturn($this->createConfiguredStub(Store::class, ['getId' => '1']));

        $carriers = $this->createCarrier($storeHelper)->getAllCarriers();

        $this->assertSame(['flatrate', 'tablerate'], array_keys($carriers));
        $this->assertSame('1_flatrate', $carriers[ 'flatrate' ]->getCarrierCode());
    }

    /**
     * For all stores, the carriers are read from the configuration of each store, and a carrier is listed if it is
     * active in any store.
     *
     * @return void
     */
    public function testGetActiveCarriersOfAllStores(): void
    {
        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStores')->willReturn([
            $this->createConfiguredStub(Store::class, ['getId' => '1']),
            $this->createConfiguredStub(Store::class, ['getId' => '2'])
        ]);

        $carriers = $this->createCarrier($storeHelper)->getActiveCarriers(true);

        $this->assertSame(['flatrate', 'freeshipping'], array_keys($carriers));
        $this->assertSame('1_flatrate', $carriers[ 'flatrate' ]->getCarrierCode());
        $this->assertSame('2_freeshipping', $carriers[ 'freeshipping' ]->getCarrierCode());
    }

    /**
     * Without a current store, there are no active carriers.
     *
     * @return void
     */
    public function testGetActiveCarriersWithoutStore(): void
    {
        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStore')->willThrowException(new NoSuchEntityException());

        $this->assertSame([], $this->createCarrier($storeHelper)->getActiveCarriers());
    }
}
