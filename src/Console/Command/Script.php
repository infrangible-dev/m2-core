<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class of the scripts that console commands (see Command) run in a Magento area.
 */
abstract class Script
{
    /** @var ObjectManagerInterface|null */
    protected ?ObjectManagerInterface $objectManager = null;

    /**
     * Runs the script.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int 0 if everything went fine, or an error code
     */
    abstract public function execute(InputInterface $input, OutputInterface $output): int;

    /**
     * Returns the object manager configured for the area of the script.
     *
     * @return ObjectManagerInterface
     * @throws RuntimeException if the object manager was not set yet
     */
    public function getObjectManager(): ObjectManagerInterface
    {
        if ($this->objectManager === null) {
            throw new RuntimeException('The object manager of the script was not set');
        }

        return $this->objectManager;
    }

    /**
     * Sets the object manager configured for the area of the script.
     *
     * @param ObjectManagerInterface $objectManager
     *
     * @return void
     */
    public function setObjectManager(ObjectManagerInterface $objectManager): void
    {
        $this->objectManager = $objectManager;
    }
}
