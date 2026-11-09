<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Directory;

use Magento\Directory\Model\Config\Source\Allregion;

/**
 * Options of the regions of all countries.
 */
class Region extends Allregion
{
    /**
     * Returns the options as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptions(): array
    {
        $result = [];

        $options = $this->toOptionArray();

        foreach ($options as $countryOptions) {
            $countryName = $countryOptions['label'];

            if (is_array($countryOptions['value'])) {
                foreach ($countryOptions['value'] as $regionOptions) {
                    $regionId = $regionOptions['value'];
                    $regionName = $regionOptions['label'];

                    $result[$regionId] = sprintf('%s [%s]', $regionName, $countryName);
                }
            }
        }

        return $result;
    }
}
