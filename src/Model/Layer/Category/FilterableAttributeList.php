<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Layer\Category;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Framework\Exception\LocalizedException;

/**
 * Filterable attributes of the category layer, sorted by label instead of position.
 */
class FilterableAttributeList extends \Magento\Catalog\Model\Layer\Category\FilterableAttributeList
{
    /**
     * Sorts the attributes by label and only returns filterable attributes.
     *
     * @param Collection $collection
     *
     * @return Collection
     * @throws LocalizedException
     */
    protected function _prepareAttributeCollection($collection): Collection
    {
        $collection->unshiftOrder(
            'frontend_label',
            \Magento\Framework\Data\Collection::SORT_ORDER_ASC
        );

        $collection->addFieldToFilter(
            'additional_table.is_filterable',
            ['gt' => 0]
        );

        return $collection;
    }
}
