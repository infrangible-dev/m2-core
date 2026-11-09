<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Model\Config\Source\Carrier\Method;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Carrier;
use Infrangible\Core\Model\Config\Source\Carrier\Method\Active;
use Magento\OfflineShipping\Model\Carrier\Flatrate;
use PHPUnit\Framework\TestCase;

/**
 * Tests the options of the methods of the active carriers.
 */
class ActiveTest extends TestCase
{
    /**
     * Returns the source with the carrier flatrate (named "Flat Rate", methods "fixed" and "express") and the carrier
     * custom without name (method "standard").
     *
     * @return Active
     */
    private function createSource(): Active
    {
        $flatrate = $this->createStub(Flatrate::class);
        $flatrate->method('getConfigData')->willReturnMap([['name', 'Flat Rate']]);
        $flatrate->method('getAllowedMethods')->willReturn(['fixed' => 'Fixed', 'express' => 'Express']);

        $custom = $this->createStub(Flatrate::class);
        $custom->method('getConfigData')->willReturn(null);
        $custom->method('getAllowedMethods')->willReturn(['standard' => 'Standard']);

        $carrierHelper = $this->createStub(Carrier::class);
        $carrierHelper->method('getActiveCarriers')->willReturn(['flatrate' => $flatrate, 'custom' => $custom]);

        return new Active(new Variables(), $carrierHelper);
    }

    /**
     * The methods are grouped by carrier; a carrier without name is labelled with its code, its methods are labelled
     * with their title like those of other carriers.
     *
     * @return void
     */
    public function testToOptionArray(): void
    {
        $options = $this->createSource()->toOptionArray();

        $emptyOption = $options[ 0 ];
        $this->assertIsArray($emptyOption);
        $this->assertSame('', $emptyOption[ 'value' ]);
        $this->assertSame(
            [
                'label' => 'Flat Rate [flatrate]',
                'value' => [
                    ['value' => 'flatrate_fixed', 'label' => 'Fixed [fixed]'],
                    ['value' => 'flatrate_express', 'label' => 'Express [express]']
                ]
            ],
            $options[ 'flatrate' ]
        );
        $this->assertSame(
            ['label' => 'custom', 'value' => [['value' => 'custom_standard', 'label' => 'Standard [standard]']]],
            $options[ 'custom' ]
        );
    }

    /**
     * The flat list contains carrier and method in the label, also for carriers without name.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $this->assertSame(
            [
                'flatrate_fixed'   => 'Flat Rate [flatrate] Fixed [fixed]',
                'flatrate_express' => 'Flat Rate [flatrate] Express [express]',
                'custom_standard'  => 'custom Standard [standard]'
            ],
            $this->createSource()->toArray()
        );
    }
}
