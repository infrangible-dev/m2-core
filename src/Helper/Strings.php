<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;

class Strings
{
    /**
     * @param \FeWeDev\Base\Strings $strings
     * @param UrlInterface $urlInterface
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        protected \FeWeDev\Base\Strings $strings,
        protected UrlInterface $urlInterface,
        protected EncryptorInterface $encryptor
    ) {
    }

    /**
     * Generate a 40 characters long uuid. The uuid is a sha1 hash over a
     * string build with the magento base url, the micro time and a 7 digit
     * long random number e.g. : 216908463793cd292cad4756525ed23dafcf7af0 .
     *
     * @return string a 40 character long hex value
     */
    public function generateUUID(): string
    {
        return $this->strings->generateUUID($this->urlInterface->getBaseUrl());
    }

    /**
     * Encrypts the string with the encryption key of the installation.
     *
     * @param string $data
     *
     * @return string
     */
    public function encrypt(string $data): string
    {
        return $this->encryptor->encrypt($data);
    }

    /**
     * Decrypts the string with the encryption key of the installation.
     *
     * @param string $data
     *
     * @return string
     */
    public function decrypt(string $data): string
    {
        return $this->encryptor->decrypt($data);
    }
}
