<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Eav\Model\Entity\Attribute;

/**
 * Options of the customer address attributes with their code as value.
 */
class AddressAttributeCode extends Address
{
    /**
     * Returns the value of the attribute in the options: its code.
     *
     * @param Attribute $customerAttribute
     *
     * @return string
     */
    protected function getAttributeValue(Attribute $customerAttribute): string
    {
        return $customerAttribute->getAttributeCode();
    }
}
