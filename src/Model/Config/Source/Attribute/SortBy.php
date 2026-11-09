<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Catalog\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options of the product attributes that can be used for sorting in the catalog.
 */
class SortBy implements OptionSourceInterface
{
    /**
     * @param Config $catalogConfig
     */
    public function __construct(
        protected Config $catalogConfig
    ) {
    }

    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int, array{value: int|string, label: mixed}>
     */
    public function toOptionArray(): array
    {
        $attributeOrder = [['value' => '', 'label' => '-- Please select --']];

        foreach ($this->catalogConfig->getAttributeUsedForSortByArray() as $attributeCode => $attributeLabel) {
            $attributeOrder[] = ['value' => $attributeCode, 'label' => $attributeLabel];
        }

        return $attributeOrder;
    }

    /**
     * Returns the options as attribute code => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptions(): array
    {
        return $this->catalogConfig->getAttributeUsedForSortByArray();
    }
}
