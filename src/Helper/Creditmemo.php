<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Variables;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Creditmemo
{
    /** @var Variables */
    protected $variables;

    /** @var CreditmemoFactory */
    protected $creditMemoFactory;

    /**
     * @param Variables         $variables
     * @param CreditmemoFactory $creditMemoFactory
     */
    public function __construct(Variables $variables, CreditmemoFactory $creditMemoFactory)
    {
        $this->variables = $variables;
        $this->creditMemoFactory = $creditMemoFactory;
    }

    /**
     * Prepare order creditmemo based on order items and requested params
     *
     * @param Order      $order
     * @param array      $qtys
     * @param float|null $shippingAmount
     * @param float|null $adjustmentPositive
     * @param float|null $adjustmentNegative
     *
     * @return Order\Creditmemo
     */
    public function prepareCreditmemo(
        Order $order,
        array $qtys = [],
        float $shippingAmount = null,
        float $adjustmentPositive = null,
        float $adjustmentNegative = null
    ): Order\Creditmemo {
        $creditMemoData = [];

        if (!$this->variables->isEmpty($qtys)) {
            $creditMemoData['qtys'] = $qtys;
        }

        if (!$this->variables->isEmpty($shippingAmount)) {
            $creditMemoData['shipping_amount'] = $shippingAmount;
        }

        if (!$this->variables->isEmpty($adjustmentPositive)) {
            $creditMemoData['adjustment_positive'] = $adjustmentPositive;
        }

        if (!$this->variables->isEmpty($adjustmentNegative)) {
            $creditMemoData['adjustment_negative'] = $adjustmentNegative;
        }

        return $this->creditMemoFactory->createByOrder($order, $creditMemoData);
    }
}
