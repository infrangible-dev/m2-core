<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Order;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order\Config;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Statuses extends Status
{
    public function __construct(Config $orderConfig)
    {
        parent::__construct($orderConfig);

        $this->_stateStatuses = null;
    }
}
