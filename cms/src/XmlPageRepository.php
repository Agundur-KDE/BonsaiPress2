<?php

declare(strict_types=1);

namespace BonsaiPress;

use SimpleXMLElement;

class XmlPageRepository implements PageRepository
{
    /** @var Page[] */
    private array $tree = [];

    /** @var array<int, Page> */
    private array $byId = [];

    /** @var array<string, Page> */
    private array $byPath = [];

    public function __construct(string $xmlPath)
    {
        $xml = simplexml_load_file($xmlPath);
        if ($xml === false) {
            throw new \RuntimeException("Cannot parse site_structure.xml: $xmlPath");
        }
        $this->tree = $this->parseLevel($xml, '/');
    }

    public function getTree(): array
    {
        return $this->tree;
    }

    public function findById(int $id): ?Page
    {
        return $this->byId[$id] ?? null;
    }

    /** @return array<int, string>  all pages flat: id => path */
    public function getPathMap(): array
    {
        return array_map(fn(Page $p) => $p->path, $this->byId);
    }

    /** @return Page[] */
    private function parseLevel(SimpleXMLElement $parent, string $parentPath): array
    {
        $pages = [];
        foreach ($parent->page as $node) {
            $page = $this->buildPage($node, $parentPath);
            $pages[] = $page;
            $this->register($page);
        }
        return $pages;
    }

    private function buildPage(SimpleXMLElement $node, string $parentPath): Page
    {
        $attrs   = $node->attributes();
        $id      = (int)(string)$attrs->id;
        $location = (string)$attrs->location;
        $isRoot  = isset($attrs->root);
        $hasChildren  = count($node->page) > 0;
        $forceChildren = isset($attrs->force_children);

        if ($isRoot) {
            $path           = '/';
            $childParentPath = '/';
        } elseif ($hasChildren || $forceChildren) {
            $path            = $parentPath . $location . '/';
            $childParentPath = $path;
        } else {
            $path            = $parentPath . $location;
            $childParentPath = $parentPath . $location . '/';
        }

        $children = $this->parseLevel($node, $childParentPath);

        return new Page(
            id:          $id,
            title:       (string)$attrs->title,
            location:    $location,
            contentType: (string)($attrs->content_type ?? ''),
            titleDesc:   (string)($attrs->titledesc ?? ''),
            isRoot:      $isRoot,
            path:        $path,
            children:    $children,
            addons:      $this->parseAddons($node),
        );
    }

    /** @return Addon[] */
    private function parseAddons(SimpleXMLElement $page): array
    {
        $addons = [];
        foreach ($page->addon as $node) {
            $attrs = $node->attributes();
            if (isset($attrs->addon)) {
                // <addon addon="name" placeholder="PH"> with optional <config> children
                $config = [];
                if (isset($node->config)) {
                    foreach ($node->config->children() as $key => $val) {
                        $config[$key] = (string)$val;
                    }
                }
                $addons[] = new Addon(
                    name:        (string)$attrs->addon,
                    placeholder: (string)($attrs->placeholder ?? ''),
                    config:      $config,
                );
            } else {
                // legacy: <addon>name::PLACEHOLDER::param</addon>
                $addons[] = Addon::fromString((string)$node);
            }
        }
        return $addons;
    }

    private function register(Page $page): void
    {
        $this->byId[$page->id]     = $page;
        $this->byPath[$page->path] = $page;
        foreach ($page->children as $child) {
            $this->register($child);
        }
    }
}
