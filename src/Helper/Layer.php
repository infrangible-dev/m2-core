<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Catalog\Model\Layer as CatalogLayer;
use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Catalog\Model\Layer\Resolver;

/**
 * The layered navigation of the current product list: of a category page or of a search result page.
 */
class Layer
{
    /**
     * @param Resolver $layerResolver
     */
    public function __construct(
        protected Resolver $layerResolver
    ) {
    }

    /**
     * Returns the layer of the current product list.
     *
     * The controller of the search result page creates the search layer, on all other pages it is the layer of the
     * category.
     *
     * @return CatalogLayer
     */
    public function getLayer(): CatalogLayer
    {
        return $this->layerResolver->get();
    }

    /**
     * Returns the applied filters of the layer, by default of the layer of the current product list.
     *
     * @param CatalogLayer|null $layer
     *
     * @return list<Item>
     */
    public function getActiveFilters(?CatalogLayer $layer = null): array
    {
        $filters = [];

        foreach (($layer ?? $this->getLayer())->getState()->getFilters() as $filter) {
            if ($filter instanceof Item) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }
}
