<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Validator\UniversalFactory;
use Psr\Log\LoggerInterface;

class Url extends AbstractHelper
{
    /** @var \Magento\Framework\Url[] */
    private array $urls = [];

    /**
     * @param Context $context
     * @param Stores $storeHelper
     * @param LoggerInterface $logging
     * @param \Magento\Framework\Url $url
     * @param \Magento\Backend\Model\Url $backendUrl
     * @param UniversalFactory $universalFactory
     * @param FilterManager $filter
     */
    public function __construct(
        Context $context,
        protected Stores $storeHelper,
        protected LoggerInterface $logging,
        protected \Magento\Framework\Url $url,
        protected \Magento\Backend\Model\Url $backendUrl,
        protected UniversalFactory $universalFactory,
        protected FilterManager $filter
    ) {
        parent::__construct($context);
    }

    /**
     * Returns the URL model for the store.
     *
     * @param int $storeId
     *
     * @return \Magento\Framework\Url
     */
    protected function getUrlModel(int $storeId): \Magento\Framework\Url
    {
        if (! array_key_exists(
            $storeId,
            $this->urls
        )) {
            /** @var \Magento\Framework\Url $url */
            $url = $this->universalFactory->create(\Magento\Framework\Url::class);

            $url->setScope($storeId);

            $this->urls[ $storeId ] = $url;
        }

        return $this->urls[ $storeId ];
    }

    /**
     * Returns the frontend URL of the route, for the store if a store ID is given.
     *
     * Without $isSecure, the URL is secure if the current store uses secure URLs and the request is secure.
     *
     * @param string $route
     * @param bool|null $isSecure
     * @param array $params
     * @param int|null $storeId
     *
     * @phpstan-param array<string, mixed> $params
     *
     * @return string
     */
    public function getUrl(string $route = '', ?bool $isSecure = null, array $params = [], ?int $storeId = null): string
    {
        if (! array_key_exists(
            '_secure',
            $params
        )) {
            try {
                $store = $this->storeHelper->getStore();

                $params[ '_secure' ] = $isSecure === null ? $store->isFrontUrlSecure() && $store->isCurrentlySecure() :
                    $isSecure !== false;
            } catch (Exception $exception) {
                $this->logging->error($exception);
            }
        }

        $url = $storeId === null ? $this->url : $this->getUrlModel($storeId);

        return $url->getUrl(
            $route,
            $params
        );
    }

    /**
     * Returns the admin URL of the route.
     *
     * @param string $route
     * @param array $params
     *
     * @phpstan-param array<string, mixed> $params
     *
     * @return string
     */
    public function getBackendUrl(string $route = '', array $params = []): string
    {
        return $this->backendUrl->getUrl(
            $route,
            $params
        );
    }

    /**
     * Returns the frontend URL of the path (without routing), for the store if a store ID is given.
     *
     * @param string $url
     * @param array $params
     * @param int|null $storeId
     *
     * @phpstan-param array<string, mixed> $params
     *
     * @return string
     */
    public function getDirectUrl(string $url, array $params = [], int|null $storeId = null): string
    {
        if ($storeId === null) {
            $urlModel = $this->url;
        } else {
            $urlModel = $this->getUrlModel($storeId);
        }

        return $urlModel->getDirectUrl(
            $url,
            $params
        );
    }

    /**
     * Formats a string as URL key (transliterated, lower case, separated by dashes).
     *
     * @param string $str
     *
     * @return string
     */
    public function formatUrlKey(string $str): string
    {
        return $this->filter->translitUrl($str);
    }
}
