<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Cms\Block\BlockByIdentifier;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Psr\Log\LoggerInterface;

class Block extends AbstractHelper
{
    /** @var LayoutInterface */
    private readonly LayoutInterface $layout;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param \Magento\Framework\View\Element\Context $viewContext
     */
    public function __construct(
        Context $context,
        protected LoggerInterface $logger,
        \Magento\Framework\View\Element\Context $viewContext
    ) {
        parent::__construct($context);

        $this->layout = $viewContext->getLayout();
    }

    /**
     * Creates a block in the current layout and sets the data on it.
     *
     * @param string $blockClassName
     * @param array $blockData
     * @param string $name
     * @param array $blockArguments
     *
     * @phpstan-param class-string<BlockInterface> $blockClassName
     * @phpstan-param array<string, mixed> $blockData
     * @phpstan-param array<string, mixed> $blockArguments
     *
     * @return BlockInterface|null
     */
    public function createBlock(
        string $blockClassName,
        array $blockData = [],
        string $name = '',
        array $blockArguments = []
    ): ?BlockInterface {
        return $this->createLayoutBlock(
            $this->layout,
            $blockClassName,
            $blockData,
            $name,
            $blockArguments
        );
    }

    /**
     * Creates a block in the layout and sets the data on it.
     *
     * @param LayoutInterface $layout
     * @param string $blockClassName
     * @param array $blockData
     * @param string $name
     * @param array $blockArguments
     *
     * @phpstan-param class-string<BlockInterface> $blockClassName
     * @phpstan-param array<string, mixed> $blockData
     * @phpstan-param array<string, mixed> $blockArguments
     *
     * @return BlockInterface|null
     */
    public function createLayoutBlock(
        LayoutInterface $layout,
        string $blockClassName,
        array $blockData = [],
        string $name = '',
        array $blockArguments = []
    ): ?BlockInterface {
        $layoutBlock = $layout->createBlock(
            $blockClassName,
            $name,
            $blockArguments
        );

        if ($layoutBlock instanceof DataObject) {
            foreach ($blockData as $key => $value) {
                $layoutBlock->setDataUsingMethod(
                    $key,
                    $value
                );
            }
        }

        return $layoutBlock;
    }

    /**
     * Creates a block in the layout of the parent block and sets the data on it.
     *
     * @param AbstractBlock $block
     * @param string $blockClassName
     * @param array $blockData
     * @param string $name
     * @param array $blockArguments
     *
     * @phpstan-param class-string<BlockInterface> $blockClassName
     * @phpstan-param array<string, mixed> $blockData
     * @phpstan-param array<string, mixed> $blockArguments
     *
     * @return BlockInterface|null
     * @throws LocalizedException
     */
    public function createChildBlock(
        AbstractBlock $block,
        string $blockClassName,
        array $blockData = [],
        string $name = '',
        array $blockArguments = []
    ): ?BlockInterface {
        return $this->createLayoutBlock(
            $block->getLayout(),
            $blockClassName,
            $blockData,
            $name,
            $blockArguments
        );
    }

    /**
     * Creates a block in the current layout and returns its HTML.
     *
     * @param string $blockClassName
     * @param array $blockData
     * @param array $blockArguments
     *
     * @phpstan-param class-string<BlockInterface> $blockClassName
     * @phpstan-param array<string, mixed> $blockData
     * @phpstan-param array<string, mixed> $blockArguments
     *
     * @return string
     */
    public function renderBlock(
        string $blockClassName,
        array $blockData = [],
        array $blockArguments = []
    ): string {
        return $this->renderLayoutBlock(
            $this->layout,
            $blockClassName,
            $blockData,
            $blockArguments
        );
    }

