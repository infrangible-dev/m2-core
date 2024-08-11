<?php

declare(strict_types=1);

namespace Infrangible\Core\Console\Command\Config;

use Infrangible\Core\Console\Command\Command;
use Magento\Framework\App\Area;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Export extends Command
{
    protected function getCommandName(): string
    {
        return 'config:export-json';
    }

    protected function getCommandDescription(): string
    {
        return 'Export JSON file with configuration';
    }

    protected function getCommandDefinition(): array
    {
        return [
            new InputOption('path', null, InputOption::VALUE_REQUIRED, 'The config path to export'),
            new InputOption('file', null, InputOption::VALUE_REQUIRED, 'The file to import')
        ];
    }

    protected function getClassName(): string
    {
        return Script\Export::class;
    }

    protected function getArea(): string
    {
        return Area::AREA_ADMINHTML;
    }
}
