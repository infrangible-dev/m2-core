<?php

namespace Infrangible\Core\Helper;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Status\History;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Order
{
    /** @var OrderFactory */
    protected $orderFactory;

    /** @var \Magento\Sales\Model\ResourceModel\OrderFactory */
    protected $orderResourceFactory;

    /** @var CollectionFactory */
    protected $orderCollectionFactory;

    /** @var HistoryFactory */
    protected $orderStatusHistoryFactory;

    /** @var OrderStatusHistoryRepositoryInterface */
    protected $orderStatusHistoryRepository;

    /**
     * @param OrderFactory                                    $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\OrderFactory $orderResourceFactory
     * @param CollectionFactory                               $orderCollectionFactory
     * @param HistoryFactory                                  $orderStatusHistoryFactory
     * @param OrderStatusHistoryRepositoryInterface           $orderStatusHistoryRepository
     */
    public function __construct(
        OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\OrderFactory $orderResourceFactory,
        CollectionFactory $orderCollectionFactory,
        HistoryFactory $orderStatusHistoryFactory,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository)
    {
        $this->orderFactory = $orderFactory;
        $this->orderResourceFactory = $orderResourceFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function newOrder(): \Magento\Sales\Model\Order
    {
        return $this->orderFactory->create();
    }

    /**
     * @param int $orderId
     *
     * @return \Magento\Sales\Model\Order
     */
    public function loadOrder(int $orderId): \Magento\Sales\Model\Order
    {
        $order = $this->newOrder();

        $this->orderResourceFactory->create()->load($order, $orderId);

        return $order;
    }

    /**
     * @param string $incrementId
     *
     * @return \Magento\Sales\Model\Order
     */
    public function loadOrderByIncrementId(string $incrementId): \Magento\Sales\Model\Order
    {
        $order = $this->orderFactory->create();

        $order->loadByIncrementId($incrementId);

        return $order;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @throws AlreadyExistsException
     */
    public function saveOrder(\Magento\Sales\Model\Order $order)
    {
        $this->orderResourceFactory->create()->save($order);
    }

    /**
     * @return Collection
     */
    public function getOrderCollection(): Collection
    {
        return $this->orderCollectionFactory->create();
    }

    /**
     * @param string                     $comment
     * @param string                     $status
     * @param \Magento\Sales\Model\Order $order
     * @param bool                       $isCustomerNotified
     * @param bool                       $isVisibleOnFrontend
     *
     * @return History
     */
    public function createOrderHistoryModel(
        string $comment,
        string $status,
        \Magento\Sales\Model\Order $order,
        bool $isCustomerNotified = false,
        bool $isVisibleOnFrontend = false): History
    {
        $historyStatus = $this->orderStatusHistoryFactory->create();

        $historyStatus->setComment($comment);
        $historyStatus->setStatus($status);
        $historyStatus->setOrder($order);
        $historyStatus->setIsCustomerNotified($isCustomerNotified);
        $historyStatus->setIsVisibleOnFront($isVisibleOnFrontend);

        return $historyStatus;
    }

    /**
     * @param History $history
     *
     * @throws CouldNotSaveException
     */
    public function saveOrderHistory(History $history)
    {
        $this->orderStatusHistoryRepository->save($history);
    }
}
