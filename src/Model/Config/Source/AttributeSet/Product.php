<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\AttributeSet;

use Infrangible\Core\Helper\EntityType;
use Infrangible\Core\Model\Config\Source\AttributeSet;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Product
    extends AttributeSet
{
    /**
     * @param EntityType        $entityTypeHelper
     * @param CollectionFactory $attributeSetCollectionFactory
     */
    public function __construct(EntityType $entityTypeHelper, CollectionFactory $attributeSetCollectionFactory)
    {
        parent::__construct($entityTypeHelper, $attributeSetCollectionFactory);

        $this->setProduct();
    }
}
