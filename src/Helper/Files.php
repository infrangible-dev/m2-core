<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Variables;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Files
{
    /** @var Variables */
    protected $variables;

    /** @var \FeWeDev\Base\Files */
    protected $files;

    /** @var LoggerInterface */
    protected $logging;

    /** @var DirectoryList */
    protected $directoryList;

    /** @var File */
    protected $file;

    public function __construct(
        Variables $variables,
        \FeWeDev\Base\Files $files,
        LoggerInterface $logging,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->variables = $variables;
        $this->files = $files;

        $this->logging = $logging;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * @throws Exception
     */
    public function determineFilePath(string $path, ?string $basePath = null, bool $makeDir = false): string
    {
        $this->logging->debug(
            sprintf(
                'Determine path of: %s with a prefix base path: %s',
                $path,
                $basePath
            )
        );

        if ($this->variables->isEmpty($basePath)) {
            $basePath = $this->directoryList->getRoot();
        }

        $path = $this->files->determineFilePath(
            $path,
            $basePath,
            $makeDir
        );

        $this->logging->debug(
            sprintf(
                'Determined absolute path: %s',
                $path
            )
        );

        return $path;
    }

    /**
     * @throws Exception
     */
    public function determineFilesFromFilePath(string $path): array
    {
        return $this->determineFromFilePath(
            $path,
            true,
            false
        );
    }

    /**
     * @throws Exception
     */
    public function determineDirectoriesFromFilePath(string $path): array
    {
        return $this->determineFromFilePath(
            $path,
            false
        );
    }

    /**
     * @throws Exception
     */
    public function determineFromFilePath(
        string $path,
        bool $includeFiles = true,
        bool $includeDirectories = true
    ): array {
        return $this->files->determineFromFilePath(
            $path,
            $this->directoryList->getRoot(),
            $includeFiles,
            $includeDirectories
        );
    }

    /**
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection RedundantSuppression
     */
    public function getTempDir(): string
    {
        try {
            return $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::TMP);
        } catch (FileSystemException $exception) {
            return sys_get_temp_dir();
        }
    }

    public function read(string $fileName): string
    {
        if (file_exists($fileName) && is_readable($fileName)) {
            $fileContent = @file_get_contents($fileName);

            if ($fileContent) {
                return $fileContent;
            }
        }

        return '';
    }

    public function removeFile(string $fileName): bool
    {
        return $this->file->rm($fileName);
    }
}
