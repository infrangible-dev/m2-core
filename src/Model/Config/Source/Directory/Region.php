<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Directory;

use Magento\Directory\Model\Config\Source\Allregion;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Region
    extends Allregion
{
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
