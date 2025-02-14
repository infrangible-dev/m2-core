<?php

declare(strict_types=1);

namespace Infrangible\Core\Block\Adminhtml\System\Config\Form;

use FeWeDev\Base\Arrays;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class AjaxButton extends Field
{
    /** @var Arrays */
    protected $arrays;

    /** @var string */
    private $buttonId;

    /** @var string */
    private $buttonLabel;

    /** @var string */
    private $ajaxUrl;

    /** @var string */
    private $dataHtmlIds;

    public function __construct(Arrays $arrays, Context $context, array $data = [])
    {
        parent::__construct(
            $context,
            $data
        );

        $this->arrays = $arrays;
    }

    protected function _construct(): void
    {
        parent::_construct();

        $this->setTemplate('Infrangible_Core::system/config/form/button.phtml');
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        /** @var array $originalData */
        $originalData = $element->getData('original_data');

        $this->setButtonId($element->getHtmlId());
        $this->setButtonLabel(
            $this->arrays->getValue(
                $originalData,
                'button_label'
            )
        );
        $this->setAjaxUrl(
            $this->getUrl(
                $this->arrays->getValue(
                    $originalData,
                    'button_url'
                ),
                ['_current' => true]
            )
        );
        $this->setDataHtmlIds(
            $this->arrays->getValue(
                $originalData,
                'data_html_ids',
                ''
            )
        );

        return $this->_toHtml();
    }

    public function getButtonId(): string
    {
        return $this->buttonId;
    }

    public function setButtonId(string $buttonId): void
    {
        $this->buttonId = $buttonId;
    }

    public function getButtonLabel(): string
    {
        return $this->buttonLabel;
    }

    public function setButtonLabel(string $buttonLabel): void
    {
        $this->buttonLabel = $buttonLabel;
    }

    public function getAjaxUrl(): string
    {
        return $this->ajaxUrl;
    }

    public function setAjaxUrl(string $ajaxUrl): void
    {
        $this->ajaxUrl = $ajaxUrl;
    }

    public function getDataHtmlIds(): string
    {
        return $this->dataHtmlIds;
    }

    public function setDataHtmlIds(string $dataHtmlIds): void
    {
        $this->dataHtmlIds = $dataHtmlIds;
    }
}
