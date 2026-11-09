<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Helper;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Config;
use Infrangible\Core\Helper\Stores;
use Infrangible\Core\Test\Unit\CreatesValueStubs;
use InvalidArgumentException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests the export of configuration values and their import.
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigTest extends TestCase
{
    use CreatesValueStubs;

    /** @var Stores&MockObject */
    private MockObject $storeHelper;

    /** @var Config */
    private Config $config;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $variables = new Variables();

        $this->storeHelper = $this->createMock(Stores::class);
        $this->config = new Config(
            new Arrays($variables),
            new File(),
            $this->storeHelper,
            new Json(),
            $variables
        );
    }

    /**
     * Website and store values are exported where they differ from the default or website value.
     *
     * @return void
     */
    public function testGetScopeConfig(): void
    {
        $store1 = $this->createValueStub(Store::class, ['getId' => '1']);
        $store2 = $this->createValueStub(Store::class, ['getId' => '2']);
        $website = $this->createValueStub(Website::class, ['getId' => '1', 'getStores' => [$store1, $store2]]);

        $this->storeHelper->method('getWebsites')->willReturn([$website]);
        $this->storeHelper->method('getWebsiteConfig')->willReturn(['code' => 'de_DE', 'firstday' => '1']);
        $this->storeHelper->method('getStoreConfig')->willReturnMap([
            ['general/locale', [], false, 0, ['code' => 'en_US', 'firstday' => '1']],
            ['general/locale', [], false, 1, ['code' => 'de_DE', 'firstday' => '1']],
            ['general/locale', [], false, 2, ['code' => 'de_CH', 'firstday' => '1']]
        ]);

        $this->assertEquals(
            [
                'default'  => [0 => ['general' => ['locale' => ['code' => 'en_US', 'firstday' => '1']]]],
                'websites' => [1 => ['general' => ['locale' => ['code' => 'de_DE']]]],
                'stores'   => [2 => ['general' => ['locale' => ['code' => 'de_CH']]]]
            ],
            $this->config->getScopeConfig('general/locale')
        );
    }

    /**
     * An exported configuration is imported into the same scopes again.
     *
     * @return void
     */
    public function testImportOfExport(): void
    {
        $saved = [];

        $this->storeHelper->expects($this->exactly(3))
            ->method('insertConfigValue')
            ->willReturnCallback(
                function (string $path, $value, string $scope, int $scopeId) use (&$saved): void {
                    $saved[] = [$path, $value, $scope, $scopeId];
                }
            );

        $json = (string)json_encode(
            [
                'default'  => [0 => ['general' => ['locale' => ['code' => 'en_US']]]],
                'websites' => [1 => ['general' => ['locale' => ['code' => 'de_DE']]]],
                'stores'   => [2 => ['general' => ['locale' => ['code' => 'de_CH']]]]
            ]
        );

        $this->config->importConfigJsonString($json);

        $this->assertSame(
            [
                ['general/locale/code', 'en_US', 'default', 0],
                ['general/locale/code', 'de_DE', 'websites', 1],
                ['general/locale/code', 'de_CH', 'stores', 2]
            ],
            $saved
        );
    }

    /**
     * Values below numeric keys (for example serialized rows) are imported with the key in the path.
     *
     * @return void
     */
    public function testImportWithNumericKeys(): void
    {
        $this->storeHelper->expects($this->once())
            ->method('insertConfigValue')
            ->with('section/group/rows/0/value', 'first', 'default', 0);

        $this->config->importConfigJsonString('{"default":{"0":{"section":{"group":{"rows":[{"value":"first"}]}}}}}');
    }

    /**
     * Provides configurations with an invalid structure.
     *
     * @return array<string, array{string}>
     */
    public static function provideInvalidConfigs(): array
    {
        return [
            'scope'          => ['{"global":{"0":{"section":{"group":{"field":"1"}}}}}'],
            'scope id'       => ['{"default":{"main":{"section":{"group":{"field":"1"}}}}}'],
            'scope config'   => ['{"default":"value"}'],
            'section config' => ['{"default":{"0":{"section":"value"}}}'],
            'group config'   => ['{"default":{"0":{"section":{"group":"value"}}}}']
        ];
    }

    /**
     * Configurations with an invalid structure are not imported.
     *
     * @dataProvider provideInvalidConfigs
     *
     * @param string $json
     *
     * @return void
     */
    #[DataProvider('provideInvalidConfigs')]
    public function testImportInvalidConfig(string $json): void
    {
        $this->storeHelper->expects($this->never())->method('insertConfigValue');

        $this->expectException(InvalidArgumentException::class);

        $this->config->importConfigJsonString($json);
    }
}
