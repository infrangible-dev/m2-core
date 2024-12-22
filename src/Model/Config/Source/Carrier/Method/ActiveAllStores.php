<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Carrier\Method;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ActiveAllStores extends Active
{
    public function toOptionArray(): array
    {
        $this->setAllStores(true);
        $this->setWithDefault(true);

        return parent::toOptionArray();
    }

    public function toArray(): array
    {
        $this->setAllStores(true);
        $this->setWithDefault(true);

        return parent::toArray();
    }
}
