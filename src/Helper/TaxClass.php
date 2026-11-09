<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Tax\Model\ClassModel;
use Magento\Tax\Model\ResourceModel\TaxClass\Collection;
use Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory;

class TaxClass
{
    /** @var CollectionFactory */
    protected CollectionFactory $taxClassCollectionFactory;

    /**
     * @param CollectionFactory $taxClassCollectionFactory
     */
    public function __construct(CollectionFactory $taxClassCollectionFactory)
    {
        $this->taxClassCollectionFactory = $taxClassCollectionFactory;
    }

    /**
     * Returns the tax class collection.
     *
     * @return Collection
     */
    public function getTaxClassCollection(): Collection
    {
        return $this->taxClassCollectionFactory->create();
    }

    /**
     * Returns the tax class with the name and type (PRODUCT or CUSTOMER), an empty tax class if there is none.
     *
     * @param string $taxClassName
     * @param string $taxClassType
     *
     * @return ClassModel|null
     * @throws LocalizedException
     */
    public function getTaxClassByName(string $taxClassName, string $taxClassType = 'PRODUCT'): ?ClassModel
    {
        $taxClassCollection = $this->taxClassCollectionFactory->create();

        $taxClassCollection->addFieldToFilter('class_name', $taxClassName);
        $taxClassCollection->addFieldToFilter('class_type', $taxClassType);

        $taxClass = $taxClassCollection->getFirstItem();

        return $taxClass instanceof ClassModel ? $taxClass : null;
    }
}