    /**
     * Creates a block in the layout and returns its HTML.
     *
     * @param LayoutInterface $layout
     * @param string $blockClassName
     * @param array $blockData
     * @param array $blockArguments
     *
     * @phpstan-param class-string<BlockInterface> $blockClassName
     * @phpstan-param array<string, mixed> $blockData
     * @phpstan-param array<string, mixed> $blockArguments
     *
     * @return string
     */
    public function renderLayoutBlock(
        LayoutInterface $layout,
        string $blockClassName,
        array $blockData = [],
        array $blockArguments = []
    ): string {
        $block = $this->createLayoutBlock(
            $layout,
            $blockClassName,
            $blockData,
            '',
            $blockArguments
        );

        return $block ? $block->toHtml() : '';
    }

    /**
     * Creates a block in the layout of the parent block and returns its HTML.
     *
     * @param AbstractBlock $block
     * @param string $blockClassName
     * @param array $blockData
     * @param array $blockArguments
     *
     * @phpstan-param class-string<BlockInterface> $blockClassName
     * @phpstan-param array<string, mixed> $blockData
     * @phpstan-param array<string, mixed> $blockArguments
     *
     * @return string
     */
    public function renderChildBlock(
        AbstractBlock $block,
        string $blockClassName,
        array $blockData = [],
        array $blockArguments = []
    ): string {
        try {
            return $this->renderLayoutBlock(
                $block->getLayout(),
                $blockClassName,
                $blockData,
                $blockArguments
            );
        } catch (LocalizedException $exception) {
            $this->logger->error($exception);

            return '';
        }
    }

    /**
     * Renders a template with a template block in the layout.
     *
     * @param LayoutInterface $layout
     * @param string $templateFile
     * @param array $templateData
     * @param array $blockArguments
     *
     * @phpstan-param array<string, mixed> $blockArguments
     * @phpstan-param array<string, mixed> $templateData
     *
     * @return string
     */
    public function renderLayoutTemplateBlock(
        LayoutInterface $layout,
        string $templateFile,
        array $templateData = [],
        array $blockArguments = []
    ): string {
        return $this->renderLayoutTemplateExtendedBlock(
            $layout,
            Template::class,
            $templateFile,
            $templateData,
            $blockArguments
        );
    }

    /**
     * Renders a template with a template block in the layout of the parent block.
     *
     * @param AbstractBlock $block
     * @param string $templateFile
     * @param array $templateData
     * @param array $blockArguments
     *
     * @phpstan-param array<string, mixed> $blockArguments
     * @phpstan-param array<string, mixed> $templateData
     *
     * @return string
     */
    public function renderChildTemplateBlock(
        AbstractBlock $block,
        string $templateFile,
        array $templateData = [],
        array $blockArguments = []
    ): string {
        return $this->renderChildTemplateExtendedBlock(
            $block,
            Template::class,
            $templateFile,
            $templateData,
            $blockArguments
        );
    }

    /**
     * Renders a template with a block of the given class in the layout.
     *
     * @param LayoutInterface $layout
     * @param string $blockClassName
     * @param string $templateFile
     * @param array $templateData
     * @param array $blockArguments
     *
     * @phpstan-param class-string<BlockInterface> $blockClassName
     * @phpstan-param array<string, mixed> $blockArguments
     * @phpstan-param array<string, mixed> $templateData
     *
     * @return string
     */
    public function renderLayoutTemplateExtendedBlock(
        LayoutInterface $layout,
        string $blockClassName,
        string $templateFile,
        array $templateData = [],
        array $blockArguments = []
    ): string {
        $templateData[ 'template' ] = $templateFile;

        return $this->renderLayoutBlock(
            $layout,
            $blockClassName,
            $templateData,
            $blockArguments
        );
    }

