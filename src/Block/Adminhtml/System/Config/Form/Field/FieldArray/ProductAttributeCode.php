<?php

declare(strict_types=1);

namespace Infrangible\Core\Block\Adminhtml\System\Config\Form\Field\FieldArray;

use Infrangible\Core\Block\Adminhtml\View\Element\Html\ProductAttributeCode as ProductAttributeCodeElement;
use Infrangible\Core\Helper\Block;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class ProductAttributeCode extends AbstractFieldArray
{
    /** @var Block */
    protected $blockHelper;

    /** @var ProductAttributeCodeElement */
    private $attributesBlock;

    public function __construct(
        Context $context,
        Block $blockHelper,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct(
            $context,
            $data,
            $secureRenderer
        );

        $this->blockHelper = $blockHelper;
    }

    /**
     * @throws LocalizedException
     */
    protected function _getAttributeRenderer(): ?ProductAttributeCodeElement
    {
        if (! $this->attributesBlock) {
            $this->attributesBlock = $this->blockHelper->createChildBlock(
                $this,
                ProductAttributeCodeElement::class,
                ['is_render_to_js_template' => true]
            );
        }

        return $this->attributesBlock;
    }

    /**
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

        #$this->addColumn(
        #    'defaultvalue',
        #    ['label' => $this->getDefaultValueLabel()]
        #);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Attribute');
    }

    abstract protected function getMappedAttributeLabel(): string;

    abstract protected function getDefaultValueLabel(): string;

    /**
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $customAttribute = $row->getData('mapped_attribute');

        $key = 'option_' . $this->_getAttributeRenderer()->calcOptionHash($customAttribute);

        $options[ $key ] = 'selected="selected"';

        $row->setData(
            'option_extra_attrs',
            $options
        );
    }
}
