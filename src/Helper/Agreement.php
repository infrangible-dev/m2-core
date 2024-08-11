<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use Magento\CheckoutAgreements\Model\AgreementFactory;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\Collection;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
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

    public function __construct(
        AgreementFactory $agreementFactory,
        \Magento\CheckoutAgreements\Model\ResourceModel\AgreementFactory $agreementResourceFactory,
        CollectionFactory $agreementCollectionFactory
    ) {
        $this->agreementFactory = $agreementFactory;
        $this->agreementResourceFactory = $agreementResourceFactory;
        $this->agreementCollectionFactory = $agreementCollectionFactory;
    }

    public function newAgreement(): \Magento\CheckoutAgreements\Model\Agreement
    {
        return $this->agreementFactory->create();
    }

    public function loadAgreement(int $agreementId): \Magento\CheckoutAgreements\Model\Agreement
    {
        $agreement = $this->newAgreement();

        $this->agreementResourceFactory->create()->load($agreement, $agreementId);

        return $agreement;
    }

    /**
     * @throws Exception
     */
    public function saveAgreement(\Magento\CheckoutAgreements\Model\Agreement $agreement): void
    {
        $this->agreementResourceFactory->create()->save($agreement);
    }

    public function getAgreementCollection(): Collection
    {
        return $this->agreementCollectionFactory->create();
    }
}
