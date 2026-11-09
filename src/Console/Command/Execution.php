<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

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
 * Runs a console script in an area, with the object manager configured for that area.
 */
class Execution
{
    /** @var State */
    protected State $state;

    /** @var ObjectManagerInterface */
    private ObjectManagerInterface $objectManager;

    /** @var AreaList */
    private AreaList $areaList;

    /** @var ConfigLoaderInterface */
    private ConfigLoaderInterface $configLoader;

    /**
     * @param State $state
     * @param ObjectManagerInterface $objectManager
     * @param AreaList $areaList
     * @param ConfigLoaderInterface $configLoader
     */
    public function __construct(
        State $state,
        ObjectManagerInterface $objectManager,
        AreaList $areaList,
        ConfigLoaderInterface $configLoader
    ) {
        $this->state = $state;
        $this->objectManager = $objectManager;
        $this->areaList = $areaList;
        $this->configLoader = $configLoader;
    }

    /**
     * Sets the area, loads its configuration and translations and runs the script with the given class name.
     *
     * @param string $className
     * @param string $area
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws LocalizedException
     */
    public function execute(string $className, string $area, InputInterface $input, OutputInterface $output): int
    {
        $this->state->setAreaCode($area);
        $this->objectManager->configure($this->configLoader->load($area));

        $this->areaList->getArea($area)->load(AreaInterface::PART_TRANSLATE);

        $script = $this->objectManager->get($className);

        if (! $script instanceof Script) {
            throw new LocalizedException(__('Class %1 is no console script', $className));
        }

        $script->setObjectManager($this->objectManager);

        return $script->execute($input, $output);
    }
}
