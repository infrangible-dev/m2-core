<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Category
    implements OptionSourceInterface
{
    /** @var CollectionFactory */
    protected $categoryAttributeCollectionFactory;

    /**
     * @param CollectionFactory $categoryAttributeCollectionFactory
     */
    public function __construct(CollectionFactory $categoryAttributeCollectionFactory)
    {
        $this->categoryAttributeCollectionFactory = $categoryAttributeCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('--Please Select--')]];

        $productAttributeCollection = $this->categoryAttributeCollectionFactory->create();

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

        $productAttributeCollection = $this->categoryAttributeCollectionFactory->create();

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
