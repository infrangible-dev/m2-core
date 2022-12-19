<?php

namespace Infrangible\Core\Helper;

use Exception;
use Psr\Log\LoggerInterface;
use Tofex\Help\Arrays;
use Tofex\Help\Variables;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Csv
{
    /** @var Variables */
    protected $variableHelper;

    /** @var Arrays */
    protected $arrayHelper;

    /** @var Files */
    protected $fileHelper;

    /** @var LoggerInterface */
    protected $logging;

    /**
     * @param Files           $fileHelper
     * @param Variables       $modelsHelper
     * @param Arrays          $arrayHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Files $fileHelper,
        Variables $modelsHelper,
        Arrays $arrayHelper,
        LoggerInterface $logger)
    {
        $this->variableHelper = $modelsHelper;
        $this->arrayHelper = $arrayHelper;
        $this->fileHelper = $fileHelper;

        $this->logging = $logger;
    }

    /**
     * @param string $fileName
     * @param bool   $removeEmptyElements
     * @param string $delimiter
     * @param array  $header
     * @param bool   $hasHeader
     *
     * @return array
     * @throws Exception
     */
    public function readFile(
        string $fileName,
        bool $removeEmptyElements = true,
        string $delimiter = ',',
        array $header = [],
        bool $hasHeader = true): array
    {
        $fileName = $this->fileHelper->determineFilePath($fileName);

        $this->logging->debug(sprintf('File path for filename: %s', $fileName));

        $data = [];

        if (is_file($fileName)) {
            $fileContent = file_get_contents($fileName);

            if ($fileContent !== false) {

                $handle = fopen('php://temp', 'r+');

                $this->logging->debug('Removing BOM if required');

                fwrite($handle, str_replace("\xEF\xBB\xBF", '', $fileContent));

                rewind($handle);

                $this->logging->debug('Convert CSV to array');

                /** @noinspection PhpAssignmentInConditionInspection */
                while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
                    if ($this->variableHelper->isEmpty($row)) {
                        continue;
                    }

                    if ($hasHeader && empty($header)) {
                        $header = $row;
                    } else {
                        while (count($row) < count($header)) {
                            $row[] = '';
                        }

                        $data[] = $hasHeader ? array_combine($header, $row) : $row;
                    }
                }

                fclose($handle);

                if ($removeEmptyElements) {
                    $data = $this->arrayHelper->arrayFilterRecursive($data);
                }
            } else {
                throw new Exception(sprintf('Could not read file: %s because: Could not open file', $fileName));
            }

            return $data;
        } else {
            throw new Exception(sprintf('Could not read file: %s because: Not a file', $fileName));
        }
    }
}
