<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute as Attribute;

/**
 * Options of the category attributes with their code as value.
 */
class CategoryAttributeCode extends Category
{
    /**
     * Returns the value of the attribute in the options: its code.
     *
     * @param Attribute $catalogAttribute
     *
     * @return string
     */
    protected function getAttributeValue(Attribute $catalogAttribute): string
    {
        return $catalogAttribute->getAttributeCode();
    }
}
