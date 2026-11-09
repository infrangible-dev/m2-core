<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Variables;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;

class Creditmemo
{
    /** @var Variables */
    protected Variables $variables;

    /** @var CreditmemoFactory */
    protected CreditmemoFactory $creditMemoFactory;

    /**
     * @param Variables $variables
     * @param CreditmemoFactory $creditMemoFactory
     */
    public function __construct(Variables $variables, CreditmemoFactory $creditMemoFactory)
    {
        $this->variables = $variables;
        $this->creditMemoFactory = $creditMemoFactory;
    }

    /**
     * Prepares a credit memo of the order for the item quantities and amounts, without saving it.
     *
     * @param Order $order
     * @param array $qtys
     * @param float|null $shippingAmount
     * @param float|null $adjustmentPositive
     * @param float|null $adjustmentNegative
     *
     * @phpstan-param array<int|string, float|int|string> $qtys order item ID => quantity
     *
     * @return Order\Creditmemo
     */
    public function prepareCreditmemo(
        Order $order,
        array $qtys = [],
        ?float $shippingAmount = null,
        ?float $adjustmentPositive = null,
        ?float $adjustmentNegative = null
    ): Order\Creditmemo {
        $creditMemoData = [];

        if (! $this->variables->isEmpty($qtys)) {
            $creditMemoData[ 'qtys' ] = $qtys;
        }

        if (! $this->variables->isEmpty($shippingAmount)) {
            $creditMemoData[ 'shipping_amount' ] = $shippingAmount;
        }

        if (! $this->variables->isEmpty($adjustmentPositive)) {
            $creditMemoData[ 'adjustment_positive' ] = $adjustmentPositive;
        }

        if (! $this->variables->isEmpty($adjustmentNegative)) {
            $creditMemoData[ 'adjustment_negative' ] = $adjustmentNegative;
        }

        return $this->creditMemoFactory->createByOrder(
            $order,
            $creditMemoData
        );
    }
}
