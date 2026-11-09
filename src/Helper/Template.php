<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Email\Model\ResourceModel\Template\CollectionFactory;
use Magento\Framework\Data\Collection;

class Template
{
    /** @var CollectionFactory */
    protected CollectionFactory $templateCollectionFactory;

    /**
     * @param CollectionFactory $templateCollectionFactory
     */
    public function __construct(CollectionFactory $templateCollectionFactory)
    {
        $this->templateCollectionFactory = $templateCollectionFactory;
    }

    /**
     * Returns the codes of all email templates stored in the database, sorted by code.
     *
     * @return string[] template id => template code
     */
    public function getAllTemplates(): array
    {
        $result = [];

        $mailTemplates = $this->templateCollectionFactory->create();

        $mailTemplates->setOrder('template_code', Collection::SORT_ORDER_ASC);

        /** @var \Magento\Email\Model\Template $mailTemplate */
        foreach ($mailTemplates as $mailTemplate) {
            // getData() instead of the magic getTemplateCode(), whose __call() declares a LocalizedException
            $templateCode = $mailTemplate->getData('template_code');

            $result[$mailTemplate->getId()] = is_scalar($templateCode) ? (string)$templateCode : '';
        }

        return $result;
    }
}
