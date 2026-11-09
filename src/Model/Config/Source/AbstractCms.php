<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Model\Config\Source;

use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Options of CMS entities (blocks, pages) with their title as label, sorted by title.
 */
abstract class AbstractCms implements OptionSourceInterface
{
    /**
     * Returns a new collection of the CMS entities.
     *
     * @return AbstractDb
     */
    abstract protected function createCollection(): AbstractDb;

    /**
     * Returns all options of the source.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function getAllOptions(): array
    {
        return $this->toOptionArray();
    }

    /**
     * Returns the options for a select field.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('--Please Select--')]];

        foreach ($this->getEntities() as $entity) {
            $options[] = [
                'value' => $entity->getId(),
                'label' => $entity->getData('title')
            ];
        }

        return $options;
    }

    /**
     * Returns the options as value => label.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function toOptions(): array
    {
        $options = [];

        foreach ($this->getEntities() as $entity) {
            $entityId = $entity->getId();

            if (is_int($entityId) || is_string($entityId)) {
                $options[ $entityId ] = $entity->getData('title');
            }
        }

        return $options;
    }

    /**
     * Returns the CMS entities sorted by title.
     *
     * @return AbstractModel[]
     */
    private function getEntities(): array
    {
        $collection = $this->createCollection();

        $collection->addOrder('title', Collection::SORT_ORDER_ASC);

        $entities = [];

        foreach ($collection as $entity) {
            if ($entity instanceof AbstractModel) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }
}
