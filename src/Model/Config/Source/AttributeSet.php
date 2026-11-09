<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source;

use Infrangible\Core\Helper\EntityType;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Type;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Options of the attribute sets of the EAV entity types, grouped by entity type if several entity types are listed.
 */
class AttributeSet extends Eav
{
    /** @var EntityType */
    protected EntityType $entityTypeHelper;

    /** @var CollectionFactory */
    protected CollectionFactory $attributeSetCollectionFactory;

    /** @var bool */
    private bool $addPleaseSelect = true;

    /** @var bool */
    private bool $addAllSelect = false;

    /**
     * @param EntityType $entityTypeHelper
     * @param CollectionFactory $attributeSetCollectionFactory
     */
    public function __construct(
        EntityType $entityTypeHelper,
        CollectionFactory $attributeSetCollectionFactory
    ) {
        $this->entityTypeHelper = $entityTypeHelper;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
    }

    /**
     * Checks whether an empty option "--Please Select--" is added.
     *
     * @return bool
     */
    public function isAddPleaseSelect(): bool
    {
        return $this->addPleaseSelect;
    }

    /**
     * Sets whether an empty option "--Please Select--" is added.
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
     * Checks whether an option "All" with the value "all" is added.
     *
     * @return bool
     */
    public function isAddAllSelect(): bool
    {
        return $this->addAllSelect;
    }

    /**
     * Sets whether an option "All" with the value "all" is added.
     *
     * @param bool $addAllSelect
     *
     * @return void
     */
    public function setAddAllSelect(bool $addAllSelect): void
    {
        $this->addAllSelect = $addAllSelect;
    }

    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
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

        $entityTypes = $this->getSelectedEntityTypes();

        $isGrouped = count($entityTypes) > 1;

        foreach ($entityTypes as [$entityTypeLabel, $entityType]) {
            $entityTypeAttributeSets = [];

            foreach ($this->getAttributeSets($entityType) as $attributeSet) {
                $entityTypeAttributeSets[] = [
                    'value' => $attributeSet->getId(),
                    'label' => $attributeSet->getAttributeSetName()
                ];
            }

            if ($isGrouped) {
                $attributeSets[] = [
                    'value' => $entityTypeAttributeSets,
                    'label' => $entityTypeLabel
                ];
            } else {
                array_push($attributeSets, ...$entityTypeAttributeSets);
            }
        }

        return $attributeSets;
    }

    /**
     * Returns the options as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     * @throws LocalizedException
     */
    public function toOptions(): array
    {
        $attributeSets = [];

        if ($this->isAddAllSelect()) {
            $attributeSets[ 'all' ] = __('All');
        }

        foreach ($this->getSelectedEntityTypes() as [$entityTypeLabel, $entityType]) {
            foreach ($this->getAttributeSets($entityType) as $attributeSet) {
                $attributeSets[ $attributeSet->getId() ] = sprintf(
                    '%s | %s',
                    $entityTypeLabel,
                    $attributeSet->getAttributeSetName()
                );
            }
        }

        return $attributeSets;
    }

    /**
     * Returns the label and the model of the selected entity types.
     *
     * @return array
     * @phpstan-return array<int, array{Phrase, Type|null}>
     * @throws LocalizedException
     */
    private function getSelectedEntityTypes(): array
    {
        $entityTypes = [];

        if ($this->isCustomer()) {
            $entityTypes[] = [__('Customer'), $this->entityTypeHelper->getCustomerEntityType()];
        }

        if ($this->isAddress()) {
            $entityTypes[] = [__('Address'), $this->entityTypeHelper->getCustomerAddressEntityType()];
        }

        if ($this->isCategory()) {
            $entityTypes[] = [__('Category'), $this->entityTypeHelper->getCategoryEntityType()];
        }

        if ($this->isProduct()) {
            $entityTypes[] = [__('Product'), $this->entityTypeHelper->getProductEntityType()];
        }

        return $entityTypes;
    }

    /**
     * Returns the attribute sets of the entity type, sorted by name.
     *
     * @param Type|null $entityType
     *
     * @return Set[]
     * @throws LocalizedException
     */
    private function getAttributeSets(?Type $entityType): array
    {
        $attributeSetCollection = $this->attributeSetCollectionFactory->create();

        $attributeSetCollection->addFieldToFilter(
            'entity_type_id',
            $this->getEntityTypeId($entityType)
        );
        $attributeSetCollection->addOrder(
            'attribute_set_name',
            Collection::SORT_ORDER_ASC
        );

        $attributeSets = [];

        foreach ($attributeSetCollection as $attributeSet) {
            if ($attributeSet instanceof Set) {
                $attributeSets[] = $attributeSet;
            }
        }

        return $attributeSets;
    }
}
