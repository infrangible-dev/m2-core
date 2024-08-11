<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Framework\Event\ManagerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Event
{
    /** @var ManagerInterface */
    protected $eventManager;

    public function __construct(ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    public function dispatch($eventName, array $data = []): void
    {
        $this->eventManager->dispatch($eventName, $data);
    }
}
