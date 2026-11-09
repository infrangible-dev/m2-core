<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source;

use Infrangible\Core\Helper\Instances;
use Magento\CatalogRule\Model\Rule\Condition\Product;
use Magento\Framework\Data\OptionSourceInterface;
use RuntimeException;

/**
 * The operators of catalog price rule conditions. Requires the optional module Magento_CatalogRule, which is only
 * loaded when the options are requested.
 */
class Operator implements OptionSourceInterface
{
    /**
     * @param Instances $instanceHelper
     */
    public function __construct(
        protected Instances $instanceHelper
    ) {
    }

    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int, array{value: string, label: mixed}>
     * @throws RuntimeException if Magento_CatalogRule is not installed
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('--Please Select--')]];

        foreach ($this->toOptions() as $operator => $label) {
            $options[] = ['value' => (string)$operator, 'label' => $label];
        }

        return $options;
    }

    /**
     * Returns the options as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     * @throws RuntimeException if Magento_CatalogRule is not installed
     */
    public function toOptions(): array
    {
        return $this->instanceHelper->createInstance(Product::class)->getDefaultOperatorOptions();
    }
}
