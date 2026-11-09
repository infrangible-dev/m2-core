<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Console\Command\Config\Script;

/**
 * Script of the console command config:encrypt.
 */
class Encrypt extends AbstractCrypt
{
    /**
     * Encrypts the value with the key, or with the key of Magento if no key is given.
     *
     * @param string $value
     * @param string|null $key
     *
     * @return string
     */
    protected function convert(string $value, ?string $key): string
    {
        return $key === null ? $this->encryptor->encrypt($value) : $this->customEncryptor->encrypt($key, $value);
    }
}
