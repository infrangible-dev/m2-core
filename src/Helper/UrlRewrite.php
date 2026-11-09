<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite as UrlRewriteData;

class UrlRewrite
{
    /**
     * @param UrlFinderInterface $urlFinder
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteFactory $urlRewriteResourceFactory
     */
    public function __construct(
        protected UrlFinderInterface $urlFinder,
        protected UrlRewriteFactory $urlRewriteFactory,
        protected \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteFactory $urlRewriteResourceFactory
    ) {
    }

    /**
     * Creates a new URL rewrite.
     *
     * @return \Magento\UrlRewrite\Model\UrlRewrite
     */
    public function newUrlRewrite(): \Magento\UrlRewrite\Model\UrlRewrite
    {
        return $this->urlRewriteFactory->create();
    }

    /**
     * Loads the URL rewrite of the request path in the store.
     *
     * @param string $requestPath
     * @param int $storeId
     *
     * @return \Magento\UrlRewrite\Model\UrlRewrite
     */
    public function loadUrlRewrite(string $requestPath, int $storeId): \Magento\UrlRewrite\Model\UrlRewrite
    {
        $rewriteData = $this->urlFinder->findOneByData(
            [
                UrlRewriteData::REQUEST_PATH => $requestPath,
                UrlRewriteData::STORE_ID => $storeId,
            ]
        );

        $rewrite = $this->newUrlRewrite();

        if ($rewriteData) {
            $this->urlRewriteResourceFactory->create()->load($rewrite, $rewriteData->getUrlRewriteId());
        }

        return $rewrite;
    }

    /**
     * Returns the URL rewrite of the category in the store.
     *
     * @param int $categoryId
     * @param int $storeId
     *
     * @return \Magento\UrlRewrite\Model\UrlRewrite
     */
    public function getCategoryUrlRewrite(int $categoryId, int $storeId): \Magento\UrlRewrite\Model\UrlRewrite
    {
        $rewriteData = $this->urlFinder->findOneByData(
            [
                UrlRewriteData::ENTITY_ID => $categoryId,
                UrlRewriteData::ENTITY_TYPE => CategoryUrlRewriteGenerator::ENTITY_TYPE,
                UrlRewriteData::STORE_ID => $storeId,
            ]
        );

        $rewrite = $this->newUrlRewrite();

        if ($rewriteData) {
            $this->urlRewriteResourceFactory->create()->load($rewrite, $rewriteData->getUrlRewriteId());
        }

        return $rewrite;
    }

    /**
     * Returns the URL rewrite of the product in the store.
     *
     * @param int $productId
     * @param int $storeId
     *
     * @return \Magento\UrlRewrite\Model\UrlRewrite
     */
    public function getProductUrlRewrite(int $productId, int $storeId): \Magento\UrlRewrite\Model\UrlRewrite
    {
        $rewriteData = $this->urlFinder->findOneByData(
            [
                UrlRewriteData::ENTITY_ID => $productId,
                UrlRewriteData::ENTITY_TYPE => ProductUrlRewriteGenerator::ENTITY_TYPE,
                UrlRewriteData::STORE_ID => $storeId,
            ]
        );

        $rewrite = $this->newUrlRewrite();

        if ($rewriteData) {
            $this->urlRewriteResourceFactory->create()->load($rewrite, $rewriteData->getUrlRewriteId());
        }

        return $rewrite;
    }
}
