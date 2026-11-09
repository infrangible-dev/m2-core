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
use InvalidArgumentException;
use Magento\Config\Model\Config\Backend\Image\Logo;
use Magento\Config\Model\ResourceModel\ConfigFactory;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Psr\Log\LoggerInterface;

class Stores extends AbstractHelper
{
    /** @var ResolverInterface|null */
    protected ?ResolverInterface $localeResolver = null;

    /**
     * @param Context $context
     * @param Variables $variables
     * @param Arrays $arrays
     * @param Database $databaseHelper
     * @param Instances $instanceHelper
     * @param LoggerInterface $logging
     * @param StoreManagerInterface $storeManager
     * @param ConfigFactory $configFactory
     * @param Filesystem $filesystem
     * @param Repository $assetRepository
     * @param RequestInterface $request
     * @param PriceCurrencyInterface $priceCurrency
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        Context $context,
        protected Variables $variables,
        protected Arrays $arrays,
        protected Database $databaseHelper,
        protected Instances $instanceHelper,
        protected LoggerInterface $logging,
        protected StoreManagerInterface $storeManager,
        protected ConfigFactory $configFactory,
        protected Filesystem $filesystem,
        protected Repository $assetRepository,
        protected RequestInterface $request,
        protected PriceCurrencyInterface $priceCurrency,
        protected TimezoneInterface $timezoneInterface
    ) {
        parent::__construct($context);
    }

    /**
     * Returns a config value of the store, or the default value if it is not set.
     *
     * @param string $path
     * @param mixed $defaultValue
     * @param bool $isFlag
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function getStoreConfig(
        string $path,
        mixed $defaultValue = null,
        bool $isFlag = false,
        ?int $storeId = null
    ): mixed {
        try {
            $store = $this->getStore($storeId);

            $value = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $store->getCode()
            );

            if ($isFlag === true && $value !== null) {
                $value = $this->scopeConfig->isSetFlag(
                    $path,
                    ScopeInterface::SCOPE_STORE,
                    $store->getCode()
                );
            }

            $value ??= $defaultValue;

            return $value;
        } catch (NoSuchEntityException) {
            return $defaultValue;
        }
    }

    /**
     * Returns a config flag of the store.
     *
     * @param string $path
     * @param bool $defaultValue
     * @param int|null $storeId
     *
     * @return bool
     */
    public function getStoreConfigFlag(string $path, bool $defaultValue = false, ?int $storeId = null): bool
    {
        return $this->getStoreConfig(
            $path,
            $defaultValue,
            true,
            $storeId
        ) === true;
    }

