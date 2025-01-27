<?php

declare(strict_types=1);

namespace Infrangible\Core\Block\Adminhtml\View\Element\Html;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * @author &why
 */
class ProductAttributeCode extends Select
{
    /** @var ProductAttributeCode */
    protected $sourceProductAttributeCode;

    public function __construct(
        Context $context,
        \Infrangible\Core\Model\Config\Source\Attribute\ProductAttributeCode $sourceProductAttributeCode,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );

        $this->sourceProductAttributeCode = $sourceProductAttributeCode;
    }

    public function setInputName(string $value): ProductAttributeCode
    {
        return $this->setDataUsingMethod(
            'name',
            $value
        );
    }

    public function _toHtml(): string
    {
        if (! $this->getOptions()) {
            $this->setOptions($this->sourceProductAttributeCode->toOptions());
        }

        return parent::_toHtml();
    }
}