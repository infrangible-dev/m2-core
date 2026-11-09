<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Console\Command\Config;

use Infrangible\Core\Console\Command\Command;
use Magento\Framework\App\Area;
use Symfony\Component\Console\Input\InputOption;

/**
 * Console command config:import-json: imports the configuration from a JSON file.
 */
class Import extends Command
{
    /**
     * Returns the name of the command.
     *
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'config:import-json';
    }

    /**
     * Returns the description of the command.
     *
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Import JSON file with configuration';
    }

    /**
     * Returns the options of the command.
     *
     * @return array
     * @phpstan-return array<InputOption>
     */
    protected function getCommandDefinition(): array
    {
        return [
            new InputOption('file', null, InputOption::VALUE_REQUIRED, 'The file to import')
        ];
    }

    /**
     * Returns the class name of the script that the command runs.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return Script\Import::class;
    }

    /**
     * Returns the area that the script runs in.
     *
     * @return string
     */
    protected function getArea(): string
    {
        return Area::AREA_ADMINHTML;
    }
}
