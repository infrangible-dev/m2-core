<?php

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Product
    implements OptionSourceInterface
{
    /** @var CollectionFactory */
    protected $productAttributeCollectionFactory;

    /**
     * @param CollectionFactory $productAttributeCollectionFactory
     */
    public function __construct(CollectionFactory $productAttributeCollectionFactory)
    {
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('--Please Select--')]];

        $productAttributeCollection = $this->productAttributeCollectionFactory->create();

        $productAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

        /** @var Attribute $catalogAttribute */
        foreach ($productAttributeCollection as $catalogAttribute) {
            $frontendLabel = $catalogAttribute->getData('frontend_label');

            if ( ! empty($frontendLabel)) {
                $options[] = [
                    'value' => $this->getAttributeValue($catalogAttribute),
                    'label' => sprintf('%s (%s)', $frontendLabel, $catalogAttribute->getAttributeCode())
                ];
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    public function toOptions(): array
    {
        $options = [];

        $productAttributeCollection = $this->productAttributeCollectionFactory->create();

        $productAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

        /** @var Attribute $catalogAttribute */
        foreach ($productAttributeCollection as $catalogAttribute) {
            $frontendLabel = $catalogAttribute->getData('frontend_label');

            if ( ! empty($frontendLabel)) {
                $options[ $this->getAttributeValue($catalogAttribute) ] =
                    sprintf('%s (%s)', $frontendLabel, $catalogAttribute->getAttributeCode());
            }
        }

        return $options;
    }

    /**
     * @param Attribute $catalogAttribute
     *
     * @return string
     */
    protected function getAttributeValue(Attribute $catalogAttribute): string
    {
        return $catalogAttribute->getAttributeId();
    }
}
