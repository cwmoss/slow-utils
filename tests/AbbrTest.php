<?php

declare(strict_types=1);

error_reporting(\E_ALL);

use PHPUnit\Framework\TestCase;
use slow\util\html\abbreviation_parser;

final class AbbrTest extends TestCase {

    function testElementIdClass() {
        $doc = abbreviation_parser::parse('#header');
        $this->assertSame('<div id="header"></div>', $doc->render());

        $doc = abbreviation_parser::parse('span#header');
        $this->assertSame('<span id="header"></span>', $doc->render());

        $doc = abbreviation_parser::parse('span #header');
        $this->assertSame('<span id="header"></span>', $doc->render());

        $doc = abbreviation_parser::parse('.head');
        $this->assertSame('<div class="head"></div>', $doc->render());

        $doc = abbreviation_parser::parse('span.head');
        $this->assertSame('<span class="head"></span>', $doc->render());

        $doc = abbreviation_parser::parse('span .head');
        $this->assertSame('<span class="head"></span>', $doc->render());

        $doc = abbreviation_parser::parse('span#header.head');
        $this->assertSame('<span id="header" class="head"></span>', $doc->render());

        $doc = abbreviation_parser::parse('span #header.head');
        $this->assertSame('<span id="header" class="head"></span>', $doc->render());

        $doc = abbreviation_parser::parse('span #header .head');
        $this->assertSame('<span id="header" class="head"></span>', $doc->render());
    }

    function testChild() {
        $doc = abbreviation_parser::parse('.head>h1');
        $this->assertSame('<div class="head"><h1></h1></div>', $doc->render());
        $doc = abbreviation_parser::parse('.head > h1');
        $this->assertSame('<div class="head"><h1></h1></div>', $doc->render());
    }

    function testUp() {
        $doc = abbreviation_parser::parse('div+div>p>span+em^code');
        $this->assertSame('<div></div><div><p><span></span><em></em></p><code></code></div>', $doc->render());
        $doc = abbreviation_parser::parse('div+div> p> span+em ^ code');
        $this->assertSame('<div></div><div><p><span></span><em></em></p><code></code></div>', $doc->render());

        $doc = abbreviation_parser::parse('div+div>p>span+em^^code');
        $this->assertSame('<div></div><div><p><span></span><em></em></p></div><code></code>', $doc->render());
        $doc = abbreviation_parser::parse('div+div>p>span+em^ ^ code');
        $this->assertSame('<div></div><div><p><span></span><em></em></p></div><code></code>', $doc->render());
    }

    function testAttr() {
        $doc = abbreviation_parser::parse('p[title=Hello]');
        $this->assertSame('<p title="Hello"></p>', $doc->render());
        $doc = abbreviation_parser::parse('p[ title=Hello]');
        $this->assertSame('<p title="Hello"></p>', $doc->render());
        $doc = abbreviation_parser::parse('a[title=Hello href=https://example.com]');
        $this->assertSame('<a title="Hello" href="https://example.com"></a>', $doc->render());
        $doc = abbreviation_parser::parse('a[title=Hello] [href=https://example.com]');
        $this->assertSame('<a title="Hello" href="https://example.com"></a>', $doc->render());
    }

    function testText() {
        $doc = abbreviation_parser::parse('{hello}');
        $this->assertSame('hello', $doc->render());
        $doc = abbreviation_parser::parse('{click }+a {here}+{ for details}');
        $this->assertSame('click <a>here</a> for details', $doc->render());
        $doc = abbreviation_parser::parse('p>{click }+a {here}+{ for details}');
        $this->assertSame('<p>click <a>here</a> for details</p>', $doc->render());
    }

    function testRefs() {
        $doc = abbreviation_parser::parse('.head>h1`h')
            ->get('h')->class_add('dim');
        $this->assertSame('<div class="head"><h1 class="dim"></h1></div>', $doc->render_doc());
        $doc = abbreviation_parser::parse('.head`h>h1')
            ->get('h')->class_add('dim');
        $this->assertSame('<div class="head dim"><h1></h1></div>', $doc->render_doc());
    }
}
