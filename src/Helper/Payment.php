<?php /** @noinspection PhpDeprecationInspection */

namespace Infrangible\Core\Helper;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Model\Order\PaymentFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Payment
{
    /** @var Stores */
    protected $storeHelper;

    /** @var PaymentFactory */
    protected $paymentFactory;

    /** @var \Magento\Sales\Model\ResourceModel\Order\PaymentFactory */
    protected $paymentResourceFactory;

    /** @var CollectionFactory */
    protected $paymentCollectionFactory;

    /** @var Factory */
    protected $paymentMethodFactory;

    /**
     * @param Stores                                                  $storeHelper
     * @param PaymentFactory                                          $paymentFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\PaymentFactory $paymentResourceFactory
     * @param CollectionFactory                                       $paymentCollectionFactory
     * @param Factory                                                 $paymentMethodFactory
     */
    public function __construct(
        Stores $storeHelper,
        PaymentFactory $paymentFactory,
        \Magento\Sales\Model\ResourceModel\Order\PaymentFactory $paymentResourceFactory,
        CollectionFactory $paymentCollectionFactory,
        Factory $paymentMethodFactory)
    {
        $this->storeHelper = $storeHelper;

        $this->paymentFactory = $paymentFactory;
        $this->paymentResourceFactory = $paymentResourceFactory;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->paymentMethodFactory = $paymentMethodFactory;
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function newPayment(): \Magento\Sales\Model\Order\Payment
    {
        return $this->paymentFactory->create();
    }

    /**
     * @param int $paymentId
     *
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function loadPayment(int $paymentId): \Magento\Sales\Model\Order\Payment
    {
        $payment = $this->newPayment();

        $this->paymentResourceFactory->create()->load($payment, $paymentId);

        return $payment;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     *
     * @throws AlreadyExistsException
     */
    public function savePayment(\Magento\Sales\Model\Order\Payment $payment)
    {
        $this->paymentResourceFactory->create()->save($payment);
    }

    /**
     * @return Collection
     */
    public function getPaymentCollection(): Collection
    {
        return $this->paymentCollectionFactory->create();
    }

    /**
     * @param bool $allStores
     * @param bool $withDefault
     *
     * @return AbstractMethod[]
     */
    public function getActiveMethods(bool $allStores = false, bool $withDefault = true): array
    {
        $methods = [];

        $stores = [];

        if ($allStores) {
            $stores = $this->storeHelper->getStores($withDefault);
        } else {
            try {
                $stores[] = $this->storeHelper->getStore();
            } catch (NoSuchEntityException $exception) {
            }
        }

        foreach ($stores as $store) {
            $storePaymentData = $this->storeHelper->getStoreConfig('payment', [], false, $store->getId());

            foreach ($storePaymentData as $code => $data) {
                if (is_array($data) && array_key_exists('model', $data)) {
                    try {
                        $methodModel = $this->paymentMethodFactory->create($data[ 'model' ]);

                        $methodModel->setStore($store->getId());

                        if ($methodModel->getConfigData('active')) {
                            $methods[ $code ] = $methodModel;
                        }
                    } catch (LocalizedException $exception) {
                    }
                }
            }
        }

        return $methods;
    }

    /**
     * @return AbstractMethod[]
     */
    public function getAllMethods(): array
    {
        $methods = [];

        foreach ($this->storeHelper->getStoreConfig('payment') as $code => $data) {
            if (isset($data[ 'active' ], $data[ 'model' ]) && $data[ 'active' ]) {
                try {
                    $methodModel = $this->paymentMethodFactory->create($data[ 'model' ]);

                    $methodModel->setStore(null);

                    $methods[ $code ] = $methodModel;
                } catch (LocalizedException $exception) {
                }
            }
        }

        return $methods;
    }
}
