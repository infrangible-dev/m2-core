<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Payment;

use FeWeDev\Base\Arrays;
use Infrangible\Core\Helper\Payment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config\Source\Allmethods;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ActiveMethods
    extends Allmethods
{
    /** @var Payment */
    protected $paymentHelper;

    /** @var Arrays */
    protected $arrays;

    /** @var bool */
    private $allStores = false;

    /** @var bool */
    private $withDefault = true;

    /**
     * @param Data    $paymentData
     * @param Payment $paymentHelper
     * @param Arrays  $arrayHelper
     */
    public function __construct(Data $paymentData, Payment $paymentHelper, Arrays $arrayHelper)
    {
        parent::__construct($paymentData);

        $this->paymentHelper = $paymentHelper;
        $this->arrays = $arrayHelper;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $activeMethods = $this->paymentHelper->getActiveMethods($this->isAllStores(), $this->isWithDefault());

        $activeMethodCodes = [];

        foreach ($activeMethods as $activeMethod) {
            $activeMethodCodes[] = $activeMethod->getCode();
        }

        $options = [['value' => '', 'label' => __('-- Please Select --')]];

        foreach ($this->filterOptions(parent::toOptionArray(), $activeMethodCodes) as $option) {
            $options[] = $option;
        }

        return $options;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function toOptions(): array
    {
        $activeMethods = $this->paymentHelper->getActiveMethods($this->isAllStores(), $this->isWithDefault());

        $activeMethodCodes = [];

        foreach ($activeMethods as $activeMethod) {
            $activeMethodCodes[] = $activeMethod->getCode();
        }

        return $this->extractOptions($this->filterOptions(parent::toOptionArray(), $activeMethodCodes));
    }

    /**
     * @param array $optionsArray
     * @param array $prefixes
     *
     * @return array
     */
    protected function extractOptions(array $optionsArray, array $prefixes = []): array
    {
        $options = [];

        foreach ($optionsArray as $option) {
            if (is_array($option)) {
                $value = $this->arrays->getValue($option, 'value');
                $label = $this->arrays->getValue($option, 'label');

                if (is_array($value)) {
                    $prefixes[] = $label;

                    foreach ($this->extractOptions($value, $prefixes) as $subValue => $subLabel) {
                        $options[$subValue] = $subLabel;
                    }
                } else {
                    $options[$value] =
                        empty($prefixes) ? $label : sprintf('%s - %s', implode(' - ', $prefixes), $label);
                }
            }
        }

        return $options;
    }

    /**
     * @param array    $options
     * @param string[] $activeMethodCodes
     *
     * @return array
     */
    protected function filterOptions(array $options, array $activeMethodCodes): array
    {
        foreach ($options as $key => $option) {
            if (array_key_exists('value', $option)) {
                $value = $option['value'];

                if (is_array($value)) {
                    $subOptions = $this->filterOptions($value, $activeMethodCodes);

                    if (empty($subOptions)) {
                        unset($options[$key]);
                    } else {
                        $options[$key]['value'] = $subOptions;
                    }
                } else {
                    if (!in_array($value, $activeMethodCodes)) {
                        unset($options[$key]);
                    }
                }
            }
        }

        return $options;
    }

    /**
     * @return bool
     */
    public function isAllStores(): bool
    {
        return $this->allStores;
    }

    /**
     * @param bool $allStores
     */
    public function setAllStores(bool $allStores)
    {
        $this->allStores = $allStores;
    }

    /**
     * @return bool
     */
    public function isWithDefault(): bool
    {
        return $this->withDefault;
    }

    /**
     * @param bool $withDefault
     */
    public function setWithDefault(bool $withDefault)
    {
        $this->withDefault = $withDefault;
    }
}
