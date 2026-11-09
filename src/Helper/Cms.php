<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use FeWeDev\Xml\SimpleXml;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page\Collection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SimpleXMLElement;

class Cms
{
    /** string for cms block identifier */
    public const STRING_IDENTIFIER = 'identifier';

    /**
     * @param Variables $variables
     * @param Arrays $arrays
     * @param Files $files
     * @param LoggerInterface $logging
     * @param PageFactory $cmsPageFactory
     * @param \Magento\Cms\Model\ResourceModel\PageFactory $cmsPageResourceFactory
     * @param CollectionFactory $cmsPageCollectionFactory
     * @param BlockFactory $cmsBlockFactory
     * @param \Magento\Cms\Model\ResourceModel\BlockFactory $cmsBlockResourceFactory
     * @param \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $cmsBlockCollectionFactory
     * @param SimpleXml $simpleXml
     */
    public function __construct(
        protected Variables $variables,
        protected Arrays $arrays,
        protected Files $files,
        protected LoggerInterface $logging,
        protected PageFactory $cmsPageFactory,
        protected \Magento\Cms\Model\ResourceModel\PageFactory $cmsPageResourceFactory,
        protected CollectionFactory $cmsPageCollectionFactory,
        protected BlockFactory $cmsBlockFactory,
        protected \Magento\Cms\Model\ResourceModel\BlockFactory $cmsBlockResourceFactory,
        protected \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $cmsBlockCollectionFactory,
        protected SimpleXml $simpleXml
    ) {
    }

    /**
     * Creates a new cms page.
     *
     * @return Page
     */
    public function newCmsPage(): Page
    {
        return $this->cmsPageFactory->create();
    }

    /**
     * Loads the cms page.
     *
     * @param int $cmsPageId
     *
     * @return Page
     */
    public function loadCmsPage(int $cmsPageId): Page
    {
        $cmsPage = $this->newCmsPage();

        $this->cmsPageResourceFactory->create()->load(
            $cmsPage,
            $cmsPageId
        );

        return $cmsPage;
    }

    /**
     * Loads the CMS page with the identifier, for the store if a store ID is given.
     *
     * @param string $identifier
     * @param int|null $storeId
     *
     * @return Page
     */
    public function loadCmsPageByIdentifier(string $identifier, ?int $storeId = null): Page
    {
        $cmsPage = $this->newCmsPage();

        if (! $this->variables->isEmpty($storeId)) {
            $cmsPage->setData(
                'store_id',
                $storeId
            );
        }

        $this->cmsPageResourceFactory->create()->load(
            $cmsPage,
            $identifier,
            'identifier'
        );

        return $cmsPage;
    }

    /**
     * Saves the cms page.
     *
     * @param Page $cmsPage
     *
     * @return void
     * @throws Exception
     */
    public function saveCmsPage(Page $cmsPage): void
    {
        $this->cmsPageResourceFactory->create()->save($cmsPage);
    }

    /**
     * Returns the cms page collection.
     *
     * @return Collection
     */
    public function getCmsPageCollection(): Collection
    {
        return $this->cmsPageCollectionFactory->create();
    }

    /**
     * Creates a new cms block.
     *
     * @return Block
     */
    public function newCmsBlock(): Block
    {
        return $this->cmsBlockFactory->create();
    }

    /**
     * Loads the cms block.
     *
     * @param int $cmsBlockId
     *
     * @return Block
     */
    public function loadCmsBlock(int $cmsBlockId): Block
    {
        $cmsBlock = $this->newCmsBlock();

        $this->cmsBlockResourceFactory->create()->load(
            $cmsBlock,
            $cmsBlockId
        );

        return $cmsBlock;
    }

