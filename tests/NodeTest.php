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

    function testWrap() {
        $node = new node(id: "joe", children: "hey");
        $wrapped = $node->wrap(new node(id: "hey"));
        $this->assertSame('<div id="hey"><div id="joe">hey</div></div>', (string) $wrapped->parent());
    }

    function testEscape() {
        $node = new node(children: "<h1>ok</h1>");
        $this->assertSame('<div>&lt;h1&gt;ok&lt;/h1&gt;</div>', (string) $node);
        $node->raw_content('<h1>ok</h1>');
        $this->assertSame('<div><h1>ok</h1></div>', (string) $node);
    }

    function testAttrs() {
        $node = new node('h1', attrs: ['class' => 'red  red ', 'id' => 'first', 'toggle' => true]);
        $this->assertSame('<h1 id="first" class="red" toggle></h1>', (string) $node);
        $this->assertSame('first', $node->id);
        $this->assertSame(['red'], $node->class);
    }
}
