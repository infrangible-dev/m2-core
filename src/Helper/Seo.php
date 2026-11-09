<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;

class Seo
{
    /** @var Stores */
    protected Stores $storeHelper;

    /** @var string|null */
    private ?string $seoSuffix = null;

    /**
     * @param Stores $storeHelper
     */
    public function __construct(Stores $storeHelper)
    {
        $this->storeHelper = $storeHelper;
    }

    /**
     * Returns the URL suffix of categories configured for the current store.
     *
     * @return string
     */
    public function getSeoSuffix(): string
    {
        if ($this->seoSuffix === null) {
            $seoSuffix = $this->storeHelper->getStoreConfig(CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX);

            $this->seoSuffix = is_scalar($seoSuffix) ? (string)$seoSuffix : '';
        }

        return $this->seoSuffix;
    }

    /**
     * Adds the URL suffix of categories to the path, if it does not end with it already.
     *
     * @param string $catPath
     *
     * @return string
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
     * Replaces the URL suffix of categories at the end of the path.
     *
     * @param string $catPath
     * @param string $replacement
     *
     * @return string
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
