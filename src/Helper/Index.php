<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

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

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Index
{
    /** @var LoggerInterface */
    protected $logging;

    /** @var StateFactory */
    protected $stateResourceFactory;

    /** @var IndexerFactory */
    protected $indexerFactory;

    public function __construct(
        LoggerInterface $logging,
        StateFactory $stateResourceFactory,
        IndexerFactory $indexerFactory
    ) {
        $this->logging = $logging;
        $this->stateResourceFactory = $stateResourceFactory;
        $this->indexerFactory = $indexerFactory;
    }

    /**
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

    public function loadIndexer(string $indexerName): IndexerInterface
    {
        return $this->indexerFactory->create()->load($indexerName);
    }
}
