<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Product;

/**
 * Options of the product types with the option "All Product Types" (value "all").
 */
class Type
{
    /** @var \Magento\Catalog\Model\Product\Type */
    protected \Magento\Catalog\Model\Product\Type $productType;

    /**
     * @param \Magento\Catalog\Model\Product\Type $productType
     */
    public function __construct(\Magento\Catalog\Model\Product\Type $productType)
    {
        $this->productType = $productType;
    }

    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionArray(): array
    {
        $options = [['value' => 'all', 'label' => __('All Product Types')]];

        foreach ($this->productType->getOptionArray() as $typeId => $label) {
            $options[] = [
                'value' => $typeId,
                'label' => $label
            ];
        }

        return $options;
    }

    /**
     * Returns the options as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toArray(): array
    {
        $options = ['all' => __('All Product Types')];

        foreach ($this->productType->getOptionArray() as $typeId => $label) {
            $options[ $typeId ] = $label;
        }

        return $options;
    }
}
