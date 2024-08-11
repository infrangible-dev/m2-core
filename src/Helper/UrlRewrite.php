<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Model\UrlRewriteFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class UrlRewrite
{
    /** @var UrlFinderInterface */
    protected $urlFinder;

    /** @var UrlRewriteFactory */
    protected $urlRewriteFactory;

    /** @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteFactory */
    protected $urlRewriteResourceFactory;

    public function __construct(
        UrlFinderInterface $urlFinder,
        UrlRewriteFactory $urlRewriteFactory,
        \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteFactory $urlRewriteResourceFactory
    ) {
        $this->urlFinder = $urlFinder;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->urlRewriteResourceFactory = $urlRewriteResourceFactory;
    }

    public function newUrlRewrite(): \Magento\UrlRewrite\Model\UrlRewrite
    {
        return $this->urlRewriteFactory->create();
    }

    public function loadUrlRewrite(string $requestPath, int $storeId): \Magento\UrlRewrite\Model\UrlRewrite
    {
        $rewriteData = $this->urlFinder->findOneByData([
                                                           \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::REQUEST_PATH => $requestPath,
                                                           \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::STORE_ID     => $storeId,
                                                       ]);

        $rewrite = $this->newUrlRewrite();

        if ($rewriteData) {
            $this->urlRewriteResourceFactory->create()->load($rewrite, $rewriteData->getUrlRewriteId());
        }

        return $rewrite;
    }

    public function getCategoryUrlRewrite(int $categoryId, int $storeId): \Magento\UrlRewrite\Model\UrlRewrite
    {
        $rewriteData = $this->urlFinder->findOneByData([
                                                           \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::ENTITY_ID   => $categoryId,
                                                           \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::ENTITY_TYPE => CategoryUrlRewriteGenerator::ENTITY_TYPE,
                                                           \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::STORE_ID    => $storeId,
                                                       ]);

        $rewrite = $this->newUrlRewrite();

        if ($rewriteData) {
            $this->urlRewriteResourceFactory->create()->load($rewrite, $rewriteData->getUrlRewriteId());
        }

        return $rewrite;
    }

    public function getProductUrlRewrite(int $productId, int $storeId): \Magento\UrlRewrite\Model\UrlRewrite
    {
        $rewriteData = $this->urlFinder->findOneByData([
                                                           \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::ENTITY_ID   => $productId,
                                                           \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::ENTITY_TYPE => ProductUrlRewriteGenerator::ENTITY_TYPE,
                                                           \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::STORE_ID    => $storeId,
                                                       ]);

        $rewrite = $this->newUrlRewrite();

        if ($rewriteData) {
            $this->urlRewriteResourceFactory->create()->load($rewrite, $rewriteData->getUrlRewriteId());
        }

        return $rewrite;
    }
}