    /**
     * Renders a template with a block of the given class in the layout of the parent block.
     *
     * @param AbstractBlock $block
     * @param string $blockClassName
     * @param string $templateFile
     * @param array $templateData
     * @param array $blockArguments
     *
     * @phpstan-param class-string<BlockInterface> $blockClassName
     * @phpstan-param array<string, mixed> $blockArguments
     * @phpstan-param array<string, mixed> $templateData
     *
     * @return string
     */
    public function renderChildTemplateExtendedBlock(
        AbstractBlock $block,
        string $blockClassName,
        string $templateFile,
        array $templateData = [],
        array $blockArguments = []
    ): string {
        $templateData[ 'template' ] = $templateFile;

        return $this->renderChildBlock(
            $block,
            $blockClassName,
            $templateData,
            $blockArguments
        );
    }

    /**
     * Returns the child block with the alias, or creates it if the block has no such child.
     *
     * @param AbstractBlock $block
     * @param string $childBlockAlias
     * @param string $childBlockClassName
     * @param array $blockData
     *
     * @phpstan-param class-string<BlockInterface> $childBlockClassName
     * @phpstan-param array<string, mixed> $blockData
     *
     * @return AbstractBlock
     */
    public function getOrCreateChildBlock(
        AbstractBlock $block,
        string $childBlockAlias,
        string $childBlockClassName,
        array $blockData = []
    ): AbstractBlock {
        $childBlock = $block->getChildBlock($childBlockAlias);

        if ($childBlock instanceof AbstractBlock) {
            return $childBlock;
        }

        return $block->addChild(
            $childBlockAlias,
            $childBlockClassName,
            $blockData
        );
    }

    /**
     * Renders an element (block or container) of the current layout.
     *
     * @param string $elementName
     * @param bool $useCache
     *
     * @return string|null
     */
    public function renderElement(string $elementName, bool $useCache = true): ?string
    {
        return $this->renderLayoutElement(
            $this->layout,
            $elementName,
            $useCache
        );
    }

    /**
     * Renders an element (block or container) of the layout.
     *
     * @param LayoutInterface $layout
     * @param string $elementName
     * @param bool $useCache
     *
     * @return string|null
     */
    public function renderLayoutElement(LayoutInterface $layout, string $elementName, bool $useCache = true): ?string
    {
        return $layout->renderElement(
            $elementName,
            $useCache
        );
    }

    /**
     * Renders an element (block or container) of the layout of the parent block.
     *
     * @param AbstractBlock $block
     * @param string $elementName
     * @param bool $useCache
     *
     * @return string|null
     */
    public function renderChildElement(AbstractBlock $block, string $elementName, bool $useCache = true): ?string
    {
        try {
            return $this->renderLayoutElement(
                $block->getLayout(),
                $elementName,
                $useCache
            );
        } catch (LocalizedException $exception) {
            $this->logger->error($exception);

            return '';
        }
    }

    /**
     * Renders the CMS block with the identifier in the current layout.
     *
     * @param string $identifier
     *
     * @return string
     */
    public function renderCmsBlock(string $identifier): string
    {
        return $this->renderLayoutCmsBlock(
            $this->layout,
            $identifier
        );
    }

    /**
     * Renders the CMS block with the identifier in the layout.
     *
     * @param LayoutInterface $layout
     * @param string $identifier
     *
     * @return string
     */
    public function renderLayoutCmsBlock(LayoutInterface $layout, string $identifier): string
    {
        /** @var BlockByIdentifier $cmsBlock */
        $cmsBlock = $this->createLayoutBlock(
            $layout,
            BlockByIdentifier::class
        );

        $cmsBlock->setData(
            'identifier',
            $identifier
        );

        return $cmsBlock->toHtml();
    }

    /**
     * Renders the CMS block with the identifier in the layout of the parent block.
     *
     * @param AbstractBlock $block
     * @param string $identifier
     *
     * @return string
     */
    public function renderChildCmsBlock(AbstractBlock $block, string $identifier): string
    {
        try {
            return $this->renderLayoutCmsBlock(
                $block->getLayout(),
                $identifier
            );
        } catch (LocalizedException $exception) {
            $this->logger->error($exception);

            return '';
        }
    }
}
