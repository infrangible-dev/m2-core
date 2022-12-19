<?php

namespace Infrangible\Core\Console\Command;

use Magento\Framework\App\AreaInterface;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManager\ConfigLoaderInterface;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Execution
{
    /** @var State */
    protected $state;

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var AreaList */
    private $areaList;

    /**
     * Test constructor.
     *
     * @param State                  $state
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(State $state, ObjectManagerInterface $objectManager)
    {
        $this->state = $state;
        $this->objectManager = $objectManager;
        $this->areaList = $this->objectManager->get(AreaList::class);
    }

    /**
     * @param string          $className
     * @param string          $area
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws LocalizedException
     */
    public function execute(string $className, string $area, InputInterface $input, OutputInterface $output): int
    {
        $this->state->setAreaCode($area);
        $configLoader = $this->objectManager->get(ConfigLoaderInterface::class);
        $this->objectManager->configure($configLoader->load($area));

        $this->areaList->getArea($area)->load(AreaInterface::PART_TRANSLATE);

        /** @var Script $script */
        $script = $this->objectManager->get($className);

        $script->setObjectManager($this->objectManager);

        return $script->execute($input, $output);
    }
}
