<?php

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Psr\Log\LoggerInterface;
use Tofex\Help\Variables;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Files
{
    /** @var Variables */
    protected $variableHelper;

    /** @var \Tofex\Help\Files */
    protected $fileHelper;

    /** @var LoggerInterface */
    protected $logging;

    /** @var DirectoryList */
    protected $directoryList;

    /** @var File */
    protected $file;

    /**
     * @param Variables         $variableHelper
     * @param \Tofex\Help\Files $fileHelper
     * @param LoggerInterface   $logging
     * @param DirectoryList     $directoryList
     * @param File              $file
     */
    public function __construct(
        Variables $variableHelper,
        \Tofex\Help\Files $fileHelper,
        LoggerInterface $logging,
        DirectoryList $directoryList,
        File $file)
    {
        $this->variableHelper = $variableHelper;
        $this->fileHelper = $fileHelper;

        $this->logging = $logging;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * Method to set path as relative (in Magento directories) or absolute for server
     *
     * @param string $path
     * @param null   $basePath
     * @param bool   $makeDir
     *
     * @return string
     * @throws Exception
     */
    public function determineFilePath(string $path, $basePath = null, bool $makeDir = false): string
    {
        $this->logging->debug(sprintf('Determine path of: %s with a prefix base path: %s', $path, $basePath));

        if ($this->variableHelper->isEmpty($basePath)) {
            $basePath = $this->directoryList->getRoot();
        }

        $path = $this->fileHelper->determineFilePath($path, $basePath, $makeDir);

        $this->logging->debug(sprintf('Determined absolute path: %s', $path));

        return $path;
    }

    /**
     * Method to read Files from directory
     *
     * @param string $path
     *
     * @return array
     * @throws Exception
     */
    public function determineFilesFromFilePath(string $path): array
    {
        return $this->determineFromFilePath($path, true, false);
    }

    /**
     * Method to read Files from directory
     *
     * @param string $path
     *
     * @return array
     * @throws Exception
     */
    public function determineDirectoriesFromFilePath(string $path): array
    {
        return $this->determineFromFilePath($path, false);
    }

    /**
     * @param string $path
     * @param bool   $includeFiles
     * @param bool   $includeDirectories
     *
     * @return array
     * @throws Exception
     */
    public function determineFromFilePath(
        string $path,
        bool $includeFiles = true,
        bool $includeDirectories = true): array
    {
        return $this->fileHelper->determineFromFilePath($path, $this->directoryList->getRoot(), $includeFiles,
            $includeDirectories);
    }

    /**
     * @return string
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
}
