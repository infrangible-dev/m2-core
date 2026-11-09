<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Order;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order\Config;

/**
 * Order statuses of all states (Magento's source model only lists the statuses of the state "new").
 */
class Statuses extends Status
{
    /**
     * @param Config $orderConfig
     */
    public function __construct(Config $orderConfig)
    {
        parent::__construct($orderConfig);

        $this->_stateStatuses = [];
    }
}
