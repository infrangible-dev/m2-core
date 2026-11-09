<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Directory;

/**
 * Regions of all countries with the option "Any".
 */
class RegionAny extends Region
{
    /**
     * Returns the regions for a select field, with the option "Any" (value 0) first.
     *
     * @param bool $isMultiselect
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionArray($isMultiselect = false): array
    {
        $result = parent::toOptionArray($isMultiselect);

        if (!$isMultiselect) {
            array_shift($result);
        }

        array_unshift($result, ['value' => 0, 'label' => __('Any')->render()]);

        return $result;
    }

    /**
     * Returns the regions as value => label, with the option "Any" (value 0).
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptions(): array
    {
        $result = parent::toOptions();

        $result += [0 => __('Any')->render()];

        return $result;
    }
}
