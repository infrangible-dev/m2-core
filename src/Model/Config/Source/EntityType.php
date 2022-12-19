<?php

namespace Infrangible\Core\Model\Config\Source;

use Magento\Framework\Exception\LocalizedException;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class EntityType
    extends Eav
{
    /** @var \Infrangible\Core\Helper\EntityType */
    protected $entityTypeHelper;

    /**
     * @param \Infrangible\Core\Helper\EntityType $entityTypeHelper
     */
    public function __construct(\Infrangible\Core\Helper\EntityType $entityTypeHelper)
    {
        $this->entityTypeHelper = $entityTypeHelper;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $entityTypes = [['value' => '', 'label' => __('--Please Select--')]];

        if ($this->isCustomer()) {
            $entityTypes[] = [
                'value' => $this->entityTypeHelper->getCustomerEntityType()->getId(),
                'label' => __('Customer')
            ];
        }

        if ($this->isAddress()) {
            $entityTypes[] = [
                'value' => $this->entityTypeHelper->getCustomerAddressEntityType()->getId(),
                'label' => __('Address')
            ];
        }

        if ($this->isCategory()) {
            $entityTypes[] = [
                'value' => $this->entityTypeHelper->getCategoryEntityType()->getId(),
                'label' => __('Category')
            ];
        }

        if ($this->isProduct()) {
            $entityTypes[] = [
                'value' => $this->entityTypeHelper->getProductEntityType()->getId(),
                'label' => __('Product')
            ];
        }

        return $entityTypes;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function toOptions(): array
    {
        $entityTypes = [];

        if ($this->isCustomer()) {
            $entityTypes[ $this->entityTypeHelper->getCustomerEntityType()->getId() ] = __('Customer');
        }

        if ($this->isAddress()) {
            $entityTypes[ $this->entityTypeHelper->getCustomerAddressEntityType()->getId() ] = __('Address');
        }

        if ($this->isCategory()) {
            $entityTypes[ $this->entityTypeHelper->getCategoryEntityType()->getId() ] = __('Category');
        }

        if ($this->isProduct()) {
            $entityTypes[ $this->entityTypeHelper->getProductEntityType()->getId() ] = __('Product');
        }

        return $entityTypes;
    }
}
