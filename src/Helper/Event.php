<?php

namespace Infrangible\Core\Helper;

use Magento\Framework\Event\ManagerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Event
{
    /** @var ManagerInterface */
    protected $eventManager;

    /**
     * @param ManagerInterface $eventManager
     */
    public function __construct(ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @param       $eventName
     * @param array $data
     */
    public function dispatch($eventName, array $data = [])
    {
        $this->eventManager->dispatch($eventName, $data);
    }
}
