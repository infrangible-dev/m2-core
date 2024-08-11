<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config;

use Exception;
use Magento\Framework\Encryption\Adapter\EncryptionAdapterInterface;
use Magento\Framework\Encryption\Adapter\Mcrypt;
use Magento\Framework\Encryption\Adapter\SodiumChachaIetf;
use SodiumException;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Encryptor implements EncryptorInterface
{
    /**
     * @throws Exception
     */
    public function decrypt(string $key, string $text): string
    {
        // no key for decryption
        if (! $key) {
            return '';
        }

        if ($text) {
            $parts = explode(':', $text, 4);
            $partsCount = count($parts);

            $initVector = null;
            // specified key, specified crypt, specified iv
            if (4 === $partsCount) {
                [$keyVersion, $cryptVersion, $iv, $text] = $parts;
                $initVector = $iv ?: null;
                $cryptVersion = \Magento\Framework\Encryption\Encryptor::CIPHER_RIJNDAEL_256;
                // specified key, specified crypt
            } elseif (3 === $partsCount) {
                [$keyVersion, $cryptVersion, $text] = $parts;
                $cryptVersion = (int)$cryptVersion;
                // no key version = oldest key, specified crypt
            } elseif (2 === $partsCount) {
                [$cryptVersion, $text] = $parts;
                $cryptVersion = (int)$cryptVersion;
                // no key version = oldest key, no crypt version = oldest crypt
            } elseif (1 === $partsCount) {
                $cryptVersion = \Magento\Framework\Encryption\Encryptor::CIPHER_BLOWFISH;
                // not supported format
            } else {
                return '';
            }

            $crypt = $this->getCrypt($key, $cryptVersion, $initVector);

            if (null === $crypt) {
                return '';
            }

            return trim($crypt->decrypt(base64_decode((string)$text)));
        }

        return '';
    }

    /**
     * @throws Exception
     */
    private function getCrypt(
        string $key,
        int $cipherVersion = null,
        string $initVector = null
    ): ?EncryptionAdapterInterface {
        if (! $key) {
            return null;
        }

        if (null === $cipherVersion) {
            $cipherVersion = \Magento\Framework\Encryption\Encryptor::CIPHER_LATEST;
        }

        if ($cipherVersion >= \Magento\Framework\Encryption\Encryptor::CIPHER_AEAD_CHACHA20POLY1305) {
            return new SodiumChachaIetf($key);
        }

        if ($cipherVersion === \Magento\Framework\Encryption\Encryptor::CIPHER_RIJNDAEL_128) {
            $cipher = MCRYPT_RIJNDAEL_128;
            $mode = MCRYPT_MODE_ECB;
        } elseif ($cipherVersion === \Magento\Framework\Encryption\Encryptor::CIPHER_RIJNDAEL_256) {
            $cipher = MCRYPT_RIJNDAEL_256;
            $mode = MCRYPT_MODE_CBC;
        } else {
            $cipher = MCRYPT_BLOWFISH;
            $mode = MCRYPT_MODE_ECB;
        }

        return new Mcrypt($key, $cipher, $mode, $initVector);
    }

    /**
     * @throws SodiumException
     */
    public function encrypt(string $key, string $text): string
    {
        $crypt = new SodiumChachaIetf($key);

        return 0 . ':' . \Magento\Framework\Encryption\Encryptor::CIPHER_AEAD_CHACHA20POLY1305 . ':' .
            base64_encode($crypt->encrypt($text));
    }
}
