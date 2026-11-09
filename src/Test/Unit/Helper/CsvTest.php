<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Csv;
use Infrangible\Core\Helper\Files;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests reading CSV files with the Magento file driver.
 */
class CsvTest extends TestCase
{
    /** @var string */
    private string $fileName = '';

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->fileName !== '' && is_file($this->fileName)) {
            unlink($this->fileName);
        }
    }

    /**
     * Returns the helper, which resolves file names to themselves.
     *
     * @return Csv
     */
    private function createCsv(): Csv
    {
        $files = $this->createStub(Files::class);
        $files->method('determineFilePath')->willReturnArgument(0);

        $variables = new Variables();

        return new Csv($files, $variables, new Arrays($variables), new NullLogger(), new File());
    }

    /**
     * Writes the content into a temporary file and returns its name.
     *
     * @param string $content
     *
     * @return string
     */
    private function writeFile(string $content): string
    {
        $fileName = tempnam(sys_get_temp_dir(), 'csv');

        $this->assertIsString($fileName);

        file_put_contents($fileName, $content);

        return $this->fileName = $fileName;
    }

    /**
     * Provides CSV contents and the rows expected with the header of the first line.
     *
     * @return array<string, array{string, array<int, array<string, string>>}>
     */
    public static function provideFiles(): array
    {
        return [
            'simple'          => [
                "sku,name\nA-1,First\nB-2,Second\n",
                [['sku' => 'A-1', 'name' => 'First'], ['sku' => 'B-2', 'name' => 'Second']]
            ],
            'byte order mark' => [
                "\xEF\xBB\xBFsku,name\nA-1,First\n",
                [['sku' => 'A-1', 'name' => 'First']]
            ],
            'blank lines'     => [
                "sku,name\n\nA-1,First\n\n",
                [['sku' => 'A-1', 'name' => 'First']]
            ],
            'more columns'    => [
                "sku,name\nA-1,First,extra\n",
                [['sku' => 'A-1', 'name' => 'First']]
            ],
            'fewer columns'   => [
                "sku,name,color\nA-1,First\n",
                [['sku' => 'A-1', 'name' => 'First']]
            ],
            'quoted values'   => [
                "sku,name\nA-1,\"First, with comma\"\n",
                [['sku' => 'A-1', 'name' => 'First, with comma']]
            ]
        ];
    }

    /**
     * The rows are combined with the header of the first line, empty values are removed.
     *
     * @dataProvider provideFiles
     *
     * @param string $content
     * @param array $expected
     *
     * @phpstan-param array<int, array<string, string>> $expected
     *
     * @return void
     * @throws Exception
     * @throws FileSystemException
     */
    #[DataProvider('provideFiles')]
    public function testReadFile(string $content, array $expected): void
    {
        $this->assertSame($expected, $this->createCsv()->readFile($this->writeFile($content)));
    }

    /**
     * Empty values are kept on request, a given header is used for all lines.
     *
     * @return void
     * @throws Exception
     * @throws FileSystemException
     */
    public function testReadFileWithGivenHeaderAndEmptyValues(): void
    {
        $this->assertSame(
            [['sku' => 'A-1', 'name' => ''], ['sku' => 'B-2', 'name' => 'Second']],
            $this->createCsv()->readFile($this->writeFile("A-1;\nB-2;Second\n"), false, ';', ['sku', 'name'])
        );
    }

    /**
     * Without header, the rows are returned as lists.
     *
     * @return void
     * @throws Exception
     * @throws FileSystemException
     */
    public function testReadFileWithoutHeader(): void
    {
        $this->assertSame(
            [['A-1', 'First'], ['B-2', 'Second']],
            $this->createCsv()->readFile($this->writeFile("A-1,First\nB-2,Second\n"), true, ',', [], false)
        );
    }

    /**
     * A missing file leads to an exception.
     *
     * @return void
     * @throws Exception
     */
    public function testReadMissingFile(): void
    {
        $this->expectException(FileSystemException::class);

        $this->createCsv()->readFile(sys_get_temp_dir() . '/does-not-exist-' . uniqid() . '.csv');
    }
}
