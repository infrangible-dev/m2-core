<?php

declare(strict_types=1);

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

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Block extends AbstractHelper
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var LayoutInterface */
    private $layout;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        \Magento\Framework\View\Element\Context $viewContext
    ) {
        parent::__construct($context);

        $this->logger = $logger;

        $this->layout = $viewContext->getLayout();
    }

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

    public function getOrCreateChildBlock(
        AbstractBlock $block,
        string $childBlockAlias,
        string $childBlockClassName,
        array $blockData = []
    ): AbstractBlock {
        $childBlock = $block->getChildBlock($childBlockAlias);

        if ($childBlock) {
            return $childBlock;
        } else {
            return $block->addChild(
                $childBlockAlias,
                $childBlockClassName,
                $blockData
            );
        }
    }

    public function renderElement(string $elementName, $useCache = true): ?string
    {
        return $this->renderLayoutElement(
            $this->layout,
            $elementName,
            $useCache
        );
    }

    public function renderLayoutElement(LayoutInterface $layout, string $elementName, $useCache = true): ?string
    {
        return $layout->renderElement(
            $elementName,
            $useCache
        );
    }

    public function renderChildElement(AbstractBlock $block, string $elementName, $useCache = true): ?string
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

    public function renderCmsBlock(string $identifier): string
    {
        return $this->renderLayoutCmsBlock(
            $this->layout,
            $identifier
        );
    }

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
