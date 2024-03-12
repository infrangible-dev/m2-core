<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Files;
use FeWeDev\Base\Variables;
use FeWeDev\Xml\Reader;
use FeWeDev\Xml\SimpleXml;
use FeWeDev\Xml\Writer;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Xml
{
    /** @var Files */
    protected $files;

    /** @var Arrays */
    protected $arrays;

    /** @var Variables */
    protected $variables;

    /** @var DirectoryList */
    protected $directoryList;

    /**
     * @param Files         $files
     * @param Arrays        $arrays
     * @param Variables     $variables
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Files $files,
        Arrays $arrays,
        Variables $variables,
        DirectoryList $directoryList
    ) {
        $this->files = $files;
        $this->arrays = $arrays;
        $this->variables = $variables;

        $this->directoryList = $directoryList;
    }

    /**
     * @param string $basePath
     * @param string $fileName
     * @param bool   $removeEmptyElements
     * @param int    $retries
     * @param int    $retryPause
     *
     * @return array
     * @throws Exception
     */
    public function read(
        string $basePath,
        string $fileName,
        bool $removeEmptyElements = true,
        int $retries = 0,
        int $retryPause = 250
    ): array {
        $simpleXml = new SimpleXml($this->variables);

        $xmlReader = new Reader($this->files, $this->arrays, $simpleXml);

        $xmlReader->setBasePath($basePath);
        $xmlReader->setFileName($fileName);

        return $xmlReader->read($removeEmptyElements, $retries, $retryPause);
    }

    /**
     * @param string $fileName
     * @param string $rootElement
     * @param array  $rootElementAttributes
     * @param array  $data
     * @param bool   $append
     * @param array  $characterDataElements
     * @param string $version
     * @param string $encoding
     *
     * @throws Exception
     */
    public function write(
        string $fileName,
        string $rootElement,
        array $rootElementAttributes,
        array $data,
        bool $append = false,
        array $characterDataElements = [],
        string $version = '1.0',
        string $encoding = 'UTF-8'
    ) {
        $xmlWriter = new Writer($this->files, $this->arrays, $this->variables);

        $xmlWriter->setBasePath($this->directoryList->getRoot());
        $xmlWriter->setFileName($fileName);

        foreach ($characterDataElements as $characterDataElement) {
            $xmlWriter->addForceCharacterData($characterDataElement);
        }

        $xmlWriter->write($rootElement, $rootElementAttributes, $data, $append, $version, $encoding);
    }

    /**
     * @param string $rootElement
     * @param array  $rootElementAttributes
     * @param array  $data
     * @param array  $characterDataElements
     * @param string $version
     * @param string $encoding
     *
     * @return string
     * @throws Exception
     */
    public function output(
        string $rootElement,
        array $rootElementAttributes,
        array $data,
        array $characterDataElements = [],
        string $version = '1.0',
        string $encoding = 'UTF-8'
    ): string {
        $xmlWriter = new Writer($this->files, $this->arrays, $this->variables);

        foreach ($characterDataElements as $characterDataElement) {
            $xmlWriter->addForceCharacterData($characterDataElement);
        }

        return $xmlWriter->output($rootElement, $rootElementAttributes, $data, $version, $encoding);
    }
}
