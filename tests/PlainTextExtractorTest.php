<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\PlainTextExtractor;
use PHPUnit\Framework\TestCase;

class PlainTextExtractorTest extends TestCase
{
    public function testStripsHtmlTags(): void
    {
        // Contenfiles sind wie im echten CMS mehrzeilig formatiert —
        // Zeilenumbrüche zwischen Tags bleiben nach strip_tags() erhalten.
        $html = "<h1>Titel</h1>\n<p>Ein <strong>Text</strong>.</p>";
        $this->assertSame("Titel\nEin Text.", PlainTextExtractor::extract($html));
    }

    public function testDecodesHtmlEntities(): void
    {
        $html = '<p>Gr&uuml;&szlig;e &amp; Gr&uuml;sse</p>';
        $this->assertSame('Grüße & Grüsse', PlainTextExtractor::extract($html));
    }

    public function testCollapsesExcessiveWhitespace(): void
    {
        $html = "<p>Zeile eins</p>\n\n\n\n   <p>Zeile zwei</p>\n\n\n";
        $this->assertSame("Zeile eins\nZeile zwei", PlainTextExtractor::extract($html));
    }

    public function testKeepsTextFromPreCodeBlocks(): void
    {
        $html = '<pre class="bg-dark"><code>bonsai static
bonsai deploy</code></pre>';
        $this->assertSame("bonsai static\nbonsai deploy", PlainTextExtractor::extract($html));
    }

    public function testDropsImagesWithoutLeavingArtifacts(): void
    {
        $html = "<p>Vorher</p>\n<img src=\"x.png\" alt=\"Beschreibung\">\n<p>Nachher</p>";
        $this->assertSame("Vorher\nNachher", PlainTextExtractor::extract($html));
    }
}
