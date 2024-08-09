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
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Block
    extends AbstractHelper
{
    protected $logger;

    public function __construct(Context $context, LoggerInterface $logger)
    {
        parent::__construct($context);

        $this->logger = $logger;
    }

    public function renderCmsBlock(AbstractBlock $block, string $identifier): string
    {
        /** @var BlockByIdentifier $cmsBlock */
        try {
            $cmsBlock = $this->createLayoutBlock($block, BlockByIdentifier::class);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception);

            return '';
        }

        $cmsBlock->setData('identifier', $identifier);

        return $cmsBlock->toHtml();
    }

    /**
     * @throws LocalizedException
     */
    public function createLayoutBlock(
        AbstractBlock $block,
        string $blockClassName,
        array $blockData = [],
        string $name = ''): ?BlockInterface
    {
        $layoutBlock = $block->getLayout()->createBlock($blockClassName, $name);

        if ($layoutBlock instanceof DataObject) {
            foreach ($blockData as $key => $value) {
                $layoutBlock->setDataUsingMethod($key, $value);
            }
        }

        return $layoutBlock;
    }

    public function renderLayoutBlock(AbstractBlock $block, string $blockClassName, array $blockData): string
    {
        try {
            $block = $this->createLayoutBlock($block, $blockClassName, $blockData);

            return $block ? $block->toHtml() : '';
        } catch (LocalizedException $exception) {
            $this->logger->error($exception);

            return '';
        }
    }

    public function renderTemplateBlock(AbstractBlock $block, string $templateFile, array $templateData): string
    {
        $templateData[ 'template' ] = $templateFile;

        return $this->renderLayoutBlock($block, Template::class, $templateData);
    }
}
