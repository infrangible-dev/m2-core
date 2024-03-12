<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Email\Model\ResourceModel\Template\CollectionFactory;
use Magento\Framework\Data\Collection;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Template
{
    /** @var CollectionFactory */
    protected $templateCollectionFactory;

    /**
     * TemplateHelper constructor.
     *
     * @param CollectionFactory $templateCollectionFactory
     */
    public function __construct(CollectionFactory $templateCollectionFactory)
    {
        $this->templateCollectionFactory = $templateCollectionFactory;
    }

    /**
     * @return string[]
     */
    public function getAllTemplates(): array
    {
        $result = [];

        $mailTemplates = $this->templateCollectionFactory->create();

        $mailTemplates->setOrder('template_code', Collection::SORT_ORDER_ASC);

        /** @var \Magento\Email\Model\Template $mailTemplate */
        foreach ($mailTemplates as $mailTemplate) {
            $result[$mailTemplate->getId()] = $mailTemplate->getTemplateCode();
        }

        return $result;
    }
}
