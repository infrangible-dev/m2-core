<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Tax\Model\ClassModel;
use Magento\Tax\Model\ResourceModel\TaxClass\Collection;
use Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class TaxClass
{
    /** @var CollectionFactory */
    protected $taxClassCollectionFactory;

    public function __construct(CollectionFactory $taxClassCollectionFactory)
    {
        $this->taxClassCollectionFactory = $taxClassCollectionFactory;
    }

    public function getTaxClassCollection(): Collection
    {
        return $this->taxClassCollectionFactory->create();
    }

    public function getTaxClassByName(string $taxClassName, string $taxClassType = 'PRODUCT'): ?ClassModel
    {
        $taxClassCollection = $this->taxClassCollectionFactory->create();

        $taxClassCollection->addFieldToFilter('class_name', $taxClassName);
        $taxClassCollection->addFieldToFilter('class_type', $taxClassType);

        $taxClass = $taxClassCollection->getFirstItem();

        return $taxClass instanceof ClassModel ? $taxClass : null;
    }
}
