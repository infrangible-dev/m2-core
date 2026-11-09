<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Fixture;

use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

/**
 * A shipping carrier of Magento_Shipping for stubs in tests, like the carriers of Magento_OfflineShipping, which this
 * module does not require.
 */
abstract class ShippingCarrier extends AbstractCarrier implements CarrierInterface
{
}
