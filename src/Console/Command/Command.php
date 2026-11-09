<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Console\Command;

use FeWeDev\Base\Variables;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class of console commands that run a script (see Script) in a Magento area with a new object manager.
 */
abstract class Command extends \Symfony\Component\Console\Command\Command
{
    /** @var Variables */
    protected Variables $variables;

    /** @var ObjectManagerFactory */
    protected ObjectManagerFactory $objectManagerFactory;

    /**
     * @param Variables $variables
     * @param ObjectManagerFactory $objectManagerFactory
     */
    public function __construct(Variables $variables, ObjectManagerFactory $objectManagerFactory)
    {
        $this->variables = $variables;

        $this->objectManagerFactory = $objectManagerFactory;

        parent::__construct();
    }

    /**
     * Returns the name of the command, for example "infrangible:config:export".
     *
     * @return string
     */
    abstract protected function getCommandName(): string;

    /**
     * Returns the description of the command.
     *
     * @return string
     */
    abstract protected function getCommandDescription(): string;

    /**
     * Returns the options and arguments of the command.
     *
     * @return array
     * @phpstan-return array<InputOption|InputArgument>
     */
    abstract protected function getCommandDefinition(): array;

    /**
     * Returns the class name of the script (see Script) that the command runs.
     *
     * @return string
     */
    abstract protected function getClassName(): string;

    /**
     * Returns the area that the script runs in, for example "adminhtml" or "frontend".
     *
     * @return string
     */
    abstract protected function getArea(): string;

    /**
     * Sets the name, description and definition of the command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName($this->getCommandName());
        $this->setDescription($this->getCommandDescription());
        $this->setDefinition($this->getCommandDefinition());

        parent::configure();
    }

    /**
     * Checks the required options and runs the script in its area with a new object manager.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int 0 if everything went fine, or an error code
     * @throws LocalizedException
     * @throws InvalidOptionException if a required option is missing
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->getDefinition()->getOptions() as $option) {
            if ($option->isValueRequired()) {
                if (!$input->hasOption($option->getName())
                    || $this->variables->isEmpty($input->getOption($option->getName()))
                    && $this->variables->isEmpty($option->getDefault())) {
                    throw new InvalidOptionException(sprintf('Missing required option: %s', $option->getName()));
                }
            }
        }

        // the object manager is created with the server parameters, as in bin/magento
        // phpcs:ignore Magento2.Security.Superglobal
        $omParams = $_SERVER;

        $omParams[StoreManager::PARAM_RUN_CODE] = 'admin';
        $omParams[Store::CUSTOM_ENTRY_POINT_PARAM] = true;

        $objectManager = $this->objectManagerFactory->create($omParams);

        $execution = $objectManager->create(Execution::class);

        if (! $execution instanceof Execution) {
            throw new LocalizedException(__('Could not create the execution of the script'));
        }

        return $execution->execute($this->getClassName(), $this->getArea(), $input, $output);
    }
}
