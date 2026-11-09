<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source;

use Magento\Framework\Exception\LocalizedException;

/**
 * Options of the EAV entity types with their id as value.
 */
class EntityType extends Eav
{
    /** @var \Infrangible\Core\Helper\EntityType */
    protected \Infrangible\Core\Helper\EntityType $entityTypeHelper;

    /**
     * @param \Infrangible\Core\Helper\EntityType $entityTypeHelper
     */
    public function __construct(\Infrangible\Core\Helper\EntityType $entityTypeHelper)
    {
        $this->entityTypeHelper = $entityTypeHelper;
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
        $entityTypes = [['value' => '', 'label' => __('--Please Select--')]];

        if ($this->isCustomer()) {
            $entityTypes[] = [
                'value' => $this->getEntityTypeId($this->entityTypeHelper->getCustomerEntityType()),
                'label' => __('Customer')
            ];
        }

        if ($this->isAddress()) {
            $entityTypes[] = [
                'value' => $this->getEntityTypeId($this->entityTypeHelper->getCustomerAddressEntityType()),
                'label' => __('Address')
            ];
        }

        if ($this->isCategory()) {
            $entityTypes[] = [
                'value' => $this->getEntityTypeId($this->entityTypeHelper->getCategoryEntityType()),
                'label' => __('Category')
            ];
        }

        if ($this->isProduct()) {
            $entityTypes[] = [
                'value' => $this->getEntityTypeId($this->entityTypeHelper->getProductEntityType()),
                'label' => __('Product')
            ];
        }

        return $entityTypes;
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
        $entityTypes = [];

        if ($this->isCustomer()) {
            $entityTypes[ $this->getEntityTypeId($this->entityTypeHelper->getCustomerEntityType()) ] = __('Customer');
        }

        if ($this->isAddress()) {
            $addressEntityType = $this->entityTypeHelper->getCustomerAddressEntityType();
            $entityTypes[ $this->getEntityTypeId($addressEntityType) ] = __('Address');
        }

        if ($this->isCategory()) {
            $entityTypes[ $this->getEntityTypeId($this->entityTypeHelper->getCategoryEntityType()) ] = __('Category');
        }

        if ($this->isProduct()) {
            $entityTypes[ $this->getEntityTypeId($this->entityTypeHelper->getProductEntityType()) ] = __('Product');
        }

        return $entityTypes;
    }
}
