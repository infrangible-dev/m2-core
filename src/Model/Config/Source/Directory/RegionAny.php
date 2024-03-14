<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Directory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class RegionAny
    extends Region
{
    public function toOptionArray($isMultiselect = false): array
    {
        $result = parent::toOptionArray($isMultiselect);

        if (!$isMultiselect) {
            array_shift($result);
        }

        array_unshift($result, ['value' => 0, 'label' => __('Any')->render()]);

        return $result;
    }

    public function toOptions(): array
    {
        $result = parent::toOptions();

        $result += [0 => __('Any')->render()];

        return $result;
    }
}
