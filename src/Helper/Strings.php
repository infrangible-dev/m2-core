<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Strings
{
    /** @var \FeWeDev\Base\Strings */
    protected $strings;

    /** @var UrlInterface */
    protected $urlInterface;

    /** @var EncryptorInterface */
    protected $encryptor;

    public function __construct(
        \FeWeDev\Base\Strings $strings,
        UrlInterface $urlInterface,
        EncryptorInterface $encryptor
    ) {
        $this->strings = $strings;

        $this->urlInterface = $urlInterface;
        $this->encryptor = $encryptor;
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
     * Encrypt a string
     */
    public function encrypt(string $data): string
    {
        return $this->encryptor->encrypt($data);
    }

    /**
     * Decrypt a string
     */
    public function decrypt(string $data): string
    {
        return $this->encryptor->decrypt($data);
    }
}
