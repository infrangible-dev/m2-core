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
class Decrypt
    extends Script
{
    /** @var EncryptorInterface */
    protected $encryptor;

    /**
     * @param EncryptorInterface $encryptor
     */
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
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

        $decrypted = $this->encryptor->decrypt($value);

        $output->writeln($decrypted);

        return 0;
    }
}
