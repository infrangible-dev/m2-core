<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Console\Command\Config\Script;

use Infrangible\Core\Console\Command\Script;
use Infrangible\Core\Helper\Config;
use InvalidArgumentException;
use Magento\Framework\Exception\FileSystemException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Script of the console command config:import-json.
 */
class Import extends Script
{
    /** @var Config */
    protected Config $configHelper;

    /**
     * @param Config $configHelper
     */
    public function __construct(Config $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * Runs the script.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws FileSystemException
     * @throws InvalidArgumentException if the configuration is not valid
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileName = $input->getOption('file');
        $fileName = is_scalar($fileName) ? (string)$fileName : '';

        $this->configHelper->importConfigJsonFile($fileName);

        return 0;
    }
}
