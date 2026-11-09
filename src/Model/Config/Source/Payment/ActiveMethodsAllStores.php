<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Payment;

/**
 * Options of the payment methods that are active in any store.
 */
class ActiveMethodsAllStores extends ActiveMethods
{
    /**
     * Returns the payment methods that are active in any store for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionArray(): array
    {
        $this->setAllStores(true);
        $this->setWithDefault(true);

        return parent::toOptionArray();
    }

    /**
     * Returns the payment methods that are active in any store as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptions(): array
    {
        $this->setAllStores(true);
        $this->setWithDefault(true);

        return parent::toOptions();
    }
}
