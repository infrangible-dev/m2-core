<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Carrier\Method;

/**
 * Options of the methods of the carriers that are active in any store.
 */
class ActiveAllStores extends Active
{
    /**
     * Returns the methods of the carriers that are active in any store for a select field.
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
     * Returns the methods of the carriers that are active in any store as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toArray(): array
    {
        $this->setAllStores(true);
        $this->setWithDefault(true);

        return parent::toArray();
    }
}
