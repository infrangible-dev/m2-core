<?php

declare(strict_types=1);

namespace Infrangible\Core\Console\Command\Config\Script;

use Infrangible\Core\Console\Command\Script;
use Magento\Framework\Encryption\EncryptorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Encrypt
    extends Script
{
    /** @var EncryptorInterface */
    protected $encryptor;

    /** @var \Infrangible\Core\Model\Config\EncryptorInterface */
    protected $customEncryptor;

    /**
     * @param EncryptorInterface                                $encryptor
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $value = $input->getOption('value');
        $key = $input->getOption('key');

        $encrypted = $key === null ? $this->encryptor->encrypt($value) : $this->customEncryptor->encrypt($key, $value);

        $output->writeln($encrypted);

        return 0;
    }
}
