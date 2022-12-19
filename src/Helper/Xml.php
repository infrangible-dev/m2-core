<?php

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Framework\Filesystem\DirectoryList;
use Tofex\Help\Arrays;
use Tofex\Help\Files;
use Tofex\Help\Variables;
use Tofex\Xml\Reader;
use Tofex\Xml\SimpleXml;
use Tofex\Xml\Writer;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Xml
{
    /** @var Files */
    protected $filesHelper;

    /** @var Arrays */
    protected $arrayHelper;

    /** @var Variables */
    protected $variableHelper;

    /** @var DirectoryList */
    protected $directoryList;

    /**
     * @param Files         $filesHelper
     * @param Arrays        $arrayHelper
     * @param Variables     $variableHelper
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Files $filesHelper,
        Arrays $arrayHelper,
        Variables $variableHelper,
        DirectoryList $directoryList)
    {
        $this->filesHelper = $filesHelper;
        $this->arrayHelper = $arrayHelper;
        $this->variableHelper = $variableHelper;

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
        int $retryPause = 250): array
    {
        $simpleXml = new SimpleXml($this->variableHelper);

        $xmlReader = new Reader($this->filesHelper, $this->arrayHelper, $simpleXml);

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
        string $encoding = 'UTF-8')
    {
        $xmlWriter = new Writer($this->filesHelper, $this->arrayHelper);

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
     */
    public function output(
        string $rootElement,
        array $rootElementAttributes,
        array $data,
        array $characterDataElements = [],
        string $version = '1.0',
        string $encoding = 'UTF-8'): string
    {
        $xmlWriter = new Writer($this->filesHelper, $this->arrayHelper);

        foreach ($characterDataElements as $characterDataElement) {
            $xmlWriter->addForceCharacterData($characterDataElement);
        }

        return $xmlWriter->output($rootElement, $rootElementAttributes, $data, $version, $encoding);
    }
}
