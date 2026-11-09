<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;

class Quote
{
    /** @var QuoteFactory */
    protected QuoteFactory $quoteFactory;

    /** @var CartRepositoryInterface */
    protected CartRepositoryInterface $quoteRepository;

    /** @var CollectionFactory */
    protected CollectionFactory $quoteCollectionFactory;

    /**
     * @param QuoteFactory $quoteFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param CollectionFactory $quoteCollectionFactory
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        CartRepositoryInterface $quoteRepository,
        CollectionFactory $quoteCollectionFactory
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
    }

    /**
     * Creates a new quote.
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function newQuote(): \Magento\Quote\Model\Quote
    {
        return $this->quoteFactory->create();
    }

    /**
     * Loads the quote.
     *
     * @param int $quoteId
     *
     * @return \Magento\Quote\Model\Quote
     * @throws NoSuchEntityException
     */
    public function loadQuote(int $quoteId): \Magento\Quote\Model\Quote
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);

        return $quote;
    }

    /**
     * Saves the quote.
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return void
     */
    public function saveQuote(\Magento\Quote\Model\Quote $quote): void
    {
        $this->quoteRepository->save($quote);
    }

    /**
     * Returns the quote collection.
     *
     * @return Collection
     */
    public function getQuoteCollection(): Collection
    {
        return $this->quoteCollectionFactory->create();
    }
}
