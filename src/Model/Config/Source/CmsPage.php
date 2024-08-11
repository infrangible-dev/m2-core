<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source;

use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CmsPage implements OptionSourceInterface
{
    /** @var CollectionFactory */
    protected $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function getAllOptions(): array
    {
        return $this->toOptionArray();
    }

    public function toOptionArray(): array
    {
        $cmsPageCollection = $this->collectionFactory->create();

        $cmsPageCollection->addOrder('title', Collection::SORT_ORDER_ASC);

        $options = [['value' => '', 'label' => __('--Please Select--')]];

        /** @var Block $cmsBlock */
        foreach ($cmsPageCollection as $cmsBlock) {
            $options[] = [
                'value' => $cmsBlock->getId(),
                'label' => $cmsBlock->getTitle()
            ];
        }

        return $options;
    }

    public function toOptions(): array
    {
        $cmsPageCollection = $this->collectionFactory->create();

        $cmsPageCollection->addOrder('title', Collection::SORT_ORDER_ASC);

        $options = [];

        /** @var Block $cmsBlock */
        foreach ($cmsPageCollection as $cmsBlock) {
            $options[ $cmsBlock->getId() ] = $cmsBlock->getTitle();
        }

        return $options;
    }
}
