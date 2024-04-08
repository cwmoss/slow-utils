<?php

declare(strict_types=1);

error_reporting(\E_ALL);

use PHPUnit\Framework\TestCase;
use slow\util\html\node;

final class NodeTest extends TestCase {

    function xxtestNode() {
        $c = 0;
        $tag = (string) node::new('.otto>h1{huhu}}')
            ->insert_before(node::new('li', 'one'))
            ->insert_before(node::new('li', 'two'))

            ->class_add("walkes")
            ->wrap("main #main > .center`center")
            // ->get('center')
            ->each(function ($e) use (&$c) {
                $e->class_add('odd', (++$c % 2))->class_add("even", $c % 2 == 0);
            });

        $html = '<main id="main"><div class="center"><li class="odd">one</li><li class="even">two</li><div class="otto walkes"><h1>huhu</h1></div></div></main>';

        $this->assertSame($html, $tag);
    }

    function testTemplate() {
        $tpl = node::new('form>input[:type=type][:disabled=is_disabled]')->template();
        $this->assertSame('<form><input type="email" disabled></form>', $tpl(['is_disabled' => true, 'type' => 'email']));
        $this->assertSame('<form><input type="email"></form>', $tpl(['is_disabled' => false, 'type' => 'email']));
        $this->assertSame('<form><input type=""></form>', $tpl(['is_disabled' => false]));
    }
}
