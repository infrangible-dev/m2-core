<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
interface EncryptorInterface
{
    public function decrypt(string $key, string $text): string;

    /**
     * @param mixed  $text
     */
    public function encrypt(string $key, string $text): string;
}
