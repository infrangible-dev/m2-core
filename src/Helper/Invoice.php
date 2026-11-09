<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;

class Invoice
{
    /**
     * @param LoggerInterface $logging
     * @param InvoiceService $invoiceService
     */
    public function __construct(
        protected LoggerInterface $logging,
        protected InvoiceService $invoiceService
    ) {
    }

    /**
     * Prepares an invoice of the order, without saving it.
     *
     * If $qtys is not empty, only the items in it are invoiced, otherwise all items of the order.
     *
     * @param Order $order
     * @param array $qtys
     *
     * @phpstan-param array<int|string, float|int|string> $qtys order item ID => quantity
     *
     * @return Order\Invoice|null
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
