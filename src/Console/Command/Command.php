<?php

namespace Infrangible\Core\Console\Command;

use Exception;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tofex\Help\Variables;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Command
    extends \Symfony\Component\Console\Command\Command
{
    /** @var Variables */
    protected $variableHelper;

    /** @var ObjectManagerFactory */
    protected $objectManagerFactory;

    /**
     * @param Variables            $variableHelper
     * @param ObjectManagerFactory $objectManagerFactory
     */
    public function __construct(Variables $variableHelper, ObjectManagerFactory $objectManagerFactory)
    {
        $this->variableHelper = $variableHelper;

        $this->objectManagerFactory = $objectManagerFactory;

        parent::__construct();
    }

    /**
     * @return string
     */
    abstract protected function getCommandName(): string;

    /**
     * @return string
     */
    abstract protected function getCommandDescription(): string;

    /**
     * @return array
     */
    abstract protected function getCommandDefinition(): array;

    /**
     * @return string
     */
    abstract protected function getClassName(): string;

    /**
     * @return string
     */
    abstract protected function getArea(): string;

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName($this->getCommandName());
        $this->setDescription($this->getCommandDescription());
        $this->setDefinition($this->getCommandDefinition());

        parent::configure();
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int 0 if everything went fine, or an error code
     *
     * @throws LocalizedException
     * @throws Exception
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->getDefinition()->getOptions() as $option) {
            if ($option->isValueRequired()) {
                if ( ! $input->hasOption($option->getName()) ||
                    $this->variableHelper->isEmpty($input->getOption($option->getName())) &&
                    $this->variableHelper->isEmpty($option->getDefault())) {
                    throw new Exception(sprintf('Missing required option: %s', $option->getName()));
                }
            }
        }

        $omParams = $_SERVER;

        $omParams[ StoreManager::PARAM_RUN_CODE ] = 'admin';
        $omParams[ Store::CUSTOM_ENTRY_POINT_PARAM ] = true;

        $objectManager = $this->objectManagerFactory->create($omParams);

        /** @var Execution $execution */
        $execution = $objectManager->create(Execution::class);

        return $execution->execute($this->getClassName(), $this->getArea(), $input, $output);
    }
}
