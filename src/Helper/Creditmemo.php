<?php

namespace Infrangible\Core\Helper;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Tofex\Help\Variables;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Creditmemo
{
    /** @var Variables */
    protected $variableHelper;

    /** @var CreditmemoFactory */
    protected $creditMemoFactory;

    /**
     * @param Variables         $variableHelper
     * @param CreditmemoFactory $creditMemoFactory
     */
    public function __construct(Variables $variableHelper, CreditmemoFactory $creditMemoFactory)
    {
        $this->variableHelper = $variableHelper;
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
        float $adjustmentNegative = null): Order\Creditmemo
    {
        $creditMemoData = [];

        if ( ! $this->variableHelper->isEmpty($qtys)) {
            $creditMemoData[ 'qtys' ] = $qtys;
        }

        if ( ! $this->variableHelper->isEmpty($shippingAmount)) {
            $creditMemoData[ 'shipping_amount' ] = $shippingAmount;
        }

        if ( ! $this->variableHelper->isEmpty($adjustmentPositive)) {
            $creditMemoData[ 'adjustment_positive' ] = $adjustmentPositive;
        }

        if ( ! $this->variableHelper->isEmpty($adjustmentNegative)) {
            $creditMemoData[ 'adjustment_negative' ] = $adjustmentNegative;
        }

        return $this->creditMemoFactory->createByOrder($order, $creditMemoData);
    }
}
