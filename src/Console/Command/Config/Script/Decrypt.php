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
class Decrypt extends Script
{
    /** @var EncryptorInterface */
    protected $encryptor;

    /** @var \Infrangible\Core\Model\Config\EncryptorInterface */
    protected $customEncryptor;

    public function __construct(
        EncryptorInterface $encryptor,
        \Infrangible\Core\Model\Config\EncryptorInterface $customEncryptor
    ) {
        $this->encryptor = $encryptor;
        $this->customEncryptor = $customEncryptor;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $value = $input->getOption('value');
        $key = $input->getOption('key');

        $decrypted = $key === null ? $this->encryptor->decrypt($value) : $this->customEncryptor->decrypt($key, $value);

        $output->writeln($decrypted);

        return 0;
    }
}
