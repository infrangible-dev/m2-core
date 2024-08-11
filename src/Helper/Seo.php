<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Seo
{
    /** @var Stores */
    protected $storeHelper;

    /** @var string */
    private $seoSuffix;

    public function __construct(Stores $storeHelper)
    {
        $this->storeHelper = $storeHelper;
    }

    public function getSeoSuffix(): string
    {
        if ($this->seoSuffix === null) {
            $this->seoSuffix =
                $this->storeHelper->getStoreConfig(CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX);
        }

        return $this->seoSuffix;
    }

    /**
     * replace seo category suffix
     */
    public function addSeoSuffix(string $catPath): string
    {
        $seoSuffix = $this->getSeoSuffix();

        if ($seoSuffix == '/') {
            return $catPath;
        }

        $seoSuffix = ltrim($seoSuffix, '.');

        $catPath = rtrim($catPath, '/');

        if (strcasecmp(substr($catPath, strlen($catPath) - strlen($seoSuffix)), $seoSuffix) !== 0) {
            $catPath .= '.' . $seoSuffix;
        }

        return $catPath;
    }

    /**
     * replace seo category suffix
     */
    public function replaceSeoSuffix(string $catPath, string $replacement = ''): string
    {
        $seoSuffix = $this->getSeoSuffix();

        if ($seoSuffix == '/') {
            return $catPath;
        }

        if (strcasecmp(substr($catPath, strlen($catPath) - strlen($seoSuffix)), $seoSuffix) === 0) {
            $catPath = substr($catPath, 0, strlen($catPath) - strlen($seoSuffix)) . $replacement;
        }

        return $catPath;
    }
}
