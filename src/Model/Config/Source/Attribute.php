<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory as CategoryAttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory as AddressAttributeCollectionFactory;
use Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory as CustomerAttributeCollectionFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Data\Collection;

/**
 * Options of the attributes of the EAV entity types with their id as value, grouped by entity type.
 */
class Attribute extends Eav
{
    /** @var CustomerAttributeCollectionFactory */
    protected CustomerAttributeCollectionFactory $customerAttributeCollectionFactory;

    /** @var AddressAttributeCollectionFactory */
    protected AddressAttributeCollectionFactory $customerAddressAttributeCollectionFactory;

    /** @var CategoryAttributeCollectionFactory */
    protected CategoryAttributeCollectionFactory $categoryAttributeCollectionFactory;

    /** @var CollectionFactory */
    protected CollectionFactory $productAttributeCollectionFactory;

    /** @var bool */
    private bool $addPleaseSelect = true;

    /**
     * @param CustomerAttributeCollectionFactory $customerAttributeCollectionFactory
     * @param AddressAttributeCollectionFactory $customerAddressAttributeCollectionFactory
     * @param CategoryAttributeCollectionFactory $categoryAttributeCollectionFactory
     * @param CollectionFactory $productAttributeCollectionFactory
     */
    public function __construct(
        CustomerAttributeCollectionFactory $customerAttributeCollectionFactory,
        AddressAttributeCollectionFactory $customerAddressAttributeCollectionFactory,
        CategoryAttributeCollectionFactory $categoryAttributeCollectionFactory,
        CollectionFactory $productAttributeCollectionFactory
    ) {
        $this->customerAttributeCollectionFactory = $customerAttributeCollectionFactory;
        $this->customerAddressAttributeCollectionFactory = $customerAddressAttributeCollectionFactory;
        $this->categoryAttributeCollectionFactory = $categoryAttributeCollectionFactory;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
    }

    /**
     * Checks whether an empty option "-- Please Select --" is added.
     *
     * @return bool
     */
    public function isAddPleaseSelect(): bool
    {
        return $this->addPleaseSelect;
    }

    /**
     * Sets whether an empty option "-- Please Select --" is added.
     *
     * @param bool $addPleaseSelect
     *
     * @return void
     */
    public function setAddPleaseSelect(bool $addPleaseSelect): void
    {
        $this->addPleaseSelect = $addPleaseSelect;
    }

    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
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

            /** @var AbstractAttribute $customerAttribute */
            foreach ($customerAttributeCollection as $customerAttribute) {
                $frontendLabel = $customerAttribute->getDefaultFrontendLabel();

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

            /** @var AbstractAttribute $addressAttribute */
            foreach ($addressAttributeCollection as $addressAttribute) {
                $frontendLabel = $addressAttribute->getDefaultFrontendLabel();

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
                $frontendLabel = $categoryAttribute->getDefaultFrontendLabel();

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
                $frontendLabel = $productAttribute->getDefaultFrontendLabel();

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

    /**
     * Returns the options as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptions(): array
    {
        $attributes = [];

        if ($this->isCustomer()) {
            $customerAttributeCollection = $this->customerAttributeCollectionFactory->create();

            $customerAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

            /** @var AbstractAttribute $customerAttribute */
            foreach ($customerAttributeCollection as $customerAttribute) {
                $frontendLabel = $customerAttribute->getDefaultFrontendLabel();

                if (! empty($frontendLabel)) {
                    $attributes[ $customerAttribute->getId() ] =
                        sprintf('%s | %s (%s)', __('Customer'), $frontendLabel, $customerAttribute->getAttributeCode());
                }
            }
        }

        if ($this->isAddress()) {
            $addressAttributeCollection = $this->customerAddressAttributeCollectionFactory->create();

            $addressAttributeCollection->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

            /** @var AbstractAttribute $addressAttribute */
            foreach ($addressAttributeCollection as $addressAttribute) {
                $frontendLabel = $addressAttribute->getDefaultFrontendLabel();

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
                $frontendLabel = $categoryAttribute->getDefaultFrontendLabel();

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
                $frontendLabel = $productAttribute->getDefaultFrontendLabel();

                if (! empty($frontendLabel)) {
                    $attributes[ $productAttribute->getId() ] =
                        sprintf('%s | %s (%s)', __('Product'), $frontendLabel, $productAttribute->getAttributeCode());
                }
            }
        }

        return $attributes;
    }
}
