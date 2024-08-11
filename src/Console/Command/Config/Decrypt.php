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
class Decrypt extends Command
{
    protected function getCommandName(): string
    {
        return 'config:decrypt';
    }

    protected function getCommandDescription(): string
    {
        return 'Decrypt a value encrypted with the secret of the environment';
    }

    /**
     * @return InputOption[]
     */
    protected function getCommandDefinition(): array
    {
        return [
            new InputOption('value', null, InputOption::VALUE_REQUIRED, 'The value to decrypt'),
            new InputOption('key', null, InputOption::VALUE_OPTIONAL, 'The key to encrypt with')
        ];
    }

    protected function getClassName(): string
    {
        return Script\Decrypt::class;
    }

    protected function getArea(): string
    {
        return Area::AREA_ADMINHTML;
    }
}
