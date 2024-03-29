<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Carrier;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Carrier;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Active
    implements OptionSourceInterface
{
    /** @var Variables */
    protected $variables;

    /** @var Stores */
    protected $storeHelper;

    /** @var Carrier */
    protected $carrierHelper;

    /** @var bool */
    private $allStores = false;

    /** @var bool */
    private $withDefault = true;

    /**
     * @param Variables $variables
     * @param Stores    $storeHelper
     * @param Carrier   $carrierHelper
     */
    public function __construct(
        Variables $variables,
        Stores $storeHelper,
        Carrier $carrierHelper
    ) {
        $this->variables = $variables;
        $this->storeHelper = $storeHelper;
        $this->carrierHelper = $carrierHelper;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $activeCarriers = $this->carrierHelper->getActiveCarriers($this->isAllStores(), $this->isWithDefault());

        $options = [['value' => '', 'label' => __('-- Please Select --')]];

        foreach ($activeCarriers as $code => $carrier) {
            $name = $carrier->getConfigData('name');

            $options[] = [
                'value' => $code,
                'label' => $this->variables->isEmpty($name) ? $code : sprintf('%s [%s]', $name, $code)
            ];
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        $activeCarriers = $this->carrierHelper->getActiveCarriers($this->isAllStores(), $this->isWithDefault());

        $options = [];

        foreach ($activeCarriers as $code => $carrier) {
            $name = $carrier->getConfigData('name');

            $options[$code] = $this->variables->isEmpty($name) ? $code : sprintf('%s [%s]', $name, $code);
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
