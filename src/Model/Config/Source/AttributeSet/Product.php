<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\AttributeSet;

use Infrangible\Core\Helper\EntityType;
use Infrangible\Core\Model\Config\Source\AttributeSet;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;

/**
 * Options of the product attribute sets.
 */
class Product extends AttributeSet
{
    /**
     * @param EntityType $entityTypeHelper
     * @param CollectionFactory $attributeSetCollectionFactory
     */
    public function __construct(EntityType $entityTypeHelper, CollectionFactory $attributeSetCollectionFactory)
    {
        parent::__construct(
            $entityTypeHelper,
            $attributeSetCollectionFactory
        );

        $this->setProduct();
    }
}
