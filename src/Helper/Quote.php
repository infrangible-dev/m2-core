<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Quote
{
    /** @var QuoteFactory */
    protected $quoteFactory;

    /** @var CartRepositoryInterface */
    protected $quoteRepository;

    /** @var CollectionFactory */
    protected $quoteCollectionFactory;

    public function __construct(
        QuoteFactory $quoteFactory,
        CartRepositoryInterface $quoteRepository,
        CollectionFactory $quoteCollectionFactory
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
    }

    public function newQuote(): \Magento\Quote\Model\Quote
    {
        return $this->quoteFactory->create();
    }

    /**
     * @throws NoSuchEntityException
     */
    public function loadQuote(int $quoteId): \Magento\Quote\Model\Quote
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);

        return $quote;
    }

    public function saveQuote(\Magento\Quote\Model\Quote $quote): void
    {
        $this->quoteRepository->save($quote);
    }

    public function getQuoteCollection(): Collection
    {
        return $this->quoteCollectionFactory->create();
    }
}
