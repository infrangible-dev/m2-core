<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Block\Adminhtml\View\Element\Html;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * Select field with all product attributes.
 */
class ProductAttributeCode extends Select
{
    /**
     * @param Context $context
     * @param \Infrangible\Core\Model\Config\Source\Attribute\ProductAttributeCode $sourceProductAttributeCode
     * @param array $data
     *
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        protected \Infrangible\Core\Model\Config\Source\Attribute\ProductAttributeCode $sourceProductAttributeCode,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Sets the name of the select field.
     *
     * @param string $value
     *
     * @return ProductAttributeCode
     */
    public function setInputName(string $value): ProductAttributeCode
    {
        return $this->setDataUsingMethod(
            'name',
            $value
        );
    }

    /**
     * Adds the product attributes as options, if no options were set, and renders the select field.
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (! $this->getOptions()) {
            $this->setOptions($this->sourceProductAttributeCode->toOptions());
        }

        return parent::_toHtml();
    }
}
