<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Carrier;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Carrier;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrierInterface;

/**
 * Base of the options of the active carriers and their methods.
 */
abstract class AbstractActive implements OptionSourceInterface
{
    /** @var Variables */
    protected Variables $variables;

    /** @var Carrier */
    protected Carrier $carrierHelper;

    /** @var bool */
    private bool $allStores = false;

    /** @var bool */
    private bool $withDefault = true;

    /**
     * @param Variables $variables
     * @param Carrier $carrierHelper
     */
    public function __construct(Variables $variables, Carrier $carrierHelper)
    {
        $this->variables = $variables;
        $this->carrierHelper = $carrierHelper;
    }

    /**
     * Returns the active carriers of the current store or of all stores.
     *
     * @return AbstractCarrierInterface[] carrier code => carrier
     */
    protected function getActiveCarriers(): array
    {
        return $this->carrierHelper->getActiveCarriers(
            $this->isAllStores(),
            $this->isWithDefault()
        );
    }

    /**
     * Returns the label of the carrier: "name [code]", or the code if the carrier has no name.
     *
     * @param string $code
     * @param AbstractCarrierInterface $carrier
     *
     * @return string
     */
    protected function getCarrierLabel(string $code, AbstractCarrierInterface $carrier): string
    {
        $name = $carrier->getConfigData('name');

        return $this->variables->isEmpty($name) ? $code : sprintf(
            '%s [%s]',
            $this->variables->stringValue($name),
            $code
        );
    }

    /**
     * Checks whether carriers that are active in any store are listed (otherwise only in the current store).
     *
     * @return bool
     */
    public function isAllStores(): bool
    {
        return $this->allStores;
    }

    /**
     * Sets whether carriers that are active in any store are listed (otherwise only in the current store).
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
