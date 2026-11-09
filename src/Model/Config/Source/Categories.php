<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source;

use Infrangible\Core\Helper\Category;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Data\Tree\Node;

/**
 * Options of all categories as indented tree.
 */
class Categories implements OptionSourceInterface
{
    /**
     * @param Category $categoryHelper
     * @param TreeFactory $categoryTreeFactory
     */
    public function __construct(
        protected Category $categoryHelper,
        protected TreeFactory $categoryTreeFactory
    ) {
    }

    /**
     * Returns the categories as indented tree for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionArray(): array
    {
        $tree = $this->categoryTreeFactory->create();

        $tree->load();

        $nodes = $tree->getNodes();

        $options = [['value' => '', 'label' => __('--Please Select--')]];

        $spaceChar = "\u{2003}";
        $arrowChar = "\u{2937}";

        /** @var Node $node */
        foreach ($nodes as $node) {
            $categoryId = $node->getData('entity_id');
            $categoryId = is_numeric($categoryId) ? (int)$categoryId : 0;
            $level = $node->getData('level');
            $level = is_numeric($level) ? (int)$level : 0;

            $category = $this->categoryHelper->loadCategory($categoryId);

            $options[] = [
                'value' => $categoryId,
                'label' => sprintf('%s%s %s', str_repeat($spaceChar, $level), $arrowChar, $category->getName())
            ];
        }

        return $options;
    }

    /**
     * Returns the categories as id => indented name.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptions(): array
    {
        $tree = $this->categoryTreeFactory->create();

        $tree->load();

        $nodes = $tree->getNodes();

        $options = [];

        $spaceChar = "\u{2003}";
        $arrowChar = "\u{2937}";

        /** @var Node $node */
        foreach ($nodes as $node) {
            $categoryId = $node->getData('entity_id');
            $categoryId = is_numeric($categoryId) ? (int)$categoryId : 0;
            $level = $node->getData('level');
            $level = is_numeric($level) ? (int)$level : 0;

            $category = $this->categoryHelper->loadCategory($categoryId);

            $options[ $categoryId ] =
                sprintf('%s%s %s', str_repeat($spaceChar, $level), $arrowChar, $category->getName());
        }

        return $options;
    }
}
