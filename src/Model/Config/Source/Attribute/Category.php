<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute as Attribute;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options of the category attributes with their id as value.
 */
class Category implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $categoryAttributeCollectionFactory
     */
    public function __construct(
        protected CollectionFactory $categoryAttributeCollectionFactory
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

        $productAttributeCollection = $this->categoryAttributeCollectionFactory->create();

        $productAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

        /** @var Attribute $catalogAttribute */
        foreach ($productAttributeCollection as $catalogAttribute) {
            $frontendLabel = $catalogAttribute->getDefaultFrontendLabel();

            if (! empty($frontendLabel)) {
                $options[] = [
                    'value' => $this->getAttributeValue($catalogAttribute),
                    'label' => sprintf('%s (%s)', $frontendLabel, $catalogAttribute->getAttributeCode())
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

        $productAttributeCollection = $this->categoryAttributeCollectionFactory->create();

        $productAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

        /** @var Attribute $catalogAttribute */
        foreach ($productAttributeCollection as $catalogAttribute) {
            $frontendLabel = $catalogAttribute->getDefaultFrontendLabel();

            if (! empty($frontendLabel)) {
                $options[ $this->getAttributeValue($catalogAttribute) ] =
                    sprintf('%s (%s)', $frontendLabel, $catalogAttribute->getAttributeCode());
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
}
