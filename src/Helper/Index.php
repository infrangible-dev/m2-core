<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\StateInterface;
use Magento\Indexer\Model\Indexer;
use Magento\Indexer\Model\Indexer\State;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Indexer\Model\ResourceModel\Indexer\StateFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class Index
{
    /**
     * @param LoggerInterface $logging
     * @param StateFactory $stateResourceFactory
     * @param IndexerFactory $indexerFactory
     */
    public function __construct(
        protected LoggerInterface $logging,
        protected StateFactory $stateResourceFactory,
        protected IndexerFactory $indexerFactory
    ) {
    }

    /**
     * Runs a full reindex, or marks the index as invalid if the indexer is in "update by schedule" mode.
     *
     * @param Indexer $indexer
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws Throwable
     */
    public function runIndexProcess(Indexer $indexer): void
    {
        if ($indexer->isScheduled()) {
            /** @var State $state */
            $state = $indexer->getState();

            $state->setStatus(StateInterface::STATUS_INVALID);

            $this->stateResourceFactory->create()->save($state);

            return;
        }

        $this->logging->info(sprintf('Starting indexer with id: %s', $indexer->getId()));

        $indexer->reindexAll();

        $this->logging->info(sprintf('Finished indexer with id: %s', $indexer->getId()));
    }

    /**
     * Loads the indexer with the ID.
     *
     * @param string $indexerName
     *
     * @return IndexerInterface
     */
    public function loadIndexer(string $indexerName): IndexerInterface
    {
        return $this->indexerFactory->create()->load($indexerName);
    }
}
