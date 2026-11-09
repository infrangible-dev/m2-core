<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Carrier;

/**
 * Options of the active carriers.
 */
class Active extends AbstractActive
{
    /**
     * Returns the active carriers for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Please Select --')]];

        foreach ($this->getActiveCarriers() as $code => $carrier) {
            $options[] = [
                'value' => $code,
                'label' => $this->getCarrierLabel($code, $carrier)
            ];
        }

        return $options;
    }

    /**
     * Returns the active carriers as code => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toArray(): array
    {
        $options = [];

        foreach ($this->getActiveCarriers() as $code => $carrier) {
            $options[ $code ] = $this->getCarrierLabel($code, $carrier);
        }

        return $options;
    }
}
