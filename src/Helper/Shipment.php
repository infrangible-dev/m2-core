<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Shipment
{
    /** @var ShipmentRepositoryInterface */
    protected $shipmentRepository;

    /** @var ShipmentFactory */
    protected $shipmentFactory;

    /** @var \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory */
    protected $shipmentResourceFactory;

    /** @var CollectionFactory */
    protected $shipmentCollectionFactory;

    /** @var TrackFactory */
    protected $shipmentTrackFactory;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\TrackFactory */
    protected $shipmentTrackResourceFactory;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory */
    protected $shipmentTrackCollectionFactory;

    /**
     * @param ShipmentRepositoryInterface                                               $shipmentRepository
     * @param ShipmentFactory                                                           $shipmentFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory                  $shipmentResourceFactory
     * @param CollectionFactory                                                         $shipmentCollectionFactory
     * @param TrackFactory                                                              $shipmentTrackFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\TrackFactory            $shipmentTrackResourceFactory
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
     * @return Order\Shipment
     */
    public function newShipment(): Order\Shipment
    {
        return $this->shipmentRepository->create();
    }

    /**
     * @param int $shipmentId
     *
     * @return Order\Shipment
     */
    public function loadShipment(int $shipmentId): Order\Shipment
    {
        $shipment = $this->newShipment();

        $this->shipmentResourceFactory->create()->load($shipment, $shipmentId);

        return $shipment;
    }

    /**
     * @param Order\Shipment $shipment
     *
     * @throws AlreadyExistsException
     */
    public function saveShipment(Order\Shipment $shipment)
    {
        $this->shipmentResourceFactory->create()->save($shipment);
    }

    /**
     * @return Collection
     */
    public function getShipmentCollection(): Collection
    {
        return $this->shipmentCollectionFactory->create();
    }

    /**
     * @return Track
     */
    public function newShipmentTrack(): Track
    {
        return $this->shipmentTrackFactory->create();
    }

    /**
     * Prepare order shipment based on order items and requested items qty
     *
     * @param Order $order
     * @param array $qtys   array with mappings of item-ids to quantity
     * @param array $tracks array with arrays of mappings
     *                      (keys from ShipmentTrackInterface, eg TRACK_NUMBER, CARRIER_CODE, TITLE)
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
