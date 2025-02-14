<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class AttributeSet extends Eav
{
    /** @var \Infrangible\Core\Helper\EntityType */
    protected $entityTypeHelper;

    /** @var CollectionFactory */
    protected $attributeSetCollectionFactory;

    /** @var bool */
    private $addPleaseSelect = true;

    /** @var bool */
    private $addAllSelect = false;

    public function __construct(
        \Infrangible\Core\Helper\EntityType $entityTypeHelper,
        CollectionFactory $attributeSetCollectionFactory
    ) {
        $this->entityTypeHelper = $entityTypeHelper;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
    }

    public function isAddPleaseSelect(): bool
    {
        return $this->addPleaseSelect;
    }

    public function setAddPleaseSelect(bool $addPleaseSelect): void
    {
        $this->addPleaseSelect = $addPleaseSelect;
    }

    public function isAddAllSelect(): bool
    {
        return $this->addAllSelect;
    }

    public function setAddAllSelect(bool $addAllSelect): void
    {
        $this->addAllSelect = $addAllSelect;
    }

    /**
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $attributeSets = [];

        if ($this->isAddPleaseSelect()) {
            $attributeSets[] = [
                'value' => '',
                'label' => __('--Please Select--')
            ];
        }

        if ($this->isAddAllSelect()) {
            $attributeSets[] = [
                'value' => 'all',
                'label' => __('All')
            ];
        }

        $groups = 0;

        if ($this->isCustomer()) {
            $groups++;
        }

        if ($this->isAddress()) {
            $groups++;
        }

        if ($this->isCategory()) {
            $groups++;
        }

        if ($this->isProduct()) {
            $groups++;
        }

        $isGrouped = $groups > 1;

        if ($this->isCustomer()) {
            $customerEntityType = $this->entityTypeHelper->getCustomerEntityType();

            $customerAttributeSetCollection = $this->attributeSetCollectionFactory->create();

            $customerAttributeSetCollection->addFieldToFilter(
                'entity_type_id',
                $customerEntityType->getId()
            );
            $customerAttributeSetCollection->addOrder(
                'attribute_set_name',
                Collection::SORT_ORDER_ASC
            );

            $customerAttributeSets = [];

            /** @var Set $customerAttributeSet */
            foreach ($customerAttributeSetCollection as $customerAttributeSet) {
                if ($isGrouped) {
                    $customerAttributeSets[] = [
                        'value' => $customerAttributeSet->getId(),
                        'label' => $customerAttributeSet->getAttributeSetName()
                    ];
                } else {
                    $attributeSets[] = [
                        'value' => $customerAttributeSet->getId(),
                        'label' => $customerAttributeSet->getAttributeSetName()
                    ];
                }
            }

            if ($isGrouped) {
                $attributeSets[] = [
                    'value' => $customerAttributeSets,
                    'label' => __('Customer')
                ];
            }
        }

        if ($this->isAddress()) {
            $addressEntityType = $this->entityTypeHelper->getCustomerAddressEntityType();

            $addressAttributeSetCollection = $this->attributeSetCollectionFactory->create();

            $addressAttributeSetCollection->addFieldToFilter(
                'entity_type_id',
                $addressEntityType->getId()
            );
            $addressAttributeSetCollection->addOrder(
                'attribute_set_name',
                Collection::SORT_ORDER_ASC
            );

            $addressAttributeSets = [];

            /** @var Set $addressAttributeSet */
            foreach ($addressAttributeSetCollection as $addressAttributeSet) {
                if ($isGrouped) {
                    $addressAttributeSets[] = [
                        'value' => $addressAttributeSet->getId(),
                        'label' => $addressAttributeSet->getAttributeSetName()
                    ];
                } else {
                    $attributeSets[] = [
                        'value' => $addressAttributeSet->getId(),
                        'label' => $addressAttributeSet->getAttributeSetName()
                    ];
                }
            }

            if ($isGrouped) {
                $attributeSets[] = [
                    'value' => $addressAttributeSets,
                    'label' => __('Address')
                ];
            }
        }

        if ($this->isCategory()) {
            $categoryEntityType = $this->entityTypeHelper->getCategoryEntityType();

            $categoryAttributeSetCollection = $this->attributeSetCollectionFactory->create();

            $categoryAttributeSetCollection->addFieldToFilter(
                'entity_type_id',
                $categoryEntityType->getId()
            );
            $categoryAttributeSetCollection->addOrder(
                'attribute_set_name',
                Collection::SORT_ORDER_ASC
            );

            $categoryAttributeSets = [];

            /** @var Set $categoryAttributeSet */
            foreach ($categoryAttributeSetCollection as $categoryAttributeSet) {
                if ($isGrouped) {
                    $categoryAttributeSets[] = [
                        'value' => $categoryAttributeSet->getId(),
                        'label' => $categoryAttributeSet->getAttributeSetName()
                    ];
                } else {
                    $attributeSets[] = [
                        'value' => $categoryAttributeSet->getId(),
                        'label' => $categoryAttributeSet->getAttributeSetName()
                    ];
                }
            }

            if ($isGrouped) {
                $attributeSets[] = [
                    'value' => $categoryAttributeSets,
                    'label' => __('Category')
                ];
            }
        }

        if ($this->isProduct()) {
            $productEntityType = $this->entityTypeHelper->getProductEntityType();

            $productAttributeSetCollection = $this->attributeSetCollectionFactory->create();

            $productAttributeSetCollection->addFieldToFilter(
                'entity_type_id',
                $productEntityType->getId()
            );
            $productAttributeSetCollection->addOrder(
                'attribute_set_name',
                Collection::SORT_ORDER_ASC
            );

            $productAttributeSets = [];

            /** @var Set $productAttributeSet */
            foreach ($productAttributeSetCollection as $productAttributeSet) {
                if ($isGrouped) {
                    $productAttributeSets[] = [
                        'value' => $productAttributeSet->getId(),
                        'label' => $productAttributeSet->getAttributeSetName()
                    ];
                } else {
                    $attributeSets[] = [
                        'value' => $productAttributeSet->getId(),
                        'label' => $productAttributeSet->getAttributeSetName()
                    ];
                }
            }

            if ($isGrouped) {
                $attributeSets[] = [
                    'value' => $productAttributeSets,
                    'label' => __('Product')
                ];
            }
        }

        return $attributeSets;
    }

    /**
     * @throws LocalizedException
     */
    public function toOptions(): array
    {
        $attributeSets = [];

        if ($this->isAddAllSelect()) {
            $attributeSets[ 'all' ] = __('All');
        }

        if ($this->isCustomer()) {
            $customerEntityType = $this->entityTypeHelper->getCustomerEntityType();

            $customerAttributeSetCollection = $this->attributeSetCollectionFactory->create();

            $customerAttributeSetCollection->addFieldToFilter(
                'entity_type_id',
                $customerEntityType->getId()
            );
            $customerAttributeSetCollection->addOrder(
                'attribute_set_name',
                Collection::SORT_ORDER_ASC
            );

            /** @var Set $customerAttributeSet */
            foreach ($customerAttributeSetCollection as $customerAttributeSet) {
                $attributeSets[ $customerAttributeSet->getId() ] = sprintf(
                    '%s | %s',
                    __('Customer'),
                    $customerAttributeSet->getAttributeSetName()
                );
            }
        }

        if ($this->isAddress()) {
            $addressEntityType = $this->entityTypeHelper->getCustomerAddressEntityType();

            $addressAttributeSetCollection = $this->attributeSetCollectionFactory->create();

            $addressAttributeSetCollection->addFieldToFilter(
                'entity_type_id',
                $addressEntityType->getId()
            );
            $addressAttributeSetCollection->addOrder(
                'attribute_set_name',
                Collection::SORT_ORDER_ASC
            );

            /** @var Set $addressAttributeSet */
            foreach ($addressAttributeSetCollection as $addressAttributeSet) {
                $attributeSets[ $addressAttributeSet->getId() ] = sprintf(
                    '%s | %s',
                    __('Address'),
                    $addressAttributeSet->getAttributeSetName()
                );
            }
        }

        if ($this->isCategory()) {
            $categoryEntityType = $this->entityTypeHelper->getCategoryEntityType();

            $categoryAttributeSetCollection = $this->attributeSetCollectionFactory->create();

            $categoryAttributeSetCollection->addFieldToFilter(
                'entity_type_id',
                $categoryEntityType->getId()
            );
            $categoryAttributeSetCollection->addOrder(
                'attribute_set_name',
                Collection::SORT_ORDER_ASC
            );

            /** @var Set $categoryAttributeSet */
            foreach ($categoryAttributeSetCollection as $categoryAttributeSet) {
                $attributeSets[ $categoryAttributeSet->getId() ] = sprintf(
                    '%s | %s',
                    __('Category'),
                    $categoryAttributeSet->getAttributeSetName()
                );
            }
        }

        if ($this->isProduct()) {
            $productEntityType = $this->entityTypeHelper->getProductEntityType();

            $productAttributeSetCollection = $this->attributeSetCollectionFactory->create();

            $productAttributeSetCollection->addFieldToFilter(
                'entity_type_id',
                $productEntityType->getId()
            );
            $productAttributeSetCollection->addOrder(
                'attribute_set_name',
                Collection::SORT_ORDER_ASC
            );

            /** @var Set $productAttributeSet */
            foreach ($productAttributeSetCollection as $productAttributeSet) {
                $attributeSets[ $productAttributeSet->getId() ] = sprintf(
                    '%s | %s',
                    __('Product'),
                    $productAttributeSet->getAttributeSetName()
                );
            }
        }

        return $attributeSets;
    }
}
