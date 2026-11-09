<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Attribute\Product;

use Infrangible\Core\Model\Config\Source\Attribute\Product;
use Infrangible\Core\Model\Layer\Category\FilterableAttributeList;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

/**
 * Options of the filterable product attributes with their id as value.
 */
class Filterable extends Product
{
    /** @var FilterableAttributeList */
    protected FilterableAttributeList $filterableAttributeList;

    /**
     * @param CollectionFactory $productAttributeCollectionFactory
     * @param FilterableAttributeList $filterableAttributeList
     */
    public function __construct(
        CollectionFactory $productAttributeCollectionFactory,
        FilterableAttributeList $filterableAttributeList
    ) {
        parent::__construct($productAttributeCollectionFactory);

        $this->filterableAttributeList = $filterableAttributeList;
    }

    /**
     * Returns the filterable product attributes.
     *
     * @return Attribute[]
     */
    protected function getList(): array
    {
        $list = $this->filterableAttributeList->getList();

        $items = is_array($list) ? $list : $list->getItems();

        return array_filter(
            $items,
            function ($item): bool {
                return $item instanceof Attribute;
            }
        );
    }
}