    /**
     * Loads the CMS block with the identifier, for the store if a store ID is given.
     *
     * @param string $identifier
     * @param int|null $storeId
     *
     * @return Block
     */
    public function loadCmsBlockByIdentifier(string $identifier, ?int $storeId = null): Block
    {
        $cmsBlock = $this->newCmsBlock();

        if (! $this->variables->isEmpty($storeId)) {
            $cmsBlock->setData(
                'store_id',
                $storeId
            );
        }

        $this->cmsBlockResourceFactory->create()->load(
            $cmsBlock,
            $identifier,
            'identifier'
        );

        return $cmsBlock;
    }

    /**
     * Saves the cms block.
     *
     * @param Block $cmsBlock
     *
     * @return void
     * @throws Exception
     */
    public function saveCmsBlock(Block $cmsBlock): void
    {
        $this->cmsBlockResourceFactory->create()->save($cmsBlock);
    }

    /**
     * Returns the cms block collection.
     *
     * @return \Magento\Cms\Model\ResourceModel\Block\Collection
     */
    public function getCmsBlockCollection(): \Magento\Cms\Model\ResourceModel\Block\Collection
    {
        return $this->cmsBlockCollectionFactory->create();
    }

    /**
     * Imports the CMS pages of an XML file (path absolute or relative to the Magento root).
     *
     * @param string $xmlFileName
     * @param bool $overwriteExisting
     *
     * @return string[] the identifiers of the pages that were not imported
     * @throws Exception
     */
    public function importPagesFromXmlFile(string $xmlFileName, bool $overwriteExisting = false): array
    {
        $fileName = $this->files->determineFilePath($xmlFileName);

        $xmlElement = $this->loadXmlFile($fileName);

        return $this->importPagesFromXml(
            $xmlElement,
            $overwriteExisting
        );
    }

    /**
     * Imports the CMS pages of an XML document.
     *
     * @param SimpleXMLElement $xml
     * @param bool $overwriteExisting
     *
     * @return string[] the identifiers of the pages that were not imported
     * @throws Exception
     */
    public function importPagesFromXml(SimpleXMLElement $xml, bool $overwriteExisting = false): array
    {
        $notImported = [];

        // <root><pages><cms_block/><cms_block/></pages></root>
        foreach ($xml->children()->children() as $pageXml) {
            $imported = $this->importPageFromXml(
                $pageXml,
                $overwriteExisting
            );

            if (! $imported) {
                $notImported[] = (string)$pageXml->{static::STRING_IDENTIFIER};
            }
        }

        return $notImported;
    }

    /**
     * Imports a CMS page from its XML element.
     *
     * @param SimpleXMLElement $pageXml
     * @param bool $overwriteExisting
     *
     * @return bool
     * @throws Exception
     */
    public function importPageFromXml(SimpleXMLElement $pageXml, bool $overwriteExisting): bool
    {
        $pageData = $this->simpleXml->xmlToArray($pageXml);

        return $this->importPage(
            $pageData,
            $overwriteExisting
        );
    }

