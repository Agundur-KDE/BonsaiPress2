<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\XmlPageRepository;
use PHPUnit\Framework\TestCase;

class XmlPageRepositoryTest extends TestCase
{
    private XmlPageRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new XmlPageRepository(__DIR__ . '/fixtures/site_structure.xml');
    }

    // --- tree structure ---

    public function testTreeHasThreeRootLevelPages(): void
    {
        $this->assertCount(4, $this->repo->getTree());
    }

    public function testRootPageIsFirst(): void
    {
        $root = $this->repo->getTree()[0];
        $this->assertSame(1, $root->id);
        $this->assertTrue($root->isRoot);
    }

    public function testDailiesHasTwoChildren(): void
    {
        $dailies = $this->repo->findById(10);
        $this->assertCount(2, $dailies->children);
    }

    // --- URL paths ---

    public function testRootPagePathIsSlash(): void
    {
        $this->assertSame('/', $this->repo->findById(1)->path);
    }

    public function testLeafPagePathHasNoTrailingSlash(): void
    {
        $this->assertSame('/pearfinds', $this->repo->findById(2)->path);
    }

    public function testParentPagePathHasTrailingSlash(): void
    {
        $this->assertSame('/dailies/', $this->repo->findById(10)->path);
    }

    public function testChildPagePathIncludesParent(): void
    {
        $this->assertSame('/dailies/haidir', $this->repo->findById(11)->path);
    }

    public function testForceChildrenAddsTrailingSlash(): void
    {
        $this->assertSame('/archive/', $this->repo->findById(20)->path);
    }

    // --- lookup ---

    public function testFindByIdReturnsNull(): void
    {
        $this->assertNull($this->repo->findById(999));
    }

    // --- path map ---

    public function testGetPathMapContainsAllPages(): void
    {
        $map = $this->repo->getPathMap();
        // fixture has 7 pages: 1, 10, 11, 12, 2, 20  → but wait, let me count: 1,2,10,11,12,20 = 6
        $this->assertCount(6, $map);
    }

    public function testGetPathMapRootIsSlash(): void
    {
        $map = $this->repo->getPathMap();
        $this->assertSame('/', $map[1]);
    }

    public function testGetPathMapContainsDeepChildPath(): void
    {
        $map = $this->repo->getPathMap();
        $this->assertSame('/dailies/haidir', $map[11]);
    }

    public function testGetPathMapContainsParentWithTrailingSlash(): void
    {
        $map = $this->repo->getPathMap();
        $this->assertSame('/dailies/', $map[10]);
    }

    // --- addons ---

    public function testRootPageHasFourAddons(): void
    {
        $this->assertCount(4, $this->repo->findById(1)->addons);
    }

    public function testAddonWithAttributesParsed(): void
    {
        $addon = $this->repo->findById(1)->addons[0];
        $this->assertSame('bs4_6menu', $addon->name);
        $this->assertSame('MAINMENU', $addon->placeholder);
    }

    public function testAddonWithConfigParsed(): void
    {
        $addon = $this->repo->findById(1)->addons[1];
        $this->assertSame('inlinejs', $addon->name);
        $this->assertSame('main', $addon->config['scripts']);
    }

    public function testLegacyAddonStringParsed(): void
    {
        $addon = $this->repo->findById(1)->addons[2];
        $this->assertSame('canonical', $addon->name);
        $this->assertSame('CANONICAL', $addon->placeholder);
        $this->assertSame(['https'], $addon->config);
    }
}
