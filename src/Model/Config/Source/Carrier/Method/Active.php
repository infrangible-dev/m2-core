<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Carrier\Method;

use Infrangible\Core\Model\Config\Source\Carrier\AbstractActive;
use Magento\Shipping\Model\Carrier\CarrierInterface;

/**
 * Options of the methods of the active carriers.
 */
class Active extends AbstractActive
{
    /**
     * Returns the methods of the active carriers for a select field, grouped by carrier. The values are
     * "carrier code_method code".
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Please Select --')]];

        foreach ($this->getActiveCarriers() as $code => $carrier) {
            $methods = [];

            if ($carrier instanceof CarrierInterface) {
                foreach ($carrier->getAllowedMethods() as $methodCode => $methodTitle) {
                    $methods[] = [
                        'value' => sprintf(
                            '%s_%s',
                            $code,
                            $methodCode
                        ),
                        'label' => $this->getMethodLabel((string)$methodCode, $methodTitle)
                    ];
                }
            }

            $options[ $code ] = ['label' => $this->getCarrierLabel($code, $carrier), 'value' => $methods];
        }

        return $options;
    }

    /**
     * Returns the methods of the active carriers as "carrier code_method code" => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toArray(): array
    {
        $options = [];

        foreach ($this->getActiveCarriers() as $code => $carrier) {
            if ($carrier instanceof CarrierInterface) {
                $carrierLabel = $this->getCarrierLabel($code, $carrier);

                foreach ($carrier->getAllowedMethods() as $methodCode => $methodTitle) {
                    $value = sprintf(
                        '%s_%s',
                        $code,
                        $methodCode
                    );

                    $options[ $value ] = sprintf(
                        '%s %s',
                        $carrierLabel,
                        $this->getMethodLabel((string)$methodCode, $methodTitle)
                    );
                }
            }
        }

        return $options;
    }

    /**
     * Returns the label of a method: its title with its code, or only its code if it has no title.
     *
     * @param string $methodCode
     * @param mixed $methodTitle
     *
     * @return string
     */
    private function getMethodLabel(string $methodCode, mixed $methodTitle): string
    {
        $title = $this->variables->stringValueOrDefault($methodTitle);

        return $title === '' ? $methodCode : sprintf('%s [%s]', $title, $methodCode);
    }
}
