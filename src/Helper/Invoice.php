<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Invoice
{
    /** @var LoggerInterface */
    protected $logging;

    /** @var InvoiceService */
    protected $invoiceService;

    public function __construct(LoggerInterface $logging, InvoiceService $invoiceService)
    {
        $this->logging = $logging;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Prepare order invoice based on order data and requested items qtys. If $qtys is not empty - the function will
     * prepare only specified items, otherwise all containing in the order.
     */
    public function prepareInvoice(Order $order, array $qtys = []): ?Order\Invoice
    {
        try {
            return $this->invoiceService->prepareInvoice($order, $qtys);
        } catch (LocalizedException | Exception $exception) {
            $this->logging->error($exception);
        }

        return null;
    }
}
