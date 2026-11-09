<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Variables;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\Method\Factory;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order\PaymentFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory;

class Payment
{
    /**
     * @param Stores $storeHelper
     * @param PaymentFactory $paymentFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\PaymentFactory $paymentResourceFactory
     * @param CollectionFactory $paymentCollectionFactory
     * @param Factory $paymentMethodFactory
     * @param Variables $variables
     */
    public function __construct(
        protected Stores $storeHelper,
        protected PaymentFactory $paymentFactory,
        protected \Magento\Sales\Model\ResourceModel\Order\PaymentFactory $paymentResourceFactory,
        protected CollectionFactory $paymentCollectionFactory,
        protected Factory $paymentMethodFactory,
        protected Variables $variables
    ) {
    }

    /**
     * Creates a new payment.
     *
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function newPayment(): \Magento\Sales\Model\Order\Payment
    {
        return $this->paymentFactory->create();
    }

    /**
     * Loads the payment.
     *
     * @param int $paymentId
     *
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function loadPayment(int $paymentId): \Magento\Sales\Model\Order\Payment
    {
        $payment = $this->newPayment();

        $this->paymentResourceFactory->create()->load(
            $payment,
            $paymentId
        );

        return $payment;
    }

    /**
     * Saves the payment.
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     *
     * @return void
     * @throws Exception
     */
    public function savePayment(\Magento\Sales\Model\Order\Payment $payment): void
    {
        $this->paymentResourceFactory->create()->save($payment);
    }

    /**
     * Returns the payment collection.
     *
     * @return Collection
     */
    public function getPaymentCollection(): Collection
    {
        return $this->paymentCollectionFactory->create();
    }

    /**
     * Returns the active payment methods of the current store or of all stores.
     *
     * @param bool $allStores
     * @param bool $withDefault
     *
     * @return MethodInterface[] method code => method
     */
    public function getActiveMethods(bool $allStores = false, bool $withDefault = true): array
    {
        $methods = [];

        if ($allStores) {
            $stores = $this->storeHelper->getStores($withDefault);
        } else {
            try {
                $stores = [$this->storeHelper->getStore()];
            } catch (NoSuchEntityException) {
                $stores = [];
            }
        }

        foreach ($stores as $store) {
            $storeId = $this->variables->intValue($store->getId());

            $storePaymentData = $this->storeHelper->getStoreConfig(
                'payment',
                [],
                false,
                $storeId
            );

            if (! is_array($storePaymentData)) {
                continue;
            }

            foreach ($storePaymentData as $code => $data) {
                $methodModel = $this->createMethod(
                    $data,
                    $storeId
                );

                if ($methodModel !== null && $methodModel->getConfigData('active')) {
                    $methods[ $code ] = $methodModel;
                }
            }
        }

        return $methods;
    }

    /**
     * Returns all payment methods that are active in the default configuration.
     *
     * @return MethodInterface[] method code => method
     */
    public function getAllMethods(): array
    {
        $methods = [];

        $paymentData = $this->storeHelper->getStoreConfig('payment');

        if (! is_array($paymentData)) {
            return [];
        }

        foreach ($paymentData as $code => $data) {
            if (is_array($data) && ! empty($data[ 'active' ])) {
                $methodModel = $this->createMethod(
                    $data,
                    0
                );

                if ($methodModel !== null) {
                    $methods[ $code ] = $methodModel;
                }
            }
        }

        return $methods;
    }

    /**
     * Creates the payment method model of the config data of a payment method.
     *
     * @param mixed $data
     * @param int $storeId
     *
     * @return MethodInterface|null null if the config data has no valid model
     */
    private function createMethod(mixed $data, int $storeId): ?MethodInterface
    {
        if (! is_array($data) || ! isset($data[ 'model' ]) || ! is_string($data[ 'model' ])) {
            return null;
        }

        try {
            $methodModel = $this->paymentMethodFactory->create($data[ 'model' ]);
        } catch (LocalizedException) {
            return null;
        }

        $methodModel->setStore($storeId);

        return $methodModel;
    }
}
