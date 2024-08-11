<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Payment;

use Magento\Framework\Exception\LocalizedException;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ActiveMethodsAllStores extends ActiveMethods
{
    /**
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $this->setAllStores(true);
        $this->setWithDefault(true);

        return parent::toOptionArray();
    }

    /**
     * @throws LocalizedException
     */
    public function toOptions(): array
    {
        $this->setAllStores(true);
        $this->setWithDefault(true);

        return parent::toOptions();
    }
}
