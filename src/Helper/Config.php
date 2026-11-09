<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use InvalidArgumentException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Imports and exports the configuration of all scopes (core_config_data) as JSON.
 */
class Config
{
    /** @var Arrays */
    protected Arrays $arrays;

    /** @var File */
    protected File $file;

    /** @var Stores */
    protected Stores $storeHelper;

    /** @var Json */
    protected Json $json;

    /** @var Variables */
    protected Variables $variables;

    /**
     * @param Arrays $arrays
     * @param File $file
     * @param Stores $storeHelper
     * @param Json $json
     * @param Variables $variables
     */
    public function __construct(
        Arrays $arrays,
        File $file,
        Stores $storeHelper,
        Json $json,
        Variables $variables
    ) {
        $this->arrays = $arrays;
        $this->file = $file;
        $this->storeHelper = $storeHelper;
        $this->json = $json;
        $this->variables = $variables;
    }

    /**
     * Imports the configuration from a JSON file in the format of exportConfigJsonFile().
     *
     * @param string $fileName
     *
     * @return void
     * @throws FileSystemException
     * @throws InvalidArgumentException if the configuration is not valid
     */
    public function importConfigJsonFile(string $fileName): void
    {
        if (! $this->file->isExists($fileName) || ! $this->file->isReadable($fileName)) {
            throw new FileSystemException(__('Could not read file: %1', $fileName));
        }

        $this->importConfigJsonString($this->file->fileGetContents($fileName));
    }

    /**
     * Imports the configuration from a JSON string in the format of exportConfigJsonFile().
     *
     * @param string $jsonString
     *
     * @return void
     * @throws InvalidArgumentException if the configuration is not valid
     */
    public function importConfigJsonString(string $jsonString): void
    {
        $config = $this->json->decode($jsonString);

        if (is_array($config) && $this->isValidConfig($config)) {
            /** @var array<string, array<int, array<int|string, mixed>>> $config */
            $this->importConfig($config);
        }
    }

