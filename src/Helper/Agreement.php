<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\Collection;
use RuntimeException;

/**
 * Checkout agreements (terms and conditions). Requires the optional module Magento_CheckoutAgreements, which is only
 * loaded when a method is called.
 */
class Agreement
{
    /**
     * @param Instances $instanceHelper
     */
    public function __construct(
        protected Instances $instanceHelper
    ) {
    }

    /**
     * Creates a new agreement.
     *
     * @return \Magento\CheckoutAgreements\Model\Agreement
     * @throws RuntimeException if Magento_CheckoutAgreements is not installed
     */
    public function newAgreement(): \Magento\CheckoutAgreements\Model\Agreement
    {
        return $this->instanceHelper->createInstance(\Magento\CheckoutAgreements\Model\Agreement::class);
    }

    /**
     * Loads the agreement.
     *
     * @param int $agreementId
     *
     * @return \Magento\CheckoutAgreements\Model\Agreement
     * @throws RuntimeException if Magento_CheckoutAgreements is not installed
     */
    public function loadAgreement(int $agreementId): \Magento\CheckoutAgreements\Model\Agreement
    {
        $agreement = $this->newAgreement();

        $this->getAgreementResource()->load($agreement, $agreementId);

        return $agreement;
    }

    /**
     * Saves the agreement.
     *
     * @param \Magento\CheckoutAgreements\Model\Agreement $agreement
     *
     * @return void
     * @throws Exception
     */
    public function saveAgreement(\Magento\CheckoutAgreements\Model\Agreement $agreement): void
    {
        $this->getAgreementResource()->save($agreement);
    }

    /**
     * Returns the agreement collection.
     *
     * @return Collection
     * @throws RuntimeException if Magento_CheckoutAgreements is not installed
     */
    public function getAgreementCollection(): Collection
    {
        return $this->instanceHelper->createInstance(Collection::class);
    }

    /**
     * Returns the agreement resource.
     *
     * @return \Magento\CheckoutAgreements\Model\ResourceModel\Agreement
     * @throws RuntimeException if Magento_CheckoutAgreements is not installed
     */
    private function getAgreementResource(): \Magento\CheckoutAgreements\Model\ResourceModel\Agreement
    {
        return $this->instanceHelper->getSharedInstance(
            \Magento\CheckoutAgreements\Model\ResourceModel\Agreement::class
        );
    }
}
