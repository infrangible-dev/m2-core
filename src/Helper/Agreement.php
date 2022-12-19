<?php

namespace Infrangible\Core\Helper;

use Exception;
use Magento\CheckoutAgreements\Model\AgreementFactory;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\Collection;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Agreement
{
    /** @var AgreementFactory */
    protected $agreementFactory;

    /** @var \Magento\CheckoutAgreements\Model\ResourceModel\AgreementFactory */
    protected $agreementResourceFactory;

    /** @var CollectionFactory */
    protected $agreementCollectionFactory;

    /**
     * @param AgreementFactory                                                 $agreementFactory
     * @param \Magento\CheckoutAgreements\Model\ResourceModel\AgreementFactory $agreementResourceFactory
     * @param CollectionFactory                                                $agreementCollectionFactory
     */
    public function __construct(
        AgreementFactory $agreementFactory,
        \Magento\CheckoutAgreements\Model\ResourceModel\AgreementFactory $agreementResourceFactory,
        CollectionFactory $agreementCollectionFactory)
    {
        $this->agreementFactory = $agreementFactory;
        $this->agreementResourceFactory = $agreementResourceFactory;
        $this->agreementCollectionFactory = $agreementCollectionFactory;
    }

    /**
     * @return \Magento\CheckoutAgreements\Model\Agreement
     */
    public function newAgreement(): \Magento\CheckoutAgreements\Model\Agreement
    {
        return $this->agreementFactory->create();
    }

    /**
     * @param int $agreementId
     *
     * @return \Magento\CheckoutAgreements\Model\Agreement
     */
    public function loadAgreement(int $agreementId): \Magento\CheckoutAgreements\Model\Agreement
    {
        $agreement = $this->newAgreement();

        $this->agreementResourceFactory->create()->load($agreement, $agreementId);

        return $agreement;
    }

    /**
     * @param \Magento\CheckoutAgreements\Model\Agreement $agreement
     *
     * @throws Exception
     */
    public function saveAgreement(\Magento\CheckoutAgreements\Model\Agreement $agreement)
    {
        $this->agreementResourceFactory->create()->save($agreement);
    }

    /**
     * @return Collection
     */
    public function getAgreementCollection(): Collection
    {
        return $this->agreementCollectionFactory->create();
    }
}