    /**
     * Checks that the configuration has the structure scope => scope id => section => group => fields.
     *
     * @param array $config
     *
     * @phpstan-param array<int|string, mixed> $config
     *
     * @return bool
     * @throws InvalidArgumentException if the configuration is not valid
     */
    protected function isValidConfig(array $config): bool
    {
        foreach ($config as $scope => $scopesConfig) {
            if ($scope !== 'default' && $scope !== 'websites' && $scope !== 'stores') {
                throw new InvalidArgumentException(sprintf('Invalid configuration scope: %s', $scope));
            }

            if (! is_array($scopesConfig)) {
                throw new InvalidArgumentException(sprintf('Invalid config for scope: %s', $scope));
            }

            foreach ($scopesConfig as $scopeId => $scopeConfig) {
                if (! ctype_digit((string)$scopeId)) {
                    throw new InvalidArgumentException(sprintf('Invalid scope id: %s', $scopeId));
                }

                if (! is_array($scopeConfig)) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid config for scope: %s with id: %s', $scope, $scopeId)
                    );
                }

                foreach ($scopeConfig as $section => $sectionConfig) {
                    if (! is_array($sectionConfig)) {
                        throw new InvalidArgumentException(
                            sprintf(
                                'Invalid section config for scope: %s with id: %s and section: %s',
                                $scope,
                                $scopeId,
                                $section
                            )
                        );
                    }

                    foreach ($sectionConfig as $group => $groupConfig) {
                        if (! is_array($groupConfig)) {
                            throw new InvalidArgumentException(
                                sprintf(
                                    'Invalid group config for scope: %s with id: %s and section: %s, group: %s',
                                    $scope,
                                    $scopeId,
                                    $section,
                                    $group
                                )
                            );
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Imports the configuration, given as:
     *
     * [scope]
     *   [id]
     *     [section]
     *       [group]
     *         [field1]: [value1]
     *         [field2]: [value2]
     *
     * Numeric keys (for example of fields with a list of values) are integers in PHP arrays.
     *
     * @param array $config
     *
     * @phpstan-param array<string, array<int, array<int|string, mixed>>> $config
     *
     * @return void
     */
    public function importConfig(array $config): void
    {
        foreach ($config as $scope => $scopesConfig) {
            foreach ($scopesConfig as $scopeId => $scopeConfig) {
                $this->importScopeConfig($scope, $scopeId, $scopeConfig);
            }
        }
    }

    /**
     * Imports the configuration of one scope, given as section => group => fields.
     *
     * @param string $scope
     * @param int $scopeId
     * @param array $scopeConfig
     *
     * @phpstan-param array<int|string, mixed> $scopeConfig
     *
     * @return void
     */
    protected function importScopeConfig(string $scope, int $scopeId, array $scopeConfig): void
    {
        foreach ($scopeConfig as $key => $value) {
            $this->importValue($scope, $scopeId, [], (string)$key, $value);
        }
    }

    /**
     * Imports the value, or all values below it if it is an array, under the path of its keys.
     *
     * @param string $scope
     * @param int $scopeId
     * @param array $parentPath
     * @param string $key
     * @param mixed $value
     *
     * @phpstan-param array<int, string> $parentPath
     *
     * @return void
     */
    protected function importValue(string $scope, int $scopeId, array $parentPath, string $key, mixed $value): void
    {
        $valuePath = $parentPath;
        $valuePath[] = $key;

        if (is_array($value)) {
            foreach ($value as $valueKey => $valueValue) {
                $this->importValue($scope, $scopeId, $valuePath, (string)$valueKey, $valueValue);
            }
        } else {
            $path = implode('/', $valuePath);

            $this->storeHelper->insertConfigValue($path, $value, $scope, $scopeId);
        }
    }

    /**
     * Exports the configuration below the path as JSON file.
     *
     * Website and store values are only exported where they differ from the default or website value.
     *
     * @param string $fileName
     * @param string $path
     *
     * @return void
     * @throws FileSystemException
     */
    public function exportConfigJsonFile(string $fileName, string $path): void
    {
        $directoryName = $this->file->getParentDirectory($fileName);

        if (! $this->file->isDirectory($directoryName)) {
            $this->file->createDirectory($directoryName);
        }

        $output = $this->json->encode($this->getScopeConfig($path), true, true);

        if ($output === null) {
            throw new FileSystemException(
                __('Could not export configuration because: %1', json_last_error_msg())
            );
        }

        $this->file->filePutContents($fileName, $output);
    }

    /**
     * Returns the configuration below the path as scope => scope id => path elements => value.
     *
     * Website and store values are only included where they differ from the default or website value.
     *
     * @param string $path
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function getScopeConfig(string $path): array
    {
        $config = [];

        $pathElements = empty($path) ? [] : explode('/', $path);
        array_unshift($pathElements, '0');
        array_unshift($pathElements, 'default');

        $defaultConfig = $this->toArray($this->storeHelper->getStoreConfig($path, [], false, 0));

        if (! empty($defaultConfig)) {
            $config = $this->arrays->addDeepValue($config, $pathElements, $defaultConfig);
        }

        foreach ($this->storeHelper->getWebsites() as $website) {
            $websiteId = $this->variables->intValue($website->getId());

            $websiteConfig = $this->toArray($this->storeHelper->getWebsiteConfig($path, [], false, $websiteId));

            $websiteConfigDiff = $this->arrays->arrayDiffRecursive($defaultConfig, $websiteConfig);

            if (! empty($websiteConfigDiff)) {
                $pathElements = empty($path) ? [] : explode('/', $path);
                array_unshift($pathElements, (string)$websiteId);
                array_unshift($pathElements, 'websites');

                $config = $this->arrays->addDeepValue($config, $pathElements, $websiteConfigDiff);
            }

            foreach ($website->getStores() as $store) {
                $storeId = $this->variables->intValue($store->getId());

                $storeConfig = $this->toArray($this->storeHelper->getStoreConfig($path, [], false, $storeId));

                $storeConfigDiff = $this->arrays->arrayDiffRecursive($websiteConfig, $storeConfig);

                if (! empty($storeConfigDiff)) {
                    $pathElements = empty($path) ? [] : explode('/', $path);
                    array_unshift($pathElements, (string)$storeId);
                    array_unshift($pathElements, 'stores');

                    $config = $this->arrays->addDeepValue($config, $pathElements, $storeConfigDiff);
                }
            }
        }

        return $this->arrays->cleanStrings($config);
    }

    /**
     * Returns the configuration value as array: a single value below the path becomes an array with that value.
     *
     * @param mixed $value
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    private function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return $value === null ? [] : [$value];
    }
}
