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
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;
use RuntimeException;

class Shipment
{
    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentFactory $shipmentFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory $shipmentResourceFactory
     * @param CollectionFactory $shipmentCollectionFactory
     * @param TrackFactory $shipmentTrackFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\TrackFactory $shipmentTrackResourceFactory
     * @param TrackCollectionFactory $shipmentTrackCollectionFactory
     */
    public function __construct(
        protected ShipmentRepositoryInterface $shipmentRepository,
        protected ShipmentFactory $shipmentFactory,
        protected \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory $shipmentResourceFactory,
        protected CollectionFactory $shipmentCollectionFactory,
        protected TrackFactory $shipmentTrackFactory,
        protected \Magento\Sales\Model\ResourceModel\Order\Shipment\TrackFactory $shipmentTrackResourceFactory,
        protected TrackCollectionFactory $shipmentTrackCollectionFactory
    ) {
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
