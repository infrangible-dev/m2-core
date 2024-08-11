<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\Collection;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Attribute extends Eav
{
    /** @var \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory */
    protected $customerAttributeCollectionFactory;

    /** @var \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory */
    protected $customerAddressAttributeCollectionFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory */
    protected $categoryAttributeCollectionFactory;

    /** @var CollectionFactory */
    protected $productAttributeCollectionFactory;

    /** @var bool */
    private $addPleaseSelect = true;

    public function __construct(
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $customerAttributeCollectionFactory,
        \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory $customerAddressAttributeCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $categoryAttributeCollectionFactory,
        CollectionFactory $productAttributeCollectionFactory
    ) {
        $this->customerAttributeCollectionFactory = $customerAttributeCollectionFactory;
        $this->customerAddressAttributeCollectionFactory = $customerAddressAttributeCollectionFactory;
        $this->categoryAttributeCollectionFactory = $categoryAttributeCollectionFactory;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
    }

    public function isAddPleaseSelect(): bool
    {
        return $this->addPleaseSelect;
    }

    public function setAddPleaseSelect(bool $addPleaseSelect): void
    {
        $this->addPleaseSelect = $addPleaseSelect;
    }

    public function toOptionArray(): array
    {
        if ($this->isAddPleaseSelect()) {
            $attributes = ['' => __('-- Please Select --')];
        } else {
            $attributes = [];
        }

        if ($this->isCustomer()) {
            $customerAttributeCollection = $this->customerAttributeCollectionFactory->create();

            $customerAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

            $customerAttributes = [];

            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $customerAttribute */
            foreach ($customerAttributeCollection as $customerAttribute) {
                $frontendLabel = $customerAttribute->getData('frontend_label');

                if (! empty($frontendLabel)) {
                    $customerAttributes[] = [
                        'value' => $customerAttribute->getId(),
                        'label' => sprintf('%s (%s)', $frontendLabel, $customerAttribute->getAttributeCode())
                    ];
                }
            }

            $attributes[] = ['value' => $customerAttributes, 'label' => __('Customer')];
        }

        if ($this->isAddress()) {
            $addressAttributeCollection = $this->customerAddressAttributeCollectionFactory->create();

            $addressAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

            $addressAttributes = [];

            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $addressAttribute */
            foreach ($addressAttributeCollection as $addressAttribute) {
                $frontendLabel = $addressAttribute->getData('frontend_label');

                if (! empty($frontendLabel)) {
                    $addressAttributes[] = [
                        'value' => $addressAttribute->getId(),
                        'label' => sprintf('%s (%s)', $frontendLabel, $addressAttribute->getAttributeCode())
                    ];
                }
            }

            $attributes[] = ['value' => $addressAttributes, 'label' => __('Address')];
        }

        if ($this->isCategory()) {
            $categoryAttributeCollection = $this->categoryAttributeCollectionFactory->create();

            $categoryAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

            $categoryAttributes = [];

            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $categoryAttribute */
            foreach ($categoryAttributeCollection as $categoryAttribute) {
                $frontendLabel = $categoryAttribute->getData('frontend_label');

                if (! empty($frontendLabel)) {
                    $categoryAttributes[] = [
                        'value' => $categoryAttribute->getId(),
                        'label' => sprintf('%s (%s)', $frontendLabel, $categoryAttribute->getAttributeCode())
                    ];
                }
            }

            $attributes[] = ['value' => $categoryAttributes, 'label' => __('Category')];
        }

        if ($this->isProduct()) {
            $productAttributeCollection = $this->productAttributeCollectionFactory->create();

            $productAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

            $productAttributes = [];

            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $productAttribute */
            foreach ($productAttributeCollection as $productAttribute) {
                $frontendLabel = $productAttribute->getData('frontend_label');

                if (! empty($frontendLabel)) {
                    $productAttributes[] = [
                        'value' => $productAttribute->getId(),
                        'label' => sprintf('%s (%s)', $frontendLabel, $productAttribute->getAttributeCode())
                    ];
                }
            }

            $attributes[] = ['value' => $productAttributes, 'label' => __('Product')];
        }

        return $attributes;
    }

    public function toOptions(): array
    {
        $attributes = [];

        if ($this->isCustomer()) {
            $customerAttributeCollection = $this->customerAttributeCollectionFactory->create();

            $customerAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $customerAttribute */
            foreach ($customerAttributeCollection as $customerAttribute) {
                $frontendLabel = $customerAttribute->getData('frontend_label');

                if (! empty($frontendLabel)) {
                    $attributes[ $customerAttribute->getId() ] =
                        sprintf('%s | %s (%s)', __('Customer'), $frontendLabel, $customerAttribute->getAttributeCode());
                }
            }
        }

        if ($this->isAddress()) {
            $addressAttributeCollection = $this->customerAddressAttributeCollectionFactory->create();

            $addressAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $addressAttribute */
            foreach ($addressAttributeCollection as $addressAttribute) {
                $frontendLabel = $addressAttribute->getData('frontend_label');

                if (! empty($frontendLabel)) {
                    $attributes[ $addressAttribute->getId() ] =
                        sprintf('%s | %s (%s)', __('Address'), $frontendLabel, $addressAttribute->getAttributeCode());
                }
            }
        }

        if ($this->isCategory()) {
            $categoryAttributeCollection = $this->categoryAttributeCollectionFactory->create();

            $categoryAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $categoryAttribute */
            foreach ($categoryAttributeCollection as $categoryAttribute) {
                $frontendLabel = $categoryAttribute->getData('frontend_label');

                if (! empty($frontendLabel)) {
                    $attributes[ $categoryAttribute->getId() ] =
                        sprintf('%s | %s (%s)', __('Category'), $frontendLabel, $categoryAttribute->getAttributeCode());
                }
            }
        }

        if ($this->isProduct()) {
            $productAttributeCollection = $this->productAttributeCollectionFactory->create();

            $productAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $productAttribute */
            foreach ($productAttributeCollection as $productAttribute) {
                $frontendLabel = $productAttribute->getData('frontend_label');

                if (! empty($frontendLabel)) {
                    $attributes[ $productAttribute->getId() ] =
                        sprintf('%s | %s (%s)', __('Product'), $frontendLabel, $productAttribute->getAttributeCode());
                }
            }
        }

        return $attributes;
    }
}
