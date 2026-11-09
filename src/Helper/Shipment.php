<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use RuntimeException;

class Shipment
{
    /** @var ShipmentRepositoryInterface */
    protected ShipmentRepositoryInterface $shipmentRepository;

    /** @var ShipmentFactory */
    protected ShipmentFactory $shipmentFactory;

    /** @var \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory */
    protected \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory $shipmentResourceFactory;

    /** @var CollectionFactory */
    protected CollectionFactory $shipmentCollectionFactory;

    /** @var TrackFactory */
    protected TrackFactory $shipmentTrackFactory;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\TrackFactory */
    protected \Magento\Sales\Model\ResourceModel\Order\Shipment\TrackFactory $shipmentTrackResourceFactory;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory */
    protected \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $shipmentTrackCollectionFactory;

    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentFactory $shipmentFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory $shipmentResourceFactory
     * @param CollectionFactory $shipmentCollectionFactory
     * @param TrackFactory $shipmentTrackFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\TrackFactory $shipmentTrackResourceFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $shipmentTrackCollectionFactory
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory $shipmentResourceFactory,
        CollectionFactory $shipmentCollectionFactory,
        TrackFactory $shipmentTrackFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\TrackFactory $shipmentTrackResourceFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $shipmentTrackCollectionFactory
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentFactory = $shipmentFactory;
        $this->shipmentResourceFactory = $shipmentResourceFactory;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->shipmentTrackFactory = $shipmentTrackFactory;
        $this->shipmentTrackResourceFactory = $shipmentTrackResourceFactory;
        $this->shipmentTrackCollectionFactory = $shipmentTrackCollectionFactory;
    }

    /**
     * Creates a new shipment.
     *
     * @return Order\Shipment
     * @throws RuntimeException
     */
    public function newShipment(): Order\Shipment
    {
        $shipment = $this->shipmentRepository->create();

        if (! $shipment instanceof Order\Shipment) {
            throw new RuntimeException('Could not create shipment');
        }

        return $shipment;
    }

    /**
     * Loads the shipment.
     *
     * @param int $shipmentId
     *
     * @return Order\Shipment
     * @throws RuntimeException
     */
    public function loadShipment(int $shipmentId): Order\Shipment
    {
        $shipment = $this->newShipment();

        $this->shipmentResourceFactory->create()->load($shipment, $shipmentId);

        return $shipment;
    }

    /**
     * Saves the shipment.
     *
     * @param Order\Shipment $shipment
     *
     * @return void
     * @throws Exception
     */
    public function saveShipment(Order\Shipment $shipment): void
    {
        $this->shipmentResourceFactory->create()->save($shipment);
    }

    /**
     * Returns the shipment collection.
     *
     * @return Collection
     */
    public function getShipmentCollection(): Collection
    {
        return $this->shipmentCollectionFactory->create();
    }

    /**
     * Creates a new shipment track.
     *
     * @return Track
     */
    public function newShipmentTrack(): Track
    {
        return $this->shipmentTrackFactory->create();
    }

    /**
     * Prepares a shipment of the order for the item quantities and tracks, without saving it.
     *
     * @param Order $order
     * @param array $qtys   order item ID => quantity
     * @param array $tracks track data (keys from ShipmentTrackInterface, e.g. TRACK_NUMBER, CARRIER_CODE, TITLE)
     *
     * @phpstan-param array<int|string, float|int|string> $qtys
     * @phpstan-param array<int, array<string, mixed>> $tracks
     *
     * @return Order\Shipment
     */
    public function prepareShipment(Order $order, array $qtys = [], array $tracks = []): Order\Shipment
    {
        /** @var Order\Shipment $shipment */
        $shipment = $this->shipmentFactory->create($order, $qtys, $tracks);

        return $shipment;
    }
}
