<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Model\Config;

use Exception;
use Infrangible\Core\Model\Config\Encryptor;
use PHPUnit\Framework\TestCase;
use SodiumException;

/**
 * Tests encrypting and decrypting values with a given key, as in the config:encrypt and config:decrypt commands.
 */
class EncryptorTest extends TestCase
{
    private const KEY = '0123456789abcdef0123456789abcdef';

    /**
     * An encrypted value is decrypted with the same key, but not with another one.
     *
     * @return void
     * @throws Exception
     * @throws SodiumException
     */
    public function testEncryptAndDecrypt(): void
    {
        $encryptor = new Encryptor();

        $encrypted = $encryptor->encrypt(self::KEY, 'secret value');

        $this->assertMatchesRegularExpression('/^0:3:/', $encrypted);
        $this->assertNotSame($encrypted, $encryptor->encrypt(self::KEY, 'secret value'));
        $this->assertSame('secret value', $encryptor->decrypt(self::KEY, $encrypted));
        $this->assertNotSame('secret value', $encryptor->decrypt('fedcba9876543210fedcba9876543210', $encrypted));
    }

    /**
     * Without key or value, the result is empty.
     *
     * @return void
     * @throws Exception
     */
    public function testDecryptWithoutKeyOrValue(): void
    {
        $encryptor = new Encryptor();

        $this->assertSame('', $encryptor->decrypt('', '0:3:abc'));
        $this->assertSame('', $encryptor->decrypt(self::KEY, ''));
    }
}
