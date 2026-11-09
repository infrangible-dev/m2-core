<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options of the customer address attributes with their id as value.
 */
class Address implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $addressAttributeCollectionFactory
     */
    public function __construct(
        protected CollectionFactory $addressAttributeCollectionFactory
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

        $addressAttributeCollection = $this->addressAttributeCollectionFactory->create();

        $addressAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

        /** @var Attribute $customerAttribute */
        foreach ($addressAttributeCollection as $customerAttribute) {
            $frontendLabel = $customerAttribute->getDefaultFrontendLabel();

            if (! empty($frontendLabel)) {
                $options[] = [
                    'value' => $this->getAttributeValue($customerAttribute),
                    'label' => sprintf('%s (%s)', $frontendLabel, $customerAttribute->getAttributeCode())
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

        $customerAttributeCollection = $this->addressAttributeCollectionFactory->create();

        $customerAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

        /** @var Attribute $customerAttribute */
        foreach ($customerAttributeCollection as $customerAttribute) {
            $frontendLabel = $customerAttribute->getDefaultFrontendLabel();

            if (! empty($frontendLabel)) {
                $options[ $this->getAttributeValue($customerAttribute) ] =
                    sprintf('%s (%s)', $frontendLabel, $customerAttribute->getAttributeCode());
            }
        }

        return $options;
    }

    /**
     * Returns the value of the attribute in the options: its id.
     *
     * @param Attribute $customerAttribute
     *
     * @return string
     */
    protected function getAttributeValue(Attribute $customerAttribute): string
    {
        return (string)$customerAttribute->getAttributeId();
    }
}
