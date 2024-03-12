<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Review\Model\ResourceModel\Review\Collection;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory;
use Magento\Review\Model\Review\Summary;
use Magento\Review\Model\Review\SummaryFactory;
use Magento\Review\Model\ReviewFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Review
{
    /** @var ReviewFactory */
    protected $reviewFactory;

    /** @var \Magento\Review\Model\ResourceModel\ReviewFactory */
    protected $reviewResourceFactory;

    /** @var CollectionFactory */
    protected $reviewCollectionFactory;

    /** @var SummaryFactory */
    protected $reviewSummaryFactory;

    /** @var \Magento\Review\Model\ResourceModel\Review\SummaryFactory */
    protected $reviewSummaryResourceFactory;

    /** @var \Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory */
    protected $reviewSummaryCollectionFactory;

    /**
     * @param ReviewFactory                                                        $reviewFactory
     * @param \Magento\Review\Model\ResourceModel\ReviewFactory                    $reviewResourceFactory
     * @param CollectionFactory                                                    $reviewCollectionFactory
     * @param SummaryFactory                                                       $reviewSummaryFactory
     * @param \Magento\Review\Model\ResourceModel\Review\SummaryFactory            $reviewSummaryResourceFactory
     * @param \Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory $reviewSummaryCollectionFactory
     */
    public function __construct(
        ReviewFactory $reviewFactory,
        \Magento\Review\Model\ResourceModel\ReviewFactory $reviewResourceFactory,
        CollectionFactory $reviewCollectionFactory,
        SummaryFactory $reviewSummaryFactory,
        \Magento\Review\Model\ResourceModel\Review\SummaryFactory $reviewSummaryResourceFactory,
        \Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory $reviewSummaryCollectionFactory)
    {
        $this->reviewFactory = $reviewFactory;
        $this->reviewResourceFactory = $reviewResourceFactory;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->reviewSummaryFactory = $reviewSummaryFactory;
        $this->reviewSummaryResourceFactory = $reviewSummaryResourceFactory;
        $this->reviewSummaryCollectionFactory = $reviewSummaryCollectionFactory;
    }

    /**
     * @return \Magento\Review\Model\Review
     */
    public function newReview(): \Magento\Review\Model\Review
    {
        return $this->reviewFactory->create();
    }

    /**
     * @param int $reviewId
     *
     * @return \Magento\Review\Model\Review
     */
    public function loadReview(int $reviewId): \Magento\Review\Model\Review
    {
        $review = $this->newReview();

        $this->reviewResourceFactory->create()->load($review, $reviewId);

        return $review;
    }

    /**
     * @param \Magento\Review\Model\Review $review
     *
     * @throws AlreadyExistsException
     */
    public function saveReview(\Magento\Review\Model\Review $review)
    {
        $this->reviewResourceFactory->create()->save($review);
    }

    /**
     * @return Collection
     */
    public function getReviewCollection(): Collection
    {
        return $this->reviewCollectionFactory->create();
    }

    /**
     * @return Summary
     */
    public function newReviewSummary(): Summary
    {
        return $this->reviewSummaryFactory->create();
    }

    /**
     * @param int $reviewSummaryId
     *
     * @return Summary
     */
    public function loadReviewSummary(int $reviewSummaryId): Summary
    {
        $reviewSummary = $this->newReviewSummary();

        $this->reviewSummaryResourceFactory->create()->load($reviewSummary, $reviewSummaryId);

        return $reviewSummary;
    }

    /**
     * @param Summary $reviewSummary
     *
     * @throws AlreadyExistsException
     */
    public function saveReviewSummary(Summary $reviewSummary)
    {
        $this->reviewSummaryResourceFactory->create()->save($reviewSummary);
    }

    /**
     * @return \Magento\Review\Model\ResourceModel\Review\Summary\Collection
     */
    public function getReviewSummaryCollection(): \Magento\Review\Model\ResourceModel\Review\Summary\Collection
    {
        return $this->reviewSummaryCollectionFactory->create();
    }
}
