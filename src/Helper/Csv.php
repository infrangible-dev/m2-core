<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

class Csv
{
    /**
     * @param Files $fileHelper
     * @param Variables $variables
     * @param Arrays $arrayHelper
     * @param LoggerInterface $logging
     * @param File $file
     */
    public function __construct(
        protected Files $fileHelper,
        protected Variables $variables,
        protected Arrays $arrayHelper,
        protected LoggerInterface $logging,
        protected File $file
    ) {
    }

    /**
     * Reads a CSV file into an array.
     *
     * Relative file names are resolved with Files::determineFilePath(). With a header (given or taken from the first
     * row), every row becomes an array with the header columns as keys.
     *
     * @param string $fileName
     * @param bool $removeEmptyElements
     * @param string $delimiter
     * @param array $header
     * @param bool $hasHeader
     *
     * @phpstan-param array<int, string> $header
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     * @throws Exception
     * @throws FileSystemException
     */
    public function readFile(
        string $fileName,
        bool $removeEmptyElements = true,
        string $delimiter = ',',
        array $header = [],
        bool $hasHeader = true
    ): array {
        $fileName = $this->fileHelper->determineFilePath($fileName);

        $this->logging->debug(sprintf('File path for filename: %s', $fileName));

        if (! $this->file->isFile($fileName)) {
            throw new FileSystemException(
                __('Could not read file: %1 because: Not a file', $fileName)
            );
        }

        $fileContent = $this->file->fileGetContents($fileName);

        $data = [];

        $handle = $this->file->fileOpen('php://temp', 'r+');

        $this->logging->debug('Removing BOM if required');

        $this->file->fileWrite($handle, str_replace("\xEF\xBB\xBF", '', $fileContent));

        $this->file->fileSeek($handle, 0);

        $this->logging->debug('Convert CSV to array');

        /** @noinspection PhpAssignmentInConditionInspection */
        while (is_array($row = $this->file->fileGetCsv($handle, 4096, $delimiter))) {
            // empty lines are returned as an array with a single null value
            if ($row === [null]) {
                continue;
            }

            if ($hasHeader && empty($header)) {
                $header = array_map(fn($value) => (string)$value, $row);
            } else {
                while (count($row) < count($header)) {
                    $row[] = '';
                }

                $data[] = $hasHeader ? array_combine($header, array_slice($row, 0, count($header))) : $row;
            }
        }

        $this->file->fileClose($handle);

        if ($removeEmptyElements) {
            $data = $this->arrayHelper->arrayFilterRecursive($data);
        }

        return $data;
    }
}