    /**
     * Imports a CMS page.
     *
     * Existing pages with the identifier in the stores are replaced if $overwriteExisting is true.
     *
     * @param array $pageData
     * @param bool $overwriteExisting
     *
     * @phpstan-param array<string, mixed> $pageData
     *
     * @return bool true if the page was saved
     * @throws LocalizedException
     */
    public function importPage(array $pageData, bool $overwriteExisting = false): bool
    {
        $pageIdentifier = $this->getStringValue(
            $pageData,
            static::STRING_IDENTIFIER
        );

        $stores = $this->arrays->getValue(
            $pageData,
            'stores:item',
            []
        );

        if ($this->variables->isEmpty($stores)) {
            $storeIds = [0];
        } else {
            $storeIds = is_array($stores) ? $stores : [$stores];
        }

        $oldPages = $this->cmsPageCollectionFactory->create();

        $oldPages->addFieldToFilter(
            static::STRING_IDENTIFIER,
            $pageIdentifier
        );
        $oldPages->addStoreFilter($storeIds);

        $oldPages->load();

        if (count($oldPages) > 0 && ! $overwriteExisting) {
            // page already exists and we are not allowed to overwrite
            $this->logging->info(
                sprintf(
                    'Skipping existing pages with identifier: %s',
                    $pageIdentifier
                )
            );

            return false;
        }

        /** @var Page $oldPage */
        foreach ($oldPages as $oldPage) {
            try {
                $this->cmsPageResourceFactory->create()->delete($oldPage);

                $this->logging->info(
                    sprintf(
                        'Removing pages(s) with identifier: %s and title: %s for stores: %s',
                        $oldPage->getIdentifier(),
                        $oldPage->getTitle(),
                        implode(
                            ', ',
                            $storeIds
                        )
                    )
                );
            } catch (Exception $exception) {
                $this->logging->error($exception);
            }
        }

        $layoutUpdateXml = $this->arrays->getValue(
            $pageData,
            'layout_update_xml'
        );

        if ($this->variables->isEmpty($layoutUpdateXml)) {
            $layoutUpdateXml = null;
        }

        $pageModelData = [
            'title'             => $this->getStringValue(
                $pageData,
                'title'
            ),
            'identifier'        => $pageIdentifier,
            'content'           => $this->getStringValue(
                $pageData,
                'content'
            ),
            'is_active'         => $this->variables->intValue(
                $this->arrays->getValue(
                    $pageData,
                    'is_active',
                    true
                )
            ),
            'stores'            => $storeIds,
            'page_layout'       => $this->getStringValue(
                $pageData,
                'page_layout'
            ),
            'layout_update_xml' => $layoutUpdateXml
        ];

        $newPage = $this->cmsPageFactory->create();
        $newPage->setData($pageModelData);

        try {
            $this->logging->info(
                sprintf(
                    'Saving page with identifier: %s and title: %s for stores: %s',
                    $newPage->getIdentifier(),
                    $newPage->getTitle(),
                    implode(
                        ', ',
                        $storeIds
                    )
                )
            );

            $this->cmsPageResourceFactory->create()->save($newPage);

            return true;
        } catch (Exception $exception) {
            $this->logging->error(
                sprintf(
                    'Could not save page with identifier: %s because: %s',
                    $pageIdentifier,
                    $exception->getMessage()
                )
            );
            $this->logging->error($exception);

            return false;
        }
    }

    /**
     * Imports the CMS blocks of an XML file (path absolute or relative to the Magento root).
     *
     * @param string $xmlFileName
     * @param bool $overwriteExisting
     *
     * @return string[] the identifiers of the blocks that were not imported
     * @throws Exception
     */
    public function importBlocksFromXmlFile(string $xmlFileName, bool $overwriteExisting = false): array
    {
        $fileName = $this->files->determineFilePath($xmlFileName);

        $xmlElement = $this->loadXmlFile($fileName);

        return $this->importBlocksFromXml(
            $xmlElement,
            $overwriteExisting
        );
    }

    /**
     * Imports the CMS blocks of an XML document.
     *
     * @param SimpleXMLElement $xml
     * @param bool $overwriteExisting
     *
     * @return string[] the identifiers of the blocks that were not imported
     * @throws Exception
     */
    public function importBlocksFromXml(SimpleXMLElement $xml, bool $overwriteExisting = false): array
    {
        $notImported = [];

        // <root><blocks><cms_block/><cms_block/></blocks></root>
        foreach ($xml->children()->children() as $blockXml) {
            $imported = $this->importBlockFromXml(
                $blockXml,
                $overwriteExisting
            );

            if (! $imported) {
                $notImported[] = (string)$blockXml->{static::STRING_IDENTIFIER};
            }
        }

        return $notImported;
    }

    /**
     * Imports a CMS block from its XML element.
     *
     * @param SimpleXMLElement $blockXml
     * @param bool $overwriteExisting
     *
     * @return bool
     * @throws Exception
     */
    public function importBlockFromXml(SimpleXMLElement $blockXml, bool $overwriteExisting): bool
    {
        $blockData = $this->simpleXml->xmlToArray($blockXml);

        return $this->importBlock(
            $blockData,
            $overwriteExisting
        );
    }

