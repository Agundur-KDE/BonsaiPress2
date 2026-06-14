<?php

    declare(strict_types=1);

    namespace BonsaiPress\Tests;

    use PHPUnit\Framework\TestCase;

    use BonsaiPress\Section;

    class SectionTest extends TestCase

    {

        public function testReadAndFetch(): void
        {
            $section = new Section();
            $section->read('tests/fixtures/sections.html');
            $this->assertEquals('SECTION-TEST', $section->fetch('TEST'));
        }

        public function testReadFileWithoutSectionsThrowsException(): void
        {
            $this->expectException(\RuntimeException::class);
            $section = new Section();
            $section->read('tests/fixtures/simple.html');
        }

        public function testSectionDoesExist(): void
        {

            $section = new Section();
            $section->read('tests/fixtures/sections.html');
            $this->assertTrue($section->section_exists('TEST'));
            $this->assertFalse($section->section_exists('DOESNOTEXIST'));

        }
        public function testReturnListOfSections(): void
        {

            $section = new Section();
            $section->read('tests/fixtures/sections.html');
            $this->assertEquals(['TEST'], $section->get_sections());

        }
    }