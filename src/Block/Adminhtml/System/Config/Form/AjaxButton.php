<?php

namespace Infrangible\Core\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Tofex\Help\Arrays;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class AjaxButton
    extends Field
{
    /** @var Arrays */
    protected $arrayHelper;

    /** @var string */
    private $buttonId;

    /** @var string */
    private $buttonLabel;

    /** @var string */
    private $ajaxUrl;

    /** @var string */
    private $dataHtmlIds;

    /**
     * @param Arrays  $arrayHelper
     * @param Context $context
     * @param array   $data
     */
    public function __construct(Arrays $arrayHelper, Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->arrayHelper = $arrayHelper;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('Infrangible_Core::system/config/form/button.phtml');
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        /** @var array $originalData */
        $originalData = $element->getData('original_data');

        $this->setButtonId($element->getHtmlId());
        $this->setButtonLabel($this->arrayHelper->getValue($originalData, 'button_label'));
        $this->setAjaxUrl($this->getUrl($this->arrayHelper->getValue($originalData, 'button_url'),
            ['_current' => true]));
        $this->setDataHtmlIds($this->arrayHelper->getValue($originalData, 'data_html_ids'));

        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getButtonId(): string
    {
        return $this->buttonId;
    }

    /**
     * @param string $buttonId
     */
    public function setButtonId(string $buttonId): void
    {
        $this->buttonId = $buttonId;
    }

    /**
     * @return string
     */
    public function getButtonLabel(): string
    {
        return $this->buttonLabel;
    }

    /**
     * @param string $buttonLabel
     */
    public function setButtonLabel(string $buttonLabel): void
    {
        $this->buttonLabel = $buttonLabel;
    }

    /**
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->ajaxUrl;
    }

    /**
     * @param string $ajaxUrl
     */
    public function setAjaxUrl(string $ajaxUrl): void
    {
        $this->ajaxUrl = $ajaxUrl;
    }

    /**
     * @return string
     */
    public function getDataHtmlIds(): string
    {
        return $this->dataHtmlIds;
    }

    /**
     * @param string $dataHtmlIds
     */
    public function setDataHtmlIds(string $dataHtmlIds): void
    {
        $this->dataHtmlIds = $dataHtmlIds;
    }
}
