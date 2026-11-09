<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\AttributeSet\Product;

use Infrangible\Core\Model\Config\Source\AttributeSet\Product;
use Magento\Framework\Exception\LocalizedException;

/**
 * Options of the product attribute sets for a multiselect field (without "--Please Select--").
 */
class Multiselect extends Product
{
    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $this->setAddPleaseSelect(false);

        return parent::toOptionArray();
    }
}
