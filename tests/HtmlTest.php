<?php

declare(strict_types=1);

error_reporting(\E_ALL);

use PHPUnit\Framework\TestCase;
use slow\util\html\tag;

final class HtmlTest extends TestCase {

    function testTag() {
        $c = 0;
        $tag = (string) tag::new('.otto', '<h1>huhu</h1>')
            ->before('li', 'one')
            ->before('li', 'two')
            ->each('before', function ($e) use (&$c) {
                $e->class_add('odd', (++$c % 2))->class_add("even", $c % 2 == 0);
            })
            ->class_add("walkes")
            ->wrap("main #main > .center");

        $html = '<main id="main"><div class="center"><li class="odd">one</li><li class="even">two</li><div class="otto walkes"><h1>huhu</h1></div></div></main>';

        $this->assertSame($html, $tag);
    }
}
