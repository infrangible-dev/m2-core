<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Options of the CMS blocks.
 */
class CmsBlock extends AbstractCms
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        protected CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Returns a new collection of the CMS blocks.
     *
     * @return AbstractDb
     */
    protected function createCollection(): AbstractDb
    {
        return $this->collectionFactory->create();
    }
}
