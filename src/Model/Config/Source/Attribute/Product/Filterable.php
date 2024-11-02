<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Attribute\Product;

use Infrangible\Core\Model\Config\Source\Attribute\Product;
use Infrangible\Core\Model\Layer\Category\FilterableAttributeList;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Filterable extends Product
{
    /** @var FilterableAttributeList */
    protected $filterableAttributeList;

    public function __construct(
        CollectionFactory $productAttributeCollectionFactory,
        FilterableAttributeList $filterableAttributeList
    ) {
        parent::__construct($productAttributeCollectionFactory);

        $this->filterableAttributeList = $filterableAttributeList;
    }

    /**
     * @return Attribute[]
     */
    protected function getList(): array
    {
        return $this->filterableAttributeList->getList()->getItems();
    }
}
