<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Block\Adminhtml\System\Config\Form\Field\FieldArray;

use Infrangible\Core\Block\Adminhtml\View\Element\Html\ProductAttributeCode as ProductAttributeCodeElement;
use Infrangible\Core\Helper\Block;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Base class of configuration fields with a table of product attributes.
 */
abstract class ProductAttributeCode extends AbstractFieldArray
{
    /** @var ProductAttributeCodeElement|null */
    private ?ProductAttributeCodeElement $attributesBlock = null;

    /**
     * @param Context $context
     * @param Block $blockHelper
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     *
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        protected Block $blockHelper,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct(
            $context,
            $data,
            $secureRenderer
        );
    }

    /**
     * Returns the block that renders the select field of the attributes.
     *
     * @return ProductAttributeCodeElement
     * @throws LocalizedException
     */
    protected function _getAttributeRenderer(): ProductAttributeCodeElement
    {
        if ($this->attributesBlock === null) {
            $attributesBlock = $this->blockHelper->createChildBlock(
                $this,
                ProductAttributeCodeElement::class,
                ['is_render_to_js_template' => true]
            );

            if (! $attributesBlock instanceof ProductAttributeCodeElement) {
                throw new LocalizedException(__('Could not create the attribute renderer'));
            }

            $this->attributesBlock = $attributesBlock;
        }

        return $this->attributesBlock;
    }

    /**
     * Adds the attribute column.
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn(
            'mapped_attribute',
            [
                'label'    => $this->getMappedAttributeLabel(),
                'renderer' => $this->_getAttributeRenderer()
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = (string)__('Add Attribute');
    }

    /**
     * Returns the label of the attribute column.
     *
     * @return string
     */
    abstract protected function getMappedAttributeLabel(): string;

    /**
     * Returns the label of the default value column (currently not shown).
     *
     * @return string
     */
    abstract protected function getDefaultValueLabel(): string;

    /**
     * Selects the attribute of the row in the select field.
     *
     * @param DataObject $row
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $customAttribute = $row->getData('mapped_attribute');
        $customAttribute = is_scalar($customAttribute) ? (string)$customAttribute : '';

        $key = 'option_' . $this->_getAttributeRenderer()->calcOptionHash($customAttribute);

        $options[ $key ] = 'selected="selected"';

        $row->setData(
            'option_extra_attrs',
            $options
        );
    }
}
