<?php

declare(strict_types=1);

error_reporting(\E_ALL);

use PHPUnit\Framework\TestCase;
use slow\util\template\php;

final class PhptemplateTest extends TestCase {

    function testSimple() {
        $tpl = new php(__DIR__ . '/data');
        $res = $tpl->render('simple', ['name' => 'world']);
        // $this->assertXmlStringEqualsXmlString('<div>hello world</div>', $res);
        $this->compare_html('<div>hello world</div>', $res);

        // again, multiple times should work too
        $res = $tpl->render('simple', ['name' => 'world']);
        $this->compare_html('<div>hello world</div>', $res);
    }

    function testLayout() {
        $tpl = new php(__DIR__ . '/data');
        $res = $tpl->render('with-layout', ['name' => 'world']);
        $this->compare_html('<body><div>hello world</div></body>', $res);

        // again, multiple times should work too
        $res = $tpl->render('with-layout', ['name' => 'world']);
        $this->compare_html('<body><div>hello world</div></body>', $res);
    }

    function testStack() {
        $tpl = new php(__DIR__ . '/data');
        $res = $tpl->render('with-stack', ['name' => 'world']);
        #dd($res);
        $this->compare_html('<body><div>/app.css</div><div>hello world</div></body>', $res);

        // again, multiple times should work too
        $res = $tpl->render('with-stack', ['name' => 'world']);
        $this->compare_html('<body><div>/app.css</div><div>hello world</div></body>', $res);
    }

    function testPartial() {
        $tpl = new php(__DIR__ . '/data');
        $res = $tpl->render('with-partial', ['name' => 'world']);
        $this->compare_html('<body><div>/app.css</div><pre>another</pre><div>hello world</div></body>', $res);

        $res = $tpl->render('with-partial', ['name' => 'world', 'list' => true, 'item' => 'horror']);
        $this->compare_html('<body><div>/app.css</div><pre>another</pre><div>hello world</div><ul><li>horror</li></ul></body>', $res);
    }

    function compare_html($expected, $actual) {
        $this->assertXmlStringEqualsXmlString($this->normalize_html($expected), $this->normalize_html($actual));
    }

    function normalize_html($input) {
        $input = str_replace("\n", "", $input);
        $input = preg_replace('!\s\s+!', ' ', $input);
        $dom = new DomDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadHTML($input);
        return $dom->saveHTML();
    }
}
