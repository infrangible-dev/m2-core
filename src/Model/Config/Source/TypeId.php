<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options of the standard product types.
 */
class TypeId implements OptionSourceInterface
{
    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('--Please Select--')],
            ['value' => 'bundle', 'label' => __('Bundle')],
            ['value' => 'configurable', 'label' => __('Configurable')],
            ['value' => 'downloadable', 'label' => __('Downloadable')],
            ['value' => 'grouped', 'label' => __('Grouped')],
            ['value' => 'simple', 'label' => __('Simple')],
            ['value' => 'virtual', 'label' => __('Virtual')]
        ];
    }

    /**
     * Returns the options as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptions(): array
    {
        return [
            'bundle'       => __('Bundle'),
            'configurable' => __('Configurable'),
            'downloadable' => __('Downloadable'),
            'grouped'      => __('Grouped'),
            'simple'       => __('Simple'),
            'virtual'      => __('Virtual')
        ];
    }
}
