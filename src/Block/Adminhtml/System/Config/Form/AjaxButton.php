<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Block\Adminhtml\System\Config\Form;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Configuration field with a button that sends the values of other fields to an admin URL with an Ajax request.
 * Configured in system.xml with button_label, button_url and data_html_ids (comma-separated HTML ids of the fields).
 */
class AjaxButton extends Field
{
    /** @var string */
    private string $buttonId = '';

    /** @var string */
    private string $buttonLabel = '';

    /** @var string */
    private string $ajaxUrl = '';

    /** @var string */
    private string $dataHtmlIds = '';

    /**
     * @param Arrays $arrays
     * @param Variables $variables
     * @param Context $context
     * @param array $data
     *
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        protected Arrays $arrays,
        protected Variables $variables,
        Context $context,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Sets the template of the button.
     *
     * @return void
     */
    protected function _construct(): void
    {
        parent::_construct();

        $this->setTemplate('Infrangible_Core::system/config/form/button.phtml');
    }

    /**
     * Renders the button with the settings of the field in system.xml.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $originalData = $element->getData('original_data');

        if (! is_array($originalData)) {
            $originalData = [];
        }

        $this->setButtonId($this->variables->stringValue($element->getHtmlId()));
        $this->setButtonLabel(
            $this->variables->stringValue(
                $this->arrays->getValue(
                    $originalData,
                    'button_label',
                    ''
                )
            )
        );
        $this->setAjaxUrl(
            $this->getUrl(
                $this->variables->stringValue(
                    $this->arrays->getValue(
                        $originalData,
                        'button_url',
                        ''
                    )
                ),
                ['_current' => true]
            )
        );
        $this->setDataHtmlIds(
            $this->variables->stringValue(
                $this->arrays->getValue(
                    $originalData,
                    'data_html_ids',
                    ''
                )
            )
        );

        return $this->_toHtml();
    }

    /**
     * Returns the button id.
     *
     * @return string
     */
    public function getButtonId(): string
    {
        return $this->buttonId;
    }

    /**
     * Sets the button id.
     *
     * @param string $buttonId
     *
     * @return void
     */
    public function setButtonId(string $buttonId): void
    {
        $this->buttonId = $buttonId;
    }

    /**
     * Returns the button label.
     *
     * @return string
     */
    public function getButtonLabel(): string
    {
        return $this->buttonLabel;
    }

    /**
     * Sets the button label.
     *
     * @param string $buttonLabel
     *
     * @return void
     */
    public function setButtonLabel(string $buttonLabel): void
    {
        $this->buttonLabel = $buttonLabel;
    }

    /**
     * Returns the URL that the button sends the request to.
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->ajaxUrl;
    }

    /**
     * Sets the URL that the button sends the request to.
     *
     * @param string $ajaxUrl
     *
     * @return void
     */
    public function setAjaxUrl(string $ajaxUrl): void
    {
        $this->ajaxUrl = $ajaxUrl;
    }

    /**
     * Returns the comma-separated HTML ids of the fields whose values are sent with the request.
     *
     * @return string
     */
    public function getDataHtmlIds(): string
    {
        return $this->dataHtmlIds;
    }

    /**
     * Sets the comma-separated HTML ids of the fields whose values are sent with the request.
     *
     * @param string $dataHtmlIds
     *
     * @return void
     */
    public function setDataHtmlIds(string $dataHtmlIds): void
    {
        $this->dataHtmlIds = $dataHtmlIds;
    }
}
