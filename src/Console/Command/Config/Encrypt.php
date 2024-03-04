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
class Encrypt
    extends Command
{
    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'config:encrypt';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Encrypt a value with the secret of the environment';
    }

    /**
     * @return InputOption[]
     */
    protected function getCommandDefinition(): array
    {
        return [
            new InputOption('value', null, InputOption::VALUE_REQUIRED, 'The value to encrypt')
        ];
    }

    /**
     * @return string
     */
    protected function getClassName(): string
    {
        return Script\Encrypt::class;
    }

    /**
     * @return string
     */
    protected function getArea(): string
    {
        return Area::AREA_ADMINHTML;
    }
}
