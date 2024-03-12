<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\Core\Helper;

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

    /**
     * @param LoggerInterface           $logging
     * @param TypeListInterface         $typeList
     * @param ReinitableConfigInterface $reinitableConfig
     * @param CacheInterface            $cache
     * @param Config                    $pageCacheConfig
     */
    public function __construct(
        LoggerInterface $logging,
        TypeListInterface $typeList,
        ReinitableConfigInterface $reinitableConfig,
        CacheInterface $cache,
        Config $pageCacheConfig)
    {
        $this->logging = $logging;
        $this->typeList = $typeList;
        $this->reinitableConfig = $reinitableConfig;
        $this->cache = $cache;
        $this->pageCacheConfig = $pageCacheConfig;
    }

    /**
     * @return void
     */
    public function cleanConfigCache()
    {
        $this->logging->info('Cleaning config cache');

        // only clean config cache to load the current configuration, leave all other caches as they are
        $this->typeList->cleanType('config');

        // to be sure that the current configuration is loaded
        $this->reinitableConfig->reinit();
    }

    /**
     * @return void
     */
    public function cleanBlockCache()
    {
        $this->logging->info('Cleaning block cache');

        $this->typeList->cleanType('block_html');
    }

    /**
     * @return void
     */
    public function cleanFullPageCache()
    {
        if ($this->pageCacheConfig->isEnabled()) {
            $this->logging->info('Cleaning full page cache');

            $this->typeList->cleanType('full_page');
        }
    }

    /**
     * @return void
     */
    public function cleanLayoutCache()
    {
        $this->logging->info('Cleaning layout cache');

        $this->typeList->cleanType('layout');
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public function loadCache(string $id): string
    {
        return $this->cache->load($id);
    }

    /**
     * @param string   $data
     * @param string   $id
     * @param array    $tags
     * @param int|null $lifeTime
     */
    public function saveCache(string $data, string $id, array $tags = [], int $lifeTime = null)
    {
        $this->cache->save($data, $id, $tags, $lifeTime);
    }

    /**
     * @param string $id
     */
    public function removeCache(string $id)
    {
        $this->cache->remove($id);
    }
}
