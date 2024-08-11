<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Validator\UniversalFactory;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Url extends AbstractHelper
{
    /** @var Stores */
    protected $storeHelper;

    /** @var LoggerInterface */
    protected $logging;

    /** @var \Magento\Framework\Url */
    protected $url;

    /** @var \Magento\Backend\Model\Url */
    protected $backendUrl;

    /** @var UniversalFactory */
    protected $universalFactory;

    /** @var FilterManager */
    protected $filter;

    /** @var \Magento\Framework\Url[] */
    private $urls = [];

    public function __construct(
        Context $context,
        Stores $storeHelper,
        LoggerInterface $logging,
        \Magento\Framework\Url $frontendUrl,
        \Magento\Backend\Model\Url $backendUrl,
        UniversalFactory $universalFactory,
        FilterManager $filter
    ) {
        parent::__construct($context);

        $this->storeHelper = $storeHelper;
        $this->logging = $logging;
        $this->url = $frontendUrl;
        $this->backendUrl = $backendUrl;
        $this->universalFactory = $universalFactory;
        $this->filter = $filter;
    }

    protected function getUrlModel(int $storeId): \Magento\Framework\Url
    {
        if (!array_key_exists($storeId, $this->urls)) {
            /** @var \Magento\Framework\Url $url */
            $url = $this->universalFactory->create(\Magento\Framework\Url::class);

            $url->setScope($storeId);

            $this->urls[$storeId] = $url;
        }

        return $this->urls[$storeId];
    }

    public function getUrl(string $route = '', bool $isSecure = null, array $params = [], int $storeId = null): string
    {
        if (!array_key_exists('_secure', $params)) {
            try {
                $store = $this->storeHelper->getStore();

                $params['_secure'] = $isSecure === null ? $store->isFrontUrlSecure() && $store->isCurrentlySecure() :
                    $isSecure !== false;
            } catch (Exception $exception) {
                $this->logging->error($exception);
            }
        }

        $url = $storeId === null ? $this->url : $this->getUrlModel($storeId);

        return $url->getUrl($route, $params);
    }

    public function getBackendUrl(string $route = '', array $params = []): string
    {
        return $this->backendUrl->getUrl($route, $params);
    }

    /**
     * @param null   $storeId
     */
    public function getDirectUrl(string $url, array $params = [], $storeId = null): string
    {
        if ($storeId === null) {
            $urlModel = $this->url;
        } else {
            $urlModel = $this->getUrlModel($storeId);
        }

        return $urlModel->getDirectUrl($url, $params);
    }

    /**
     * Format Key for URL
     */
    public function formatUrlKey(string $str): string
    {
        return $this->filter->translitUrl($str);
    }
}
