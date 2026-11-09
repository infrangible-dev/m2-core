<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Status\History;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class Order
{
    /**
     * @param OrderFactory $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\OrderFactory $orderResourceFactory
     * @param CollectionFactory $orderCollectionFactory
     * @param HistoryFactory $orderStatusHistoryFactory
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    public function __construct(
        protected OrderFactory $orderFactory,
        protected \Magento\Sales\Model\ResourceModel\OrderFactory $orderResourceFactory,
        protected CollectionFactory $orderCollectionFactory,
        protected HistoryFactory $orderStatusHistoryFactory,
        protected OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
    }

    /**
     * Creates a new order.
     *
     * @return \Magento\Sales\Model\Order
     */
    public function newOrder(): \Magento\Sales\Model\Order
    {
        return $this->orderFactory->create();
    }

    /**
     * Loads the order.
     *
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
     * Loads the order with the increment ID.
     *
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
     * Saves the order.
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return void
     * @throws Exception
     */
    public function saveOrder(\Magento\Sales\Model\Order $order): void
    {
        $this->orderResourceFactory->create()->save($order);
    }

    /**
     * Returns the order collection.
     *
     * @return Collection
     */
    public function getOrderCollection(): Collection
    {
        return $this->orderCollectionFactory->create();
    }

    /**
     * Creates a status history entry (comment) for the order, without saving it.
     *
     * @param string $comment
     * @param string $status
     * @param \Magento\Sales\Model\Order $order
     * @param bool $isCustomerNotified
     * @param bool $isVisibleOnFrontend
     *
     * @return History
     */
    public function createOrderHistoryModel(
        string $comment,
        string $status,
        \Magento\Sales\Model\Order $order,
        bool $isCustomerNotified = false,
        bool $isVisibleOnFrontend = false
    ): History {
        $historyStatus = $this->orderStatusHistoryFactory->create();

        $historyStatus->setComment($comment);
        $historyStatus->setStatus($status);
        $historyStatus->setOrder($order);
        $historyStatus->setIsCustomerNotified($isCustomerNotified);
        $historyStatus->setIsVisibleOnFront($isVisibleOnFrontend ? 1 : 0);

        return $historyStatus;
    }

    /**
     * Saves a status history entry of an order.
     *
     * @param History $history
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function saveOrderHistory(History $history): void
    {
        $this->orderStatusHistoryRepository->save($history);
    }
}
