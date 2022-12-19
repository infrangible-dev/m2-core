<?php

namespace Infrangible\Core\Model\Config\Source;

use Magento\CatalogRule\Model\Rule\Condition\Product;
use Magento\CatalogRule\Model\Rule\Condition\ProductFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Operator
    implements OptionSourceInterface
{
    /** @var Product */
    protected $ruleCondition;

    /**
     * @param ProductFactory $ruleConditionFactory
     */
    public function __construct(ProductFactory $ruleConditionFactory)
    {
        $this->ruleCondition = $ruleConditionFactory->create();
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('--Please Select--')]];

        foreach ($this->ruleCondition->getDefaultOperatorOptions() as $operator => $label) {
            $options[] = ['value' => $operator, 'label' => $label];
        }

        return $options;
    }

    /**
     * @return array
     */
    public function toOptions(): array
    {
        return $this->ruleCondition->getDefaultOperatorOptions();
    }
}