    /**
     * Imports a CMS block.
     *
     * Existing blocks with the identifier in the stores are replaced if $overwriteExisting is true.
     *
     * @param array $blockData
     * @param bool $overwriteExisting
     *
     * @phpstan-param array<string, mixed> $blockData
     *
     * @return bool true if the block was saved
     * @throws LocalizedException
     */
    public function importBlock(array $blockData, bool $overwriteExisting = false): bool
    {
        $blockIdentifier = $this->getStringValue(
            $blockData,
            static::STRING_IDENTIFIER
        );

        $stores = $this->arrays->getValue(
            $blockData,
            'stores:item',
            []
        );

        if ($this->variables->isEmpty($stores)) {
            $storeIds = [0];
        } else {
            $storeIds = is_array($stores) ? $stores : [$stores];
        }

        $oldBlocks = $this->cmsBlockCollectionFactory->create();

        $oldBlocks->addFieldToFilter(
            static::STRING_IDENTIFIER,
            $blockIdentifier
        );
        $oldBlocks->addStoreFilter($storeIds);

        $oldBlocks->load();

        if (count($oldBlocks) > 0 && ! $overwriteExisting) {
            // block already exists and we are not allowed to overwrite
            $this->logging->info(
                sprintf(
                    'Skipping existing block with identifier: %s',
                    $blockIdentifier
                )
            );

            return false;
        }

        /** @var Block $oldBlock */
        foreach ($oldBlocks as $oldBlock) {
            try {
                $this->cmsBlockResourceFactory->create()->delete($oldBlock);

                $this->logging->info(
                    sprintf(
                        'Removing block(s) with identifier: %s and title: %s for stores: %s',
                        $oldBlock->getIdentifier(),
                        $oldBlock->getTitle(),
                        implode(
                            ', ',
                            $storeIds
                        )
                    )
                );
            } catch (Exception $exception) {
                $this->logging->error($exception);
            }
        }

        $blockModelData = [
            'title'      => $this->getStringValue(
                $blockData,
                'title'
            ),
            'identifier' => $blockIdentifier,
            'content'    => $this->getStringValue(
                $blockData,
                'content'
            ),
            'is_active'  => $this->variables->intValue(
                $this->arrays->getValue(
                    $blockData,
                    'is_active',
                    true
                )
            ),
            'stores'     => $storeIds
        ];

        $newBlock = $this->cmsBlockFactory->create();
        $newBlock->setData($blockModelData);

        try {
            $this->logging->info(
                sprintf(
                    'Saving block with identifier: %s and title: %s for stores: %s',
                    $newBlock->getIdentifier(),
                    $newBlock->getTitle(),
                    implode(
                        ', ',
                        $storeIds
                    )
                )
            );

            $this->cmsBlockResourceFactory->create()->save($newBlock);

            return true;
        } catch (Exception $exception) {
            $this->logging->error(
                sprintf(
                    'Could not save block with identifier: %s because: %s',
                    $blockIdentifier,
                    $exception->getMessage()
                )
            );
            $this->logging->error($exception);

            return false;
        }
    }

    /**
     * Loads an XML file.
     *
     * @param string $fileName
     *
     * @return SimpleXMLElement
     * @throws Exception
     */
    private function loadXmlFile(string $fileName): SimpleXMLElement
    {
        $xmlElement = $this->simpleXml->simpleXmlLoadFile($fileName);

        if ($xmlElement === false) {
            throw new RuntimeException(sprintf('Could not load XML file: %s', $fileName));
        }

        return $xmlElement;
    }

    /**
     * Returns a value of the data as string, or an empty string if the value is no scalar.
     *
     * @param array $data
     * @param string $key
     *
     * @phpstan-param array<string, mixed> $data
     *
     * @return string
     */
    private function getStringValue(array $data, string $key): string
    {
        $value = $this->arrays->getValue(
            $data,
            $key
        );

        return is_scalar($value) ? (string)$value : '';
    }
}
