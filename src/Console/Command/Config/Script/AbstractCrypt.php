<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Console\Command\Config\Script;

use Infrangible\Core\Console\Command\Script;
use Magento\Framework\Encryption\EncryptorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base of the scripts of the console commands config:encrypt and config:decrypt: converts the value of the option
 * "value", with the key of the option "key" or with the key of Magento.
 */
abstract class AbstractCrypt extends Script
{
    /** @var EncryptorInterface */
    protected EncryptorInterface $encryptor;

    /** @var \Infrangible\Core\Model\Config\EncryptorInterface */
    protected \Infrangible\Core\Model\Config\EncryptorInterface $customEncryptor;

    /**
     * @param EncryptorInterface $encryptor
     * @param \Infrangible\Core\Model\Config\EncryptorInterface $customEncryptor
     */
    public function __construct(
        EncryptorInterface $encryptor,
        \Infrangible\Core\Model\Config\EncryptorInterface $customEncryptor
    ) {
        $this->encryptor = $encryptor;
        $this->customEncryptor = $customEncryptor;
    }

    /**
     * Converts the value with the key, or with the key of Magento if no key is given.
     *
     * @param string $value
     * @param string|null $key
     *
     * @return string
     */
    abstract protected function convert(string $value, ?string $key): string;

    /**
     * Runs the script.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $value = $input->getOption('value');
        $value = is_scalar($value) ? (string)$value : '';
        $key = $input->getOption('key');
        $key = is_scalar($key) ? (string)$key : null;

        $output->writeln($this->convert($value, $key));

        return 0;
    }
}
