<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\Page;
use BonsaiPress\PageTreeIterator;
use BonsaiPress\XmlPageRepository;
use PHPUnit\Framework\TestCase;

class PageTreeIteratorTest extends TestCase
{
    private PageTreeIterator $iterator;

    protected function setUp(): void
    {
        $repo = new XmlPageRepository('tests/fixtures/site_structure.xml');
        $this->iterator = new PageTreeIterator($repo->getTree());
    }

    public function testIteratesTopLevelPages(): void
    {
        $ids = [];
        foreach ($this->iterator as $page) {
            $ids[] = $page->id;
        }
        $this->assertEquals([1, 10, 2, 20], $ids);
    }

    public function testHasChildrenReturnsTrueForParent(): void
    {
        $this->iterator->rewind();
        $this->iterator->next(); // page id=10 has children

        $this->assertTrue($this->iterator->hasChildren());
    }

    public function testHasChildrenReturnsFalseForLeaf(): void
    {
        $this->iterator->rewind(); // page id=1, no children

        $this->assertFalse($this->iterator->hasChildren());
    }

    public function testGetChildrenReturnsIteratorWithChildren(): void
    {
        $this->iterator->rewind();
        $this->iterator->next(); // page id=10

        $children = $this->iterator->getChildren();
        $ids = [];
        foreach ($children as $page) {
            $ids[] = $page->id;
        }
        $this->assertEquals([11, 12], $ids);
    }

    public function testRecursiveIteratorVisitsAllPages(): void
    {
        $rit = new \RecursiveIteratorIterator(
            $this->iterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $ids = [];
        foreach ($rit as $page) {
            $ids[] = $page->id;
        }
        $this->assertEquals([1, 10, 11, 12, 2, 20], $ids);
    }
}
