<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Variables;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\Io\File;
use Psr\Log\LoggerInterface;

class Files
{
    /**
     * @param Variables $variables
     * @param \FeWeDev\Base\Files $files
     * @param LoggerInterface $logging
     * @param DirectoryList $directoryList
     * @param File $file
     * @param FileDriver $fileDriver
     */
    public function __construct(
        protected Variables $variables,
        protected \FeWeDev\Base\Files $files,
        protected LoggerInterface $logging,
        protected DirectoryList $directoryList,
        protected File $file,
        protected FileDriver $fileDriver
    ) {
    }

    /**
     * Returns the absolute path of the file.
     *
     * Relative paths are resolved against the base path, by default the Magento root directory.
     *
     * @param string $path
     * @param string|null $basePath
     * @param bool $makeDir
     *
     * @return string
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
     * Returns the files in the directory (relative to the Magento root directory, or absolute).
     *
     * @param string $path
     *
     * @return array
     * @phpstan-return array<int, string>
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
     * Returns the directories in the directory (relative to the Magento root directory, or absolute).
     *
     * @param string $path
     *
     * @return array
     * @phpstan-return array<int, string>
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
     * Returns the files and/or directories in the directory (relative to the Magento root directory, or absolute).
     *
     * @param string $path
     * @param bool $includeFiles
     * @param bool $includeDirectories
     *
     * @return array
     * @phpstan-return array<int, string>
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
     * Returns the tmp directory of Magento, or the temporary directory of the system if it is not available.
     *
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection RedundantSuppression
     * @return string
     */
    public function getTempDir(): string
    {
        try {
            return $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::TMP);
        } catch (FileSystemException) {
            return sys_get_temp_dir();
        }
    }

    /**
     * Returns the content of the file, or an empty string if it does not exist or cannot be read.
     *
     * @param string $fileName
     *
     * @return string
     */
    public function read(string $fileName): string
    {
        try {
            if ($this->fileDriver->isExists($fileName) && $this->fileDriver->isReadable($fileName)) {
                return $this->fileDriver->fileGetContents($fileName);
            }
        } catch (FileSystemException $exception) {
            $this->logging->error($exception);
        }

        return '';
    }

    /**
     * Removes the file and returns whether it was removed.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function removeFile(string $fileName): bool
    {
        return $this->file->rm($fileName);
    }
}
