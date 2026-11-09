<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Framework\Event\ManagerInterface;

class Event
{
    /** @var ManagerInterface */
    protected ManagerInterface $eventManager;

    /**
     * @param ManagerInterface $eventManager
     */
    public function __construct(ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Dispatches the event with the given data to its observers.
     *
     * @param string $eventName
     * @param array $data
     *
     * @phpstan-param array<string, mixed> $data
     *
     * @return void
     */
    public function dispatch(string $eventName, array $data = []): void
    {
        $this->eventManager->dispatch($eventName, $data);
    }
}
