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
    /**
     * @param string $key
     * @param string $text
     *
     * @return string
     */
    public function decrypt(string $key, string $text): string;

    /**
     * @param string $key
     * @param mixed  $text
     *
     * @return string
     */
    public function encrypt(string $key, string $text): string;
}
