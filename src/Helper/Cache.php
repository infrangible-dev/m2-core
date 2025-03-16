<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Variables;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\PageCache\Model\Config;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Cache
{
    /** @var LoggerInterface */
    protected $logging;

    /** @var TypeListInterface */
    protected $typeList;

    /** @var ReinitableConfigInterface */
    protected $reinitableConfig;

    /** @var CacheInterface */
    protected $cache;

    /** @var Config */
    protected $pageCacheConfig;

    /** @var Variables */
    protected $variables;

    public function __construct(
        LoggerInterface $logging,
        TypeListInterface $typeList,
        ReinitableConfigInterface $reinitableConfig,
        CacheInterface $cache,
        Config $pageCacheConfig,
        Variables $variables
    ) {
        $this->logging = $logging;
        $this->typeList = $typeList;
        $this->reinitableConfig = $reinitableConfig;
        $this->cache = $cache;
        $this->pageCacheConfig = $pageCacheConfig;
        $this->variables = $variables;
    }

    public function cleanConfigCache(): void
    {
        $this->logging->info('Cleaning config cache');

        // only clean config cache to load the current configuration, leave all other caches as they are
        $this->typeList->cleanType('config');

        // to be sure that the current configuration is loaded
        $this->reinitableConfig->reinit();
    }

    public function invalidateConfigCache(): void
    {
        $this->typeList->invalidate('config');
    }

    public function cleanBlockCache(): void
    {
        $this->logging->info('Cleaning block cache');

        $this->typeList->cleanType('block_html');
    }

    public function invalidateBlockCache(): void
    {
        $this->typeList->invalidate('block_html');
    }

    public function cleanFullPageCache(): void
    {
        if ($this->pageCacheConfig->isEnabled()) {
            $this->logging->info('Cleaning full page cache');

            $this->typeList->cleanType('full_page');
        }
    }

    public function invalidateFullPageCache(): void
    {
        if ($this->pageCacheConfig->isEnabled()) {
            $this->typeList->invalidate('full_page');
        }
    }

    public function cleanLayoutCache(): void
    {
        $this->logging->info('Cleaning layout cache');

        $this->typeList->cleanType('layout');
    }

    public function invalidateLayoutCache(): void
    {
        $this->typeList->invalidate('layout');
    }

    public function loadCache(string $id): ?string
    {
        /** @var string|false $value */
        $value = $this->cache->load($id);

        return $value === false ? null : $this->variables->stringValue($value);
    }

    public function saveCache(string $data, string $id, array $tags = [], int $lifeTime = null): void
    {
        $this->cache->save(
            $data,
            $id,
            $tags,
            $lifeTime
        );
    }

    public function removeCache(string $id): void
    {
        $this->cache->remove($id);
    }
}
