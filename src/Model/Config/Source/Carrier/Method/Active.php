<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Carrier\Method;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Carrier;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Shipping\Model\Carrier\CarrierInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Active implements OptionSourceInterface
{
    /** @var Variables */
    protected $variables;

    /** @var Carrier */
    protected $carrierHelper;

    /** @var bool */
    private $allStores = false;

    /** @var bool */
    private $withDefault = true;

    public function __construct(Variables $variables, Carrier $carrierHelper)
    {
        $this->variables = $variables;
        $this->carrierHelper = $carrierHelper;
    }

    public function toOptionArray(): array
    {
        $activeCarriers = $this->carrierHelper->getActiveCarriers(
            $this->isAllStores(),
            $this->isWithDefault()
        );

        $options = [['value' => '', 'label' => __('-- Please Select --')]];

        foreach ($activeCarriers as $code => $carrier) {
            $name = $carrier->getConfigData('name');

            $label = $this->variables->isEmpty($name) ? $code : sprintf(
                '%s [%s]',
                $name,
                $code
            );

            $options[ $code ] = ['label' => $label, 'value' => []];

            if ($carrier instanceof CarrierInterface) {
                foreach ($carrier->getAllowedMethods() as $methodCode => $methodTitle) {
                    $value = sprintf(
                        '%s_%s',
                        $code,
                        $methodCode
                    );

                    $label = $this->variables->isEmpty($name) ? $code : sprintf(
                        '%s [%s]',
                        $methodTitle,
                        $methodCode
                    );

                    $options[ $code ][ 'value' ][] = [
                        'value' => $value,
                        'label' => $label
                    ];
                }
            }
        }

        return $options;
    }

    public function toArray(): array
    {
        $activeCarriers = $this->carrierHelper->getActiveCarriers(
            $this->isAllStores(),
            $this->isWithDefault()
        );

        $options = [];

        foreach ($activeCarriers as $code => $carrier) {
            $name = $carrier->getConfigData('name');

            if ($carrier instanceof CarrierInterface) {
                foreach ($carrier->getAllowedMethods() as $methodCode => $methodTitle) {
                    $value = sprintf(
                        '%s_%s',
                        $code,
                        $methodCode
                    );

                    $label = $this->variables->isEmpty($name) ? $code : sprintf(
                        '%s [%s] %s [%s]',
                        $name,
                        $code,
                        $methodTitle,
                        $methodCode
                    );

                    $options[ $value ] = $label;
                }
            }
        }

        return $options;
    }

    public function isAllStores(): bool
    {
        return $this->allStores;
    }

    public function setAllStores(bool $allStores)
    {
        $this->allStores = $allStores;
    }

    public function isWithDefault(): bool
    {
        return $this->withDefault;
    }

    public function setWithDefault(bool $withDefault)
    {
        $this->withDefault = $withDefault;
    }
}