    /**
     * Returns a config value of the store.
     *
     * @param string $path
     * @param mixed $defaultValue
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function getStoreConfigValue(string $path, mixed $defaultValue = null, ?int $storeId = null): mixed
    {
        return $this->getStoreConfig(
            $path,
            $defaultValue,
            false,
            $storeId
        );
    }

    /**
     * Returns a config value of the website, or the default value if it is not set.
     *
     * @param string $path
     * @param mixed $defaultValue
     * @param bool $isFlag
     * @param int|null $websiteId
     *
     * @return mixed
     */
    public function getWebsiteConfig(
        string $path,
        mixed $defaultValue = null,
        bool $isFlag = false,
        ?int $websiteId = null
    ): mixed {
        try {
            $websiteCode = $this->getWebsite($websiteId)->getCode();
            $websiteCode = is_string($websiteCode) ? $websiteCode : null;

            $value = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            );

            if ($isFlag === true && $value !== null) {
                $value = $this->scopeConfig->isSetFlag(
                    $path,
                    ScopeInterface::SCOPE_WEBSITE,
                    $websiteCode
                );
            }

            $value ??= $defaultValue;

            return $value;
        } catch (LocalizedException) {
            return $defaultValue;
        }
    }

    /**
     * Returns a config flag of the website.
     *
     * @param string $path
     * @param bool $defaultValue
     * @param int|null $websiteId
     *
     * @return bool
     */
    public function getWebsiteConfigFlag(string $path, bool $defaultValue = false, ?int $websiteId = null): bool
    {
        return $this->getWebsiteConfig(
            $path,
            $defaultValue,
            true,
            $websiteId
        ) === true;
    }

    /**
     * Returns a config value of the website.
     *
     * @param string $path
     * @param mixed $defaultValue
     * @param int|null $websiteId
     *
     * @return mixed
     */
    public function getWebsiteConfigValue(string $path, mixed $defaultValue = null, ?int $websiteId = null): mixed
    {
        return $this->getWebsiteConfig(
            $path,
            $defaultValue,
            false,
            $websiteId
        );
    }

    /**
     * Returns a config value of the default scope, or the default value if it is not set.
     *
     * @param string $path
     * @param mixed $defaultValue
     * @param bool $isFlag
     *
     * @return mixed
     */
    public function getDefaultConfig(string $path, mixed $defaultValue = null, bool $isFlag = false): mixed
    {
        $value = $this->scopeConfig->getValue(
            $path,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        if ($isFlag === true && $value !== null) {
            $value = $this->scopeConfig->isSetFlag(
                $path,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }

        $value ??= $defaultValue;

        return $value;
    }

    /**
     * Returns a config flag of the default scope.
     *
     * @param string $path
     * @param bool $defaultValue
     *
     * @return bool
     */
    public function getDefaultConfigFlag(string $path, bool $defaultValue = false): bool
    {
        return $this->getDefaultConfig(
            $path,
            $defaultValue,
            true
        ) === true;
    }

    /**
     * Returns a config value of the default scope.
     *
     * @param string $path
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getDefaultConfigValue(string $path, mixed $defaultValue = null): mixed
    {
        return $this->getDefaultConfig(
            $path,
            $defaultValue
        );
    }

    /**
     * Returns the store config value split into its trimmed parts.
     *
     * The value is split at the delimiter, or at line breaks if it does not contain the delimiter.
     *
     * @param string $configPath
     * @param string $delimiter
     * @param int|null $storeId
     *
     * @phpstan-param non-empty-string $delimiter
     *
     * @return string[]
     */
    public function getExplodedConfigValues(string $configPath, string $delimiter = ',', ?int $storeId = null): array
    {
        $valueString = $this->getStoreConfig(
            $configPath,
            null,
            false,
            $storeId
        );

        if (! $this->variables->isEmpty($valueString)) {
            $valueString = $this->variables->stringValue($valueString);

            $delimiterPosition = strpos(
                $valueString,
                $delimiter
            );

            if ($delimiterPosition !== false) {
                $values = explode(
                    $delimiter,
                    $valueString
                );

                return array_map(
                    'trim',
                    $values
                );
            } elseif (preg_match(
                '/\n/',
                $valueString
            )) {
                $values = explode(
                    "\n",
                    $valueString
                );

                return array_map(
                    'trim',
                    $values
                );
            } else {
                return [trim($valueString)];
            }
        }

        return [];
    }

    /**
     * Moves the config values of a path to another path, in every scope.
     *
     * Values that already exist at the new path are overwritten.
     *
     * @param string $oldPath
     * @param string $newPath
     *
     * @return void
     */
    public function moveConfigValue(string $oldPath, string $newPath): void
    {
        $writeAdapter = $this->databaseHelper->getDefaultConnection();

        $tableName = $this->databaseHelper->getTableName('core_config_data');

        $oldQuery = $writeAdapter->select()->from($tableName);

        $oldQuery->where(
            'path = ?',
            $oldPath
        );

        foreach ($writeAdapter->fetchAll($oldQuery) as $oldData) {
            $this->moveConfigRow(
                $writeAdapter,
                $tableName,
                $oldData,
                $newPath
            );
        }
    }

    /**
     * Moves all config values of a module (the first part of the path) to another module, in every scope.
     *
     * Values that already exist at the new path are overwritten.
     *
     * @param string $oldModuleId
     * @param string $newModuleId
     *
     * @return void
     */
    public function moveModuleConfigValues(string $oldModuleId, string $newModuleId): void
    {
        $writeAdapter = $this->databaseHelper->getDefaultConnection();

        $tableName = $this->databaseHelper->getTableName('core_config_data');

        $oldQuery = $writeAdapter->select()->from($tableName);

        $oldQuery->where(
            'path like ?',
            sprintf(
                '%s/%%',
                $oldModuleId
            )
        );

        foreach ($writeAdapter->fetchAll($oldQuery) as $oldData) {
            $path = substr(
                $this->variables->stringValue($oldData[ 'path' ] ?? ''),
                strlen($oldModuleId) + 1
            );

            $this->moveConfigRow(
                $writeAdapter,
                $tableName,
                $oldData,
                sprintf(
                    '%s/%s',
                    $newModuleId,
                    $path
                )
            );
        }
    }

    /**
     * Moves a row of the table core_config_data to a new path, or overwrites the value of the row with the new path.
     *
     * @param AdapterInterface $writeAdapter
     * @param string $tableName
     * @param array $oldData
     * @param string $newPath
     *
     * @phpstan-param array<string, mixed> $oldData
     *
     * @return void
     */
    private function moveConfigRow(
        AdapterInterface $writeAdapter,
        string $tableName,
        array $oldData,
        string $newPath
    ): void {
        $oldConfigId = $this->variables->intValue($oldData[ 'config_id' ] ?? null);

        $newQuery = $writeAdapter->select()->from($tableName);

        $newQuery->where(
            'path = ?',
            $newPath
        );
        $newQuery->where(
            'scope = ?',
            $this->variables->stringValue($oldData[ 'scope' ] ?? '')
        );
        $newQuery->where(
            'scope_id = ?',
            $this->variables->intValue($oldData[ 'scope_id' ] ?? null)
        );

        $newData = $writeAdapter->fetchRow($newQuery);

        if (is_array($newData) && ! $this->variables->isEmpty($newData)) {
            $writeAdapter->update(
                $tableName,
                ['value' => $oldData[ 'value' ] ?? null],
                ['config_id = ?' => $this->variables->intValue($newData[ 'config_id' ] ?? null)]
            );

            $writeAdapter->delete(
                $tableName,
                ['config_id = ?' => $oldConfigId]
            );
        } else {
            $writeAdapter->update(
                $tableName,
                ['path' => $newPath],
                ['config_id = ?' => $oldConfigId]
            );
        }
    }

    /**
     * Saves a config value in the given scope. Array values are saved comma separated.
     *
     * @param string $path
     * @param mixed $value
     * @param string $scope
     * @param int $scopeId
     *
     * @return void
     * @throws InvalidArgumentException if the value is neither scalar, array nor null
     */
    public function insertConfigValue(string $path, mixed $value, string $scope = 'default', int $scopeId = 0): void
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        } elseif (is_scalar($value)) {
            $value = (string)$value;
        } elseif ($value !== null) {
            throw new InvalidArgumentException(sprintf('Invalid value for config path: %s', $path));
        }

        $this->configFactory->create()->saveConfig(
            $path,
            // @phpstan-ignore argument.type (Magento saves null as NULL, which removes the value)
            $value,
            $scope,
            $scopeId
        );
    }

    /**
     * Removes the config values of the path in all scopes.
     *
     * @param string $path
     *
     * @return void
     */
    public function removeConfigValue(string $path): void
    {
        $this->databaseHelper->getDefaultConnection()->delete(
            $this->databaseHelper->getTableName('core_config_data'),
            ['path = ?' => $path]
        );
    }

    /**
     * Returns the store with the ID or code, or the current store.
     *
     * @param int|string|null $storeId
     *
     * @return Store
     * @throws NoSuchEntityException
     */
    public function getStore(int|string|null $storeId = null): Store
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore($storeId);

        return $store;
    }

    /**
     * Returns the ID of the current store, or the ID of the admin store if there is no current store.
     *
     * @return int
     */
    public function getStoreId(): int
    {
        try {
            /** @var Store $store */
            $store = $this->storeManager->getStore();

            return $this->variables->intValue($store->getId());
        } catch (NoSuchEntityException|Exception $exception) {
            $this->logging->error($exception->getMessage());

            return Store::DEFAULT_STORE_ID;
        }
    }

    /**
     * Returns the default store of the website, or of the current website.
     *
     * @param int|null $websiteId
     *
     * @return Store
     * @throws LocalizedException
     */
    public function getDefaultStore(?int $websiteId = null): Store
    {
        $website = $this->getWebsite($websiteId);

        $group = $this->storeManager->getGroup($this->variables->stringValue($website->getDefaultGroupId()));

        return $this->getStore($group->getDefaultStoreId());
    }

    /**
     * Returns the website with the ID or code, or the current website.
     *
     * @param int|string|null $websiteId
     *
     * @return Website
     * @throws LocalizedException
     */
    public function getWebsite(int|string|null $websiteId = null): Website
    {
        /** @var Website $website */
        $website = $this->storeManager->getWebsite($websiteId);

        return $website;
    }

    /**
     * Returns the ID of the current website, or 0 if there is no current website.
     *
     * @return int
     */
    public function getWebsiteId(): int
    {
        try {
            /** @var Website $website */
            $website = $this->storeManager->getWebsite();

            return $this->variables->intValue($website->getId());
        } catch (NoSuchEntityException|Exception $exception) {
            $this->logging->error($exception->getMessage());

            return 0;
        }
    }

    /**
     * Returns all websites.
     *
     * @param bool $withDefault
     * @param bool $codeKey
     *
     * @return Website[]
     */
    public function getWebsites(bool $withDefault = false, bool $codeKey = false): array
    {
        /** @var Website[] $websites */
        $websites = $this->storeManager->getWebsites(
            $withDefault,
            $codeKey
        );

        return $websites;
    }

    /**
     * Returns all store views.
     *
     * @param bool $withDefault
     * @param bool $codeKey
     *
     * @return Store[]
     */
    public function getStores(bool $withDefault = false, bool $codeKey = false): array
    {
        /** @var Store[] $stores */
        $stores = $this->storeManager->getStores(
            $withDefault,
            $codeKey
        );

        return $stores;
    }

    /**
     * Returns the base web URL of the current store, or of the default store if there is no current store.
     *
     * @return string
     */
    public function getWebUrl(): string
    {
        $store = null;

        try {
            $store = $this->getStore();
        } catch (NoSuchEntityException) {
            try {
                $store = $this->getDefaultStore();
            } catch (LocalizedException $exception) {
                $this->logging->error($exception->getMessage());
            }
        }

        return $store !== null ? $store->getBaseUrl(UrlInterface::URL_TYPE_WEB) : '';
    }

    /**
     * Returns the base media URL of the current store, or of the default store if there is no current store.
     *
     * @return string
     */
    public function getMediaUrl(): string
    {
        $store = null;

        try {
            $store = $this->getStore();
        } catch (NoSuchEntityException) {
            try {
                $store = $this->getDefaultStore();
            } catch (LocalizedException $exception) {
                $this->logging->error($exception->getMessage());
            }
        }

        return $store !== null ? $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) : '';
    }

    /**
     * Returns the URL of the logo configured for the store, or of the logo of the theme.
     *
     * @return string
     */
    public function getSiteLogo(): string
    {
        $folderName = Logo::UPLOAD_DIR;

        $storeLogoPath = $this->getStoreConfig('design/header/logo_src');

        $path = $folderName . '/' . $storeLogoPath;

        $logoUrl = $this->getMediaUrl() . $path;

        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        return $storeLogoPath !== null && $mediaDirectory->isFile($path) ? $logoUrl :
            $this->assetRepository->getUrlWithParams(
                'images/logo.svg',
                ['_secure' => $this->request->isSecure()]
            );
    }

    /**
     * Checks whether the single store mode is active.
     *
     * @return bool
     */
    public function isSingleStoreMode(): bool
    {
        return $this->storeManager->isSingleStoreMode();
    }

    /**
     * Imports config values of the store (the default scope for the admin store), grouped by section and group.
     *
     * The values of the sections in $resetSections are removed before.
     *
     * @param Store $store
     * @param array $data
     * @param array $resetSections
     *
     * @phpstan-param array<string, array<string, array<string, mixed>>> $data
     * @phpstan-param string[] $resetSections
     *
     * @return void
     * @throws CouldNotDeleteException
     */
    public function import(Store $store, array $data, array $resetSections = []): void
    {
        $storeId = $this->variables->intValue($store->getId());
        $isDefault = $storeId === 0;

        foreach ($data as $section => $sectionData) {
            if (in_array(
                $section,
                $resetSections
            )) {
                $this->logging->info(
                    sprintf(
                        'Resetting section: %s',
                        $section
                    )
                );

                if ($isDefault) {
                    $this->databaseHelper->deleteTableData(
                        $this->databaseHelper->getDefaultConnection(),
                        $this->databaseHelper->getTableName('core_config_data'),
                        sprintf(
                            'path like "%s/%%"',
                            $section
                        )
                    );
                } else {
                    $this->databaseHelper->deleteTableData(
                        $this->databaseHelper->getDefaultConnection(),
                        $this->databaseHelper->getTableName('core_config_data'),
                        sprintf(
                            'path like "%s/%%" AND scope = "stores" AND scope_id = %d',
                            $section,
                            $storeId
                        )
                    );
                }
            }

            $this->logging->info(
                sprintf(
                    'Importing section: %s',
                    $section
                )
            );

            foreach ($sectionData as $group => $groupData) {
                $this->logging->info(
                    sprintf(
                        'Importing group: %s/%s',
                        $section,
                        $group
                    )
                );

                foreach ($groupData as $field => $value) {
                    $this->insertConfigValue(
                        sprintf(
                            '%s/%s/%s',
                            $section,
                            $group,
                            $field
                        ),
                        $value,
                        $isDefault ? 'default' : 'stores',
                        $isDefault ? 0 : $storeId
                    );
                }
            }
        }
    }

    /**
     * Exports the config values of the sections for the store.
     *
     * @param Store $store
     * @param array $sections
     *
     * @phpstan-param string[] $sections
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    public function export(Store $store, array $sections): array
    {
        $data = [];

        foreach ($sections as $section) {
            $data[ $section ] = $this->getStoreConfig(
                $section,
                [],
                false,
                $this->variables->intValue($store->getId())
            );
        }

        return $data;
    }

    /**
     * Parses a number formatted in the default locale of the store.
     *
     * @param string $value
     *
     * @return int|float|false false if the value is not a number
     */
    public function getNumber(string $value): int|float|false
    {
        $locale = $this->variables->stringValue(
            $this->getStoreConfig(
                Data::XML_PATH_DEFAULT_LOCALE,
                'en_US'
            )
        );

        $formatter = \NumberFormatter::create(
            $locale,
            \NumberFormatter::DECIMAL
        );

        return $formatter->parse($value);
    }

    /**
     * Formats a number in the default locale of the store.
     *
     * @param float $value
     * @param int $precision
     *
     * @return string
     */
    public function formatNumber(float $value, int $precision = 2): string
    {
        $locale = $this->variables->stringValue(
            $this->getStoreConfig(
                Data::XML_PATH_DEFAULT_LOCALE,
                'en_US'
            )
        );

        $formatter = \NumberFormatter::create(
            $locale,
            \NumberFormatter::DECIMAL
        );
        $formatter->setAttribute(
            \NumberFormatter::FRACTION_DIGITS,
            $precision
        );

        $formattedValue = $formatter->format($value);

        // older PHP stubs declare string|bool instead of string|false
        return is_string($formattedValue) ? $formattedValue : (string)$value;
    }

    /**
     * Formats a price in the currency of the current store.
     *
     * @param float $price
     * @param bool $includeContainer
     *
     * @return string
     */
    public function formatPrice(float $price, bool $includeContainer = true): string
    {
        try {
            return $this->priceCurrency->format(
                $price,
                $includeContainer,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $this->getStore()
            );
        } catch (NoSuchEntityException $exception) {
            $this->logging->error($exception);
        }

        return (string)$price;
    }

    /**
     * Rounds a price to two decimals.
     *
     * @param int|float $price
     *
     * @return float
     */
    public function roundPrice(int|float $price): float
    {
        return round(
            $price,
            2,
            PHP_ROUND_HALF_UP
        );
    }

    /**
     * Converts a price from the base currency to the current currency of the store, optionally formatted.
     *
     * @param float $price
     * @param bool $format
     *
     * @return float|string
     */
    public function convertPrice(float $price, bool $format = false): float|string
    {
        $value = $price;

        try {
            $store = $this->getStore();

            $value = $store->getBaseCurrency()->convert(
                $price,
                $store->getCurrentCurrency()
            );

            if ($format) {
                $value = $this->formatPrice(
                    $value,
                    false
                );
            }
        } catch (NoSuchEntityException|Exception $exception) {
            $this->logging->error($exception);
        }

        return $value;
    }

    /**
     * Returns the locale of the current request.
     *
     * @return string
     */
    public function getLocale(): string
    {
        $this->localeResolver ??= $this->instanceHelper->getSharedInstance(ResolverInterface::class);

        return $this->localeResolver->getLocale();
    }

    /**
     * Returns a date in the timezone of the store.
     *
     * @param mixed $date
     * @param bool $useTimezone
     * @param bool $includeTime
     *
     * @return \DateTime
     */
    public function getDate(mixed $date = null, bool $useTimezone = true, bool $includeTime = true): \DateTime
    {
        return $this->timezoneInterface->date(
            $date,
            null,
            $useTimezone,
            $includeTime
        );
    }
}
