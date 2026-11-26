<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Helper;

use Infrangible\Core\Helper\Layer;
use Magento\Catalog\Model\Layer as CatalogLayer;
use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Layer\State;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests the helper of the layered navigation.
 */
#[AllowMockObjectsWithoutExpectations]
class LayerTest extends TestCase
{
    /**
     * Returns a layer with the given applied filters.
     *
     * @param array $filters
     * @phpstan-param array<int, mixed> $filters
     *
     * @return CatalogLayer
     */
    private function createLayer(array $filters): CatalogLayer
    {
        $state = $this->createStub(State::class);
        $state->method('getFilters')->willReturn($filters);

        $layer = $this->createStub(CatalogLayer::class);
        $layer->method('getState')->willReturn($state);

        return $layer;
    }

    /**
     * The applied filters of the current layer are returned, values that are no filter items are left out.
     *
     * @return void
     */
    public function testActiveFiltersOfCurrentLayer(): void
    {
        $first = $this->createStub(Item::class);
        $second = $this->createStub(Item::class);

        $resolver = $this->createStub(Resolver::class);
        $resolver->method('get')->willReturn($this->createLayer([$first, 'not an item', $second]));

        $this->assertSame([$first, $second], (new Layer($resolver))->getActiveFilters());
    }

    /**
     * The applied filters of a given layer are returned without asking the resolver.
     *
     * @return void
     */
    public function testActiveFiltersOfGivenLayer(): void
    {
        $item = $this->createStub(Item::class);

        $resolver = $this->createMock(Resolver::class);
        $resolver->expects($this->never())->method('get');

        $this->assertSame([$item], (new Layer($resolver))->getActiveFilters($this->createLayer([$item])));
    }

    /**
     * A layer without applied filters returns an empty list.
     *
     * @return void
     */
    public function testNoActiveFilters(): void
    {
        $resolver = $this->createStub(Resolver::class);
        $resolver->method('get')->willReturn($this->createLayer([]));

        $this->assertSame([], (new Layer($resolver))->getActiveFilters());
    }
}
