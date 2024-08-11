<?php

declare(strict_types=1);

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
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
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

    public function __construct(
        OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\OrderFactory $orderResourceFactory,
        CollectionFactory $orderCollectionFactory,
        HistoryFactory $orderStatusHistoryFactory,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderResourceFactory = $orderResourceFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    public function newOrder(): \Magento\Sales\Model\Order
    {
        return $this->orderFactory->create();
    }

    public function loadOrder(int $orderId): \Magento\Sales\Model\Order
    {
        $order = $this->newOrder();

        $this->orderResourceFactory->create()->load($order, $orderId);

        return $order;
    }

    public function loadOrderByIncrementId(string $incrementId): \Magento\Sales\Model\Order
    {
        $order = $this->orderFactory->create();

        $order->loadByIncrementId($incrementId);

        return $order;
    }

    /**
     * @throws AlreadyExistsException
     */
    public function saveOrder(\Magento\Sales\Model\Order $order): void
    {
        $this->orderResourceFactory->create()->save($order);
    }

    public function getOrderCollection(): Collection
    {
        return $this->orderCollectionFactory->create();
    }

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
        $historyStatus->setIsVisibleOnFront($isVisibleOnFrontend);

        return $historyStatus;
    }

    /**
     * @throws CouldNotSaveException
     */
    public function saveOrderHistory(History $history): void
    {
        $this->orderStatusHistoryRepository->save($history);
    }
}
