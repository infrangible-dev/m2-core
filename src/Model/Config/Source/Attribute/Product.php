<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options of the product attributes with their id as value.
 */
class Product implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $productAttributeCollectionFactory
     */
    public function __construct(
        protected CollectionFactory $productAttributeCollectionFactory
    ) {
    }

    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('--Please Select--')]];

        foreach ($this->getList() as $catalogAttribute) {
            $frontendLabel = $catalogAttribute->getDefaultFrontendLabel();

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

    /**
     * Returns the options as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptions(): array
    {
        $options = [];

        foreach ($this->getList() as $catalogAttribute) {
            $frontendLabel = $catalogAttribute->getDefaultFrontendLabel();

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

    /**
     * Returns the value of the attribute in the options: its id.
     *
     * @param Attribute $catalogAttribute
     *
     * @return string
     */
    protected function getAttributeValue(Attribute $catalogAttribute): string
    {
        return (string)$catalogAttribute->getAttributeId();
    }

    /**
     * Returns the attributes listed as options.
     *
     * @return Attribute[]
     */
    protected function getList(): array
    {
        $productAttributeCollection = $this->productAttributeCollectionFactory->create();

        $productAttributeCollection->addOrder(
            'frontend_label',
            Collection::SORT_ORDER_ASC
        );

        return array_filter(
            $productAttributeCollection->getItems(),
            static fn ($item): bool => $item instanceof Attribute
        );
    }
}
