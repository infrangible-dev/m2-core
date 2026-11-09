<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Variables;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item\Collection;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\CatalogInventory\Model\Stock\ItemFactory;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\DB\QueryBuilderFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Stock
{
    /**
     * @param Variables $variables
     * @param Stores $storeHelper
     * @param ItemFactory $stockItemFactory
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockItemResourceFactory
     * @param CollectionFactory $stockItemCollectionFactory
     * @param StockItemRepository $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory
     * @param QueryBuilderFactory $queryBuilderFactory
     */
    public function __construct(
        protected Variables $variables,
        protected Stores $storeHelper,
        protected ItemFactory $stockItemFactory,
        protected \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockItemResourceFactory,
        protected CollectionFactory $stockItemCollectionFactory,
        protected StockItemRepository $stockItemRepository,
        protected StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory,
        protected QueryBuilderFactory $queryBuilderFactory
    ) {
    }

    /**
     * Creates a new stock item.
     *
     * @return Item
     */
    public function newStockItem(): Item
    {
        return $this->stockItemFactory->create();
    }

    /**
     * Loads the stock item.
     *
     * @param int $stockItemId
     *
     * @return StockItemInterface
     * @throws NoSuchEntityException if the stock item does not exist
     * @phpstan-ignore throws.unusedType (the repository throws it, but does not declare it)
     */
    public function loadStockItem(int $stockItemId): StockItemInterface
    {
        return $this->stockItemRepository->get($stockItemId);
    }

    /**
     * Loads the stock item by product.
     *
     * @param int $productId
     * @param int $stockId
     *
     * @return Item
     */
    public function loadStockItemByProduct(int $productId, int $stockId): Item
    {
        $stockItem = $this->newStockItem();

        $this->stockItemResourceFactory->create()->loadByProductId($stockItem, $productId, $stockId);

        return $stockItem;
    }

    /**
     * Saves the stock item.
     *
     * @param Item $stockItem
     *
     * @return void
     * @throws CouldNotSaveException
     * @phpstan-ignore throws.unusedType (the repository throws it, but does not declare it)
     */
    public function saveStockItem(Item $stockItem): void
    {
        $this->stockItemRepository->save($stockItem);
    }

    /**
     * Returns a new collection of stock items, filtered by products and the website of the store if given.
     *
     * @param int[]           $productIds
     * @param int|string|null $storeId
     *
     * @return Collection
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getStockItemCollection(
        array $productIds = [],
        int|string|null $storeId = null
    ): Collection {
        if (count($productIds) > 0 || !$this->variables->isEmpty($storeId)) {
            $criteria = $this->stockItemCriteriaInterfaceFactory->create();

            if (count($productIds) > 0) {
                $criteria->setProductsFilter($productIds);
            }

            if (!$this->variables->isEmpty($storeId)) {
                $criteria->setScopeFilter(
                    $this->variables->intValue($this->storeHelper->getStore($storeId)->getWebsiteId())
                );
            }

            $queryBuilder = $this->queryBuilderFactory->create();

            $queryBuilder->setCriteria($criteria);
            $queryBuilder->setResource($this->stockItemResourceFactory->create());

            $query = $queryBuilder->create();

            return $this->stockItemCollectionFactory->create(['query' => $query]);
        } else {
            return $this->stockItemCollectionFactory->create();
        }
    }
}
