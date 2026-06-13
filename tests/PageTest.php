<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\Page;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    public function testPageHoldsAttributes(): void
    {
        $page = new Page(
            id: 1,
            title: 'Eternal Lands',
            location: 'index',
            contentType: 'standard_html',
            titleDesc: 'A page about EL',
            isRoot: true,
        );

        $this->assertSame(1, $page->id);
        $this->assertSame('Eternal Lands', $page->title);
        $this->assertSame('index', $page->location);
        $this->assertTrue($page->isRoot);
    }

    public function testChildrenAreAccessible(): void
    {
        $child = new Page(id: 11, title: 'Haidir', location: 'haidir', contentType: 'standard_html');
        $parent = new Page(id: 10, title: 'Dailies', location: 'dailies', contentType: 'standard_html', children: [$child]);

        $this->assertCount(1, $parent->children);
        $this->assertSame('haidir', $parent->children[0]->location);
    }
}
