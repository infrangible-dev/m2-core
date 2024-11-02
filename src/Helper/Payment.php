<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Variables;
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
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
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

    /** @var Variables */
    protected $variables;

    public function __construct(
        Stores $storeHelper,
        PaymentFactory $paymentFactory,
        \Magento\Sales\Model\ResourceModel\Order\PaymentFactory $paymentResourceFactory,
        CollectionFactory $paymentCollectionFactory,
        Factory $paymentMethodFactory,
        Variables $variables
    ) {
        $this->storeHelper = $storeHelper;
        $this->paymentFactory = $paymentFactory;
        $this->paymentResourceFactory = $paymentResourceFactory;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->variables = $variables;
    }

    public function newPayment(): \Magento\Sales\Model\Order\Payment
    {
        return $this->paymentFactory->create();
    }

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
     * @throws AlreadyExistsException
     */
    public function savePayment(\Magento\Sales\Model\Order\Payment $payment)
    {
        $this->paymentResourceFactory->create()->save($payment);
    }

    public function getPaymentCollection(): Collection
    {
        return $this->paymentCollectionFactory->create();
    }

    /**
     * @return AbstractMethod[]
     * @throws \Exception
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
            $storePaymentData = $this->storeHelper->getStoreConfig(
                'payment',
                [],
                false,
                $this->variables->intValue($store->getId())
            );

            foreach ($storePaymentData as $code => $data) {
                if (is_array($data) && array_key_exists(
                        'model',
                        $data
                    )) {
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

                    $methodModel->setStore(0);

                    $methods[ $code ] = $methodModel;
                } catch (LocalizedException $exception) {
                }
            }
        }

        return $methods;
    }
}
