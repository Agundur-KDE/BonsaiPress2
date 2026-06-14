<?php

    declare(strict_types=1);

    namespace BonsaiPress\Tests;

    use PHPUnit\Framework\TestCase;
    use BonsaiPress\Template;

    class TemplateTest extends TestCase
    {

        public function testRender(): void
        {
            $template = new Template();
            $template->read('tests/fixtures/simple.html');
            $this->assertEquals('<p>Hello World</p>', $template->render());
        }

        public function testReadNonExistentFileThrowsException(): void
        {
            $this->expectException(\RuntimeException::class);
            $template = new Template();
            $template->read('does_not_exist.html');
        }


        public function testAssign(): void
        {
            $template = new Template();
            $template->read('tests/fixtures/placeholder.html');
            $template->assign('GREETING', 'Hello World');
            $this->assertEquals('<p>Hello World</p>', $template->render());
        }

        public function testAdd(): void
        {
            $template = new Template();
            $template->read('tests/fixtures/placeholder.html');
            $template->assign('GREETING', 'Hello World');
            $this->assertEquals('<p>Hello World</p>', $template->render());
            $template->add('GREETING', ' and moon');
            $this->assertEquals('<p>Hello World and moon</p>', $template->render());
        }

        public function testKeepUnassigned(): void
        {
            $template = new Template();
            $template->keep_unassigned = true;
            $template->read('tests/fixtures/placeholder.html');
            $this->assertEquals('<p>{GREETING}</p>', $template->render());
        }

        public function testSameMultiread(): void
        {
            $template = new Template();
            $template->read('tests/fixtures/placeholder.html');
            $template->assign('GREETING', 'Hello World');
            $this->assertEquals('<p>Hello World</p>', $template->render());
            $template->read('tests/fixtures/placeholder.html');
            $this->assertEquals('<p>Hello World</p>', $template->render());

        }

        public function testDiffMultiread(): void
        {
            $template = new Template();
            $template->read('tests/fixtures/simple.html');
            $template->assign('GREETING', 'Hello World');
            $this->assertEquals('<p>Hello World</p>', $template->render());
            $template->read('tests/fixtures/simple2.html');
            $this->assertEquals('<p>Hello World</p><p>Hello moon</p>', $template->render());
        }

    }