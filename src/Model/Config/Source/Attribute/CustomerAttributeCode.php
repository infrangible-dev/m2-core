<?php

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Eav\Model\Entity\Attribute;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CustomerAttributeCode
    extends Customer
{
    /**
     * @param Attribute $customerAttribute
     *
     * @return string
     */
    protected function getAttributeValue(Attribute $customerAttribute): string
    {
        return $customerAttribute->getAttributeCode();
    }
}
