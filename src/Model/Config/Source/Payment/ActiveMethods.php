<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Payment;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Payment;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config\Source\Allmethods;

/**
 * Options of the active payment methods.
 */
class ActiveMethods extends Allmethods
{
    /** @var Payment */
    protected Payment $paymentHelper;

    /** @var Arrays */
    protected Arrays $arrays;

    /** @var Variables */
    protected Variables $variables;

    /** @var bool */
    private bool $allStores = false;

    /** @var bool */
    private bool $withDefault = true;

    /**
     * @param Data $paymentData
     * @param Payment $paymentHelper
     * @param Arrays $arrayHelper
     * @param Variables $variables
     */
    public function __construct(Data $paymentData, Payment $paymentHelper, Arrays $arrayHelper, Variables $variables)
    {
        parent::__construct($paymentData);

        $this->paymentHelper = $paymentHelper;
        $this->arrays = $arrayHelper;
        $this->variables = $variables;
    }

    /**
     * Returns the active payment methods for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
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
     * Returns the active payment methods as code => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
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
     * Converts options for a select field (with groups) into value => label.
     *
     * The labels of the groups are added before the labels of their options.
     *
     * @param array $optionsArray
     * @param array $prefixes
     *
     * @phpstan-param array<int|string, mixed> $optionsArray
     * @phpstan-param array<int, string> $prefixes
     *
     * @return array
     * @phpstan-return array<int|string, string>
     */
    protected function extractOptions(array $optionsArray, array $prefixes = []): array
    {
        $options = [];

        foreach ($optionsArray as $option) {
            if (is_array($option)) {
                $value = $this->arrays->getValue($option, 'value');
                $label = $this->variables->stringValue($this->arrays->getValue($option, 'label', ''));

                if (is_array($value)) {
                    $groupPrefixes = $prefixes;
                    $groupPrefixes[] = $label;

                    foreach ($this->extractOptions($value, $groupPrefixes) as $subValue => $subLabel) {
                        $options[$subValue] = $subLabel;
                    }
                } elseif (is_int($value) || is_string($value)) {
                    $options[$value] =
                        empty($prefixes) ? $label : sprintf('%s - %s', implode(' - ', $prefixes), $label);
                }
            }
        }

        return $options;
    }

    /**
     * Removes the options of methods that are not active, and groups without options.
     *
     * @param array $options
     * @param string[] $activeMethodCodes
     *
     * @phpstan-param array<int|string, mixed> $options
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function filterOptions(array $options, array $activeMethodCodes): array
    {
        foreach ($options as $key => $option) {
            if (is_array($option) && array_key_exists('value', $option)) {
                $value = $option['value'];

                if (is_array($value)) {
                    $subOptions = $this->filterOptions($value, $activeMethodCodes);

                    if (empty($subOptions)) {
                        unset($options[$key]);
                    } else {
                        $option['value'] = $subOptions;
                        $options[$key] = $option;
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
     * Checks whether methods that are active in any store are listed (otherwise only in the current store).
     *
     * @return bool
     */
    public function isAllStores(): bool
    {
        return $this->allStores;
    }

    /**
     * Sets whether methods that are active in any store are listed (otherwise only in the current store).
     *
     * @param bool $allStores
     *
     * @return void
     */
    public function setAllStores(bool $allStores): void
    {
        $this->allStores = $allStores;
    }

    /**
     * Checks whether the default configuration is included when checking the stores.
     *
     * @return bool
     */
    public function isWithDefault(): bool
    {
        return $this->withDefault;
    }

    /**
     * Sets whether the default configuration is included when checking the stores.
     *
     * @param bool $withDefault
     *
     * @return void
     */
    public function setWithDefault(bool $withDefault): void
    {
        $this->withDefault = $withDefault;
    }
}
