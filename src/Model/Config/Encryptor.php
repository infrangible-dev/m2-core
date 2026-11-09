<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config;

use Exception;
use Magento\Framework\Encryption\Adapter\EncryptionAdapterInterface;
use Magento\Framework\Encryption\Adapter\Mcrypt;
use Magento\Framework\Encryption\Adapter\SodiumChachaIetf;
use SodiumException;

/**
 * Encrypts and decrypts values like Magento, but with a given key instead of the key of the installation, for example
 * to transfer configuration values between installations.
 */
class Encryptor implements EncryptorInterface
{
    private const MCRYPT_BLOWFISH = 'blowfish';
    private const MCRYPT_RIJNDAEL_128 = 'rijndael-128';
    private const MCRYPT_RIJNDAEL_256 = 'rijndael-256';
    private const MCRYPT_MODE_ECB = 'ecb';
    private const MCRYPT_MODE_CBC = 'cbc';

    /**
     * Decrypts a value encrypted by Magento with the given key.
     *
     * Returns an empty string, if the value or key is empty or the format of the value is unknown.
     *
     * @param string $key
     * @param string $text
     *
     * @return string
     * @throws Exception
     */
    public function decrypt(string $key, string $text): string
    {
        // no key for decryption
        if (! $key) {
            return '';
        }

        if ($text) {
            $parts = explode(
                ':',
                $text,
                4
            );
            $partsCount = count($parts);

            $initVector = null;
            // specified key, specified crypt, specified iv
            if (4 === $partsCount) {
                [, , $iv, $text] = $parts;
                $initVector = $iv ? : null;
                $cryptVersion = \Magento\Framework\Encryption\Encryptor::CIPHER_RIJNDAEL_256;
                // specified key, specified crypt
            } elseif (3 === $partsCount) {
                [, $cryptVersion, $text] = $parts;
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

            $crypt = $this->getCrypt(
                $key,
                $cryptVersion,
                $initVector
            );

            if (null === $crypt) {
                return '';
            }

            // phpcs:ignore Magento2.Functions.DiscouragedFunction -- encrypted values are base64 encoded, as in Magento
            return trim($crypt->decrypt(base64_decode($text)));
        }

        return '';
    }

    /**
     * Returns the encryption adapter for the cipher version, as Magento's own encryptor does.
     *
     * @param string $key
     * @param int|null $cipherVersion
     * @param string|null $initVector
     *
     * @return EncryptionAdapterInterface|null
     * @throws Exception
     */
    private function getCrypt(
        string $key,
        ?int $cipherVersion = null,
        ?string $initVector = null
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

        // values of the constants MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB and so on, which only exist with the mcrypt
        // extension (removed from PHP in 7.2) that the Mcrypt adapter of Magento needs for these old ciphers
        if ($cipherVersion === \Magento\Framework\Encryption\Encryptor::CIPHER_RIJNDAEL_128) {
            $cipher = self::MCRYPT_RIJNDAEL_128;
            $mode = self::MCRYPT_MODE_ECB;
        } elseif ($cipherVersion === \Magento\Framework\Encryption\Encryptor::CIPHER_RIJNDAEL_256) {
            $cipher = self::MCRYPT_RIJNDAEL_256;
            $mode = self::MCRYPT_MODE_CBC;
        } else {
            $cipher = self::MCRYPT_BLOWFISH;
            $mode = self::MCRYPT_MODE_ECB;
        }

        return new Mcrypt(
            $key,
            $cipher,
            $mode,
            $initVector
        );
    }

    /**
     * Encrypts the value with the given key and the latest cipher of Magento (ChaCha20-Poly1305).
     *
     * @param string $key
     * @param string $text
     *
     * @return string
     * @throws SodiumException
     */
    public function encrypt(string $key, string $text): string
    {
        $crypt = new SodiumChachaIetf($key);

        return 0 . ':' . \Magento\Framework\Encryption\Encryptor::CIPHER_AEAD_CHACHA20POLY1305 . ':' .
            base64_encode($crypt->encrypt($text));
    }
}
