<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source;

use Infrangible\Core\Helper\Category;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Data\Tree\Node;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Categories
    implements OptionSourceInterface
{
    /** @var Category */
    protected $categoryHelper;

    /** @var TreeFactory */
    protected $categoryTreeFactory;

    public function __construct(Category $categoryHelper, TreeFactory $categoryTreeFactory)
    {
        $this->categoryHelper = $categoryHelper;
        $this->categoryTreeFactory = $categoryTreeFactory;
    }

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
            $level = $node->getData('level');

            $category = $this->categoryHelper->loadCategory($categoryId);

            $options[] = [
                'value' => $categoryId,
                'label' => sprintf('%s%s %s', str_repeat($spaceChar, $level), $arrowChar, $category->getName())
            ];
        }

        return $options;
    }

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
            $level = $node->getData('level');

            $category = $this->categoryHelper->loadCategory($categoryId);

            $options[ $categoryId ] =
                sprintf('%s%s %s', str_repeat($spaceChar, $level), $arrowChar, $category->getName());
        }

        return $options;
    }
}
