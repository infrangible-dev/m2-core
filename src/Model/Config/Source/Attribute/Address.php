<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\Attribute;

use Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Address
    implements OptionSourceInterface
{
    /** @var CollectionFactory */
    protected $addressAttributeCollectionFactory;

    /**
     * @param CollectionFactory $addressAttributeCollectionFactory
     */
    public function __construct(CollectionFactory $addressAttributeCollectionFactory)
    {
        $this->addressAttributeCollectionFactory = $addressAttributeCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('--Please Select--')]];

        $addressAttributeCollection = $this->addressAttributeCollectionFactory->create();

        $addressAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

        /** @var Attribute $customerAttribute */
        foreach ($addressAttributeCollection as $customerAttribute) {
            $frontendLabel = $customerAttribute->getData('frontend_label');

            if ( ! empty($frontendLabel)) {
                $options[] = [
                    'value' => $this->getAttributeValue($customerAttribute),
                    'label' => sprintf('%s (%s)', $frontendLabel, $customerAttribute->getAttributeCode())
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

        $customerAttributeCollection = $this->addressAttributeCollectionFactory->create();

        $customerAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

        /** @var Attribute $customerAttribute */
        foreach ($customerAttributeCollection as $customerAttribute) {
            $frontendLabel = $customerAttribute->getData('frontend_label');

            if ( ! empty($frontendLabel)) {
                $options[ $this->getAttributeValue($customerAttribute) ] =
                    sprintf('%s (%s)', $frontendLabel, $customerAttribute->getAttributeCode());
            }
        }

        return $options;
    }

    /**
     * @param Attribute $customerAttribute
     *
     * @return string
     */
    protected function getAttributeValue(Attribute $customerAttribute): string
    {
        return $customerAttribute->getAttributeId();
    }
}
