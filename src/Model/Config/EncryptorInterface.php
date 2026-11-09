<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config;

/**
 * Encrypts and decrypts values with a given key instead of the key of the installation.
 */
interface EncryptorInterface
{
    /**
     * Decrypts a value encrypted by Magento with the given key.
     *
     * @param string $key
     * @param string $text
     *
     * @return string
     */
    public function decrypt(string $key, string $text): string;

    /**
     * Encrypts the value with the given key, as Magento does.
     *
     * @param string $key
     * @param string $text
     *
     * @return string
     */
    public function encrypt(string $key, string $text): string;
}
