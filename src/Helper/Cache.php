<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Variables;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\PageCache\Model\Config;
use Psr\Log\LoggerInterface;

class Cache
{
    /**
     * @param LoggerInterface $logging
     * @param TypeListInterface $typeList
     * @param ReinitableConfigInterface $reinitableConfig
     * @param CacheInterface $cache
     * @param Config $pageCacheConfig
     * @param Variables $variables
     */
    public function __construct(
        protected LoggerInterface $logging,
        protected TypeListInterface $typeList,
        protected ReinitableConfigInterface $reinitableConfig,
        protected CacheInterface $cache,
        protected Config $pageCacheConfig,
        protected Variables $variables
    ) {
    }

    /**
     * Cleans the config cache and reloads the config.
     *
     * @return void
     */
    public function cleanConfigCache(): void
    {
        $this->logging->info('Cleaning config cache');

        // only clean the config cache to load the current configuration, leave all other caches as they are
        $this->typeList->cleanType('config');

        // to be sure that the current configuration is loaded
        $this->reinitableConfig->reinit();
    }

    /**
     * Marks the config cache as invalid, so that it has to be refreshed in the admin.
     *
     * @return void
     */
    public function invalidateConfigCache(): void
    {
        $this->typeList->invalidate('config');
    }

    /**
     * Cleans the block HTML cache.
     *
     * @return void
     */
    public function cleanBlockCache(): void
    {
        $this->logging->info('Cleaning block cache');

        $this->typeList->cleanType('block_html');
    }

    /**
     * Marks the block HTML cache as invalid, so that it has to be refreshed in the admin.
     *
     * @return void
     */
    public function invalidateBlockCache(): void
    {
        $this->typeList->invalidate('block_html');
    }

    /**
     * Cleans the full page cache, if it is enabled.
     *
     * @return void
     */
    public function cleanFullPageCache(): void
    {
        if ($this->pageCacheConfig->isEnabled()) {
            $this->logging->info('Cleaning full page cache');

            $this->typeList->cleanType('full_page');
        }
    }

    /**
     * Marks the full page cache as invalid, if it is enabled, so that it has to be refreshed in the admin.
     *
     * @return void
     */
    public function invalidateFullPageCache(): void
    {
        if ($this->pageCacheConfig->isEnabled()) {
            $this->typeList->invalidate('full_page');
        }
    }

    /**
     * Cleans the layout cache.
     *
     * @return void
     */
    public function cleanLayoutCache(): void
    {
        $this->logging->info('Cleaning layout cache');

        $this->typeList->cleanType('layout');
    }

    /**
     * Marks the layout cache as invalid, so that it has to be refreshed in the admin.
     *
     * @return void
     */
    public function invalidateLayoutCache(): void
    {
        $this->typeList->invalidate('layout');
    }

    /**
     * Loads a value from the cache.
     *
     * @param string $id
     *
     * @return string|null
     */
    public function loadCache(string $id): ?string
    {
        /** @var string|false $value */
        $value = $this->cache->load($id);

        return $value === false ? null : $this->variables->stringValue($value);
    }

    /**
     * Saves a value in the cache.
     *
     * @param string $data
     * @param string $id
     * @param array $tags
     * @param int|null $lifeTime
     *
     * @phpstan-param string[] $tags
     *
     * @return void
     */
    public function saveCache(string $data, string $id, array $tags = [], ?int $lifeTime = null): void
    {
        $this->cache->save(
            $data,
            $id,
            $tags,
            $lifeTime
        );
    }

    /**
     * Removes a value from the cache.
     *
     * @param string $id
     *
     * @return void
     */
    public function removeCache(string $id): void
    {
        $this->cache->remove($id);
    }
}
