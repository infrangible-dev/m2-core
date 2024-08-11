<?php

declare(strict_types=1);

namespace Infrangible\Core\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Script
{
    /** @var ObjectManagerInterface */
    protected $objectManager;

    /**
     * Executes the current command.
     *
     * @return int 0 if everything went fine, or an error code
     */
    abstract public function execute(InputInterface $input, OutputInterface $output): int;

    public function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }

    public function setObjectManager(ObjectManagerInterface $objectManager): void
    {
        $this->objectManager = $objectManager;
    }
}
