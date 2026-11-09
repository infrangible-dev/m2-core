<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Order;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\Order\Config;

/**
 * Options of the order states.
 */
class State implements OptionSourceInterface
{
    /** @var Config */
    protected Config $orderConfig;

    /**
     * @param Config $orderConfig
     */
    public function __construct(Config $orderConfig)
    {
        $this->orderConfig = $orderConfig;
    }

    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int, array{value: int|string, label: mixed}>
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->toOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    /**
     * Returns the options as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptions(): array
    {
        return $this->orderConfig->getStates();
    }
}
