<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Product implements OptionSourceInterface
{
    /** @var CollectionFactory */
    protected $productAttributeCollectionFactory;

    public function __construct(CollectionFactory $productAttributeCollectionFactory)
    {
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
    }

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('--Please Select--')]];

        foreach ($this->getList() as $catalogAttribute) {
            $frontendLabel = $catalogAttribute->getData('frontend_label');

            if (! empty($frontendLabel)) {
                $options[] = [
                    'value' => $this->getAttributeValue($catalogAttribute),
                    'label' => sprintf(
                        '%s (%s)',
                        $frontendLabel,
                        $catalogAttribute->getAttributeCode()
                    )
                ];
            }
        }

        return $options;
    }

    public function toOptions(): array
    {
        $options = [];

        foreach ($this->getList() as $catalogAttribute) {
            $frontendLabel = $catalogAttribute->getData('frontend_label');

            if (! empty($frontendLabel)) {
                $options[ $this->getAttributeValue($catalogAttribute) ] = sprintf(
                    '%s (%s)',
                    $frontendLabel,
                    $catalogAttribute->getAttributeCode()
                );
            }
        }

        return $options;
    }

    protected function getAttributeValue(Attribute $catalogAttribute): string
    {
        return $catalogAttribute->getAttributeId();
    }

    /**
     * @return Attribute[]
     */
    protected function getList(): array
    {
        $productAttributeCollection = $this->productAttributeCollectionFactory->create();

        $productAttributeCollection->addOrder(
            'frontend_label',
            Collection::SORT_ORDER_ASC
        );

        return $productAttributeCollection->getItems();
    }
}
