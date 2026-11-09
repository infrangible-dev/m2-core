<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Review\Model\ResourceModel\Review\Collection;
use Magento\Review\Model\Review\Summary;
use RuntimeException;

/**
 * Reviews and review summaries. Requires the optional module Magento_Review, which is only loaded when a method is
 * called.
 */
class Review
{
    /**
     * @param Instances $instanceHelper
     */
    public function __construct(
        protected Instances $instanceHelper
    ) {
    }

    /**
     * Creates a new review.
     *
     * @return \Magento\Review\Model\Review
     * @throws RuntimeException if Magento_Review is not installed
     */
    public function newReview(): \Magento\Review\Model\Review
    {
        return $this->instanceHelper->createInstance(\Magento\Review\Model\Review::class);
    }

    /**
     * Loads the review.
     *
     * @param int $reviewId
     *
     * @return \Magento\Review\Model\Review
     * @throws RuntimeException if Magento_Review is not installed
     */
    public function loadReview(int $reviewId): \Magento\Review\Model\Review
    {
        $review = $this->newReview();

        $this->getReviewResource()->load($review, $reviewId);

        return $review;
    }

    /**
     * Saves the review.
     *
     * @param \Magento\Review\Model\Review $review
     *
     * @return void
     * @throws Exception
     */
    public function saveReview(\Magento\Review\Model\Review $review): void
    {
        $this->getReviewResource()->save($review);
    }

    /**
     * Returns the review collection.
     *
     * @return Collection
     * @throws RuntimeException if Magento_Review is not installed
     */
    public function getReviewCollection(): Collection
    {
        return $this->instanceHelper->createInstance(Collection::class);
    }

    /**
     * Creates a new review summary.
     *
     * @return Summary
     * @throws RuntimeException if Magento_Review is not installed
     */
    public function newReviewSummary(): Summary
    {
        return $this->instanceHelper->createInstance(Summary::class);
    }

    /**
     * Loads the review summary.
     *
     * @param int $reviewSummaryId
     *
     * @return Summary
     * @throws RuntimeException if Magento_Review is not installed
     */
    public function loadReviewSummary(int $reviewSummaryId): Summary
    {
        $reviewSummary = $this->newReviewSummary();

        $this->getReviewSummaryResource()->load($reviewSummary, $reviewSummaryId);

        return $reviewSummary;
    }

    /**
     * Saves the review summary.
     *
     * @param Summary $reviewSummary
     *
     * @return void
     * @throws Exception
     */
    public function saveReviewSummary(Summary $reviewSummary): void
    {
        $this->getReviewSummaryResource()->save($reviewSummary);
    }

    /**
     * Returns the review summary collection.
     *
     * @return \Magento\Review\Model\ResourceModel\Review\Summary\Collection
     * @throws RuntimeException if Magento_Review is not installed
     */
    public function getReviewSummaryCollection(): \Magento\Review\Model\ResourceModel\Review\Summary\Collection
    {
        return $this->instanceHelper->createInstance(
            \Magento\Review\Model\ResourceModel\Review\Summary\Collection::class
        );
    }

    /**
     * Returns the review resource.
     *
     * @return \Magento\Review\Model\ResourceModel\Review
     * @throws RuntimeException if Magento_Review is not installed
     */
    private function getReviewResource(): \Magento\Review\Model\ResourceModel\Review
    {
        return $this->instanceHelper->getSharedInstance(\Magento\Review\Model\ResourceModel\Review::class);
    }

    /**
     * Returns the review summary resource.
     *
     * @return \Magento\Review\Model\ResourceModel\Review\Summary
     * @throws RuntimeException if Magento_Review is not installed
     */
    private function getReviewSummaryResource(): \Magento\Review\Model\ResourceModel\Review\Summary
    {
        return $this->instanceHelper->getSharedInstance(\Magento\Review\Model\ResourceModel\Review\Summary::class);
    }
}
