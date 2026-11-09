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
 * Console command config:decrypt: decrypts a value with the encryption key of the installation or a given key.
 */
class Decrypt extends Command
{
    /**
     * Returns the name of the command.
     *
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'config:decrypt';
    }

    /**
     * Returns the description of the command.
     *
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Decrypt a value encrypted with the secret of the environment';
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
            new InputOption('value', null, InputOption::VALUE_REQUIRED, 'The value to decrypt'),
            new InputOption('key', null, InputOption::VALUE_OPTIONAL, 'The key to decrypt with')
        ];
    }

    /**
     * Returns the class name of the script that the command runs.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return Script\Decrypt::class;
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
