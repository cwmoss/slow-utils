<?php

declare(strict_types=1);

error_reporting(\E_ALL);

use PHPUnit\Framework\TestCase;
use slow\util\text;

final class TextparserTest extends TestCase {

    function testWords() {
        $res = text::words(' eins zwo   drei');
        $this->assertEquals(['eins', 'zwo', 'drei'], $res);

        $res = text::words(' eins |zwo |  drei', '|');
        $this->assertEquals(['eins', 'zwo', 'drei'], $res);

        $res = text::words(' eins ,,zwo ,  drei', ',');
        $this->assertEquals(['eins', 'zwo', 'drei'], $res);
    }

    function testSwitches() {
        $res = text::switches(' index -executable +full-output');
        $this->assertEquals(['index' => true, 'executable' => false, 'full-output' => true], $res);
    }

    function testTextReplace() {
        $res = text::text_replace(' {label} has value: ${value}', ['label' => 'price', 'value' => '4']);
        $this->assertEquals(' price has value: $4', $res);

        $res = text::text_replace(' {label} has value: ${value} ({value})', ['label' => 'price', 'value' => '4']);
        $this->assertEquals(' price has value: $4 (4)', $res);
    }

    function testTextFor() {
        $res = text::text_for(' {label} has value: ${value}', ['label' => 'price', 'value' => '4']);
        $this->assertEquals(' price has value: $4', $res);

        $res = text::text_for(' {label} has value: ${value} ({value})', ['label' => 'price', 'value' => '4']);
        $this->assertEquals(' price has value: $4 (4)', $res);

        $res = text::text_for(' {label| strtoupper} has value: ${value}', ['label' => 'price', 'value' => '4']);
        $this->assertEquals(' PRICE has value: $4', $res);

        $res = text::text_for(' {label| strtoupper |strrev} has value: ${value}', ['label' => 'beil', 'value' => '4']);
        $this->assertEquals(' LIEB has value: $4', $res);

        $res = text::text_for(' {label| strtoupper |strrev} has value: {value|%01.2f} €', ['label' => 'beil', 'value' => '4']);
        $this->assertEquals(' LIEB has value: 4.00 €', $res);

        $nice = function ($d) {
            $date = new \DateTime($d);
            return $date->format('j.n.Y');
        };
        $res = text::text_for(
            'today is the {date| nice_date}, hurra! ({date})',
            ['date' => '1989-11-09'],
            ['nice_date' => $nice]
        );
        $this->assertEquals('today is the 9.11.1989, hurra! (1989-11-09)', $res);
    }

    function testArrayToSections() {
        $t = [' ', '# start', '== about', 'About Us', '=='];
        $res = text::split_array_to_sections($t);
        $this->assertEquals(['about' => ['About Us']], $res);

        $t = [' ', '# start', '== about', 'About Us', '==', 'Fin'];
        $res = text::split_array_to_sections($t);
        $this->assertEquals(['about' => ['About Us'], '__undefined__' => ['Fin']], $res);

        $t = [' ', '# start', '== about', 'About Us', '', 'some words', '  # another comment', '==', 'Fin'];
        $res = text::split_array_to_sections($t);
        $this->assertEquals(['about' => ['About Us', 'some words'], '__undefined__' => ['Fin']], $res);

        $t = [' ', '# start', '==  ', 'Lets start',  '== about', 'About Us', '==', 'Fin'];
        $res = text::split_array_to_sections($t);
        $this->assertEquals(['about' => ['About Us'], '__undefined__' => ['Lets start', 'Fin']], $res);
    }

    function testBoundary() {
        $t = '
        
        @@@hallo@@@
         welt';
        $parts = text::split_boundary($t);
        $this->assertSame('hallo', key($parts));
        $this->assertStringContainsString('welt', $parts['hallo']);
        $t = '
        
        @@@   hallo      @@@
         welt';
        $parts = text::split_boundary($t);
        $this->assertSame('hallo', key($parts));
        $this->assertStringContainsString('welt', $parts['hallo']);

        $t = '@@@   hallo      @@@
         welt
         
         ';
        $parts = text::split_boundary($t);
        $this->assertSame('hallo', key($parts));
        $this->assertStringContainsString('welt', $parts['hallo']);

        $t = '
        
        @@@   hallo      @@@
         welt
         
         ';
        $parts = text::split_boundary($t);
        $this->assertSame('hallo', key($parts));
        $this->assertStringContainsString('welt', $parts['hallo']);

        $t = '
        
        🐧   hallo      🐧
         welt
         
         ';
        $parts = text::split_boundary($t, '🐧');
        $this->assertSame('hallo', key($parts));
        $this->assertStringContainsString('welt', $parts['hallo']);

        $t = 'ÖhalloÖ
         welt';
        $parts = text::split_boundary($t, 'Ö');
        $this->assertSame('hallo', key($parts));
        $this->assertStringContainsString('welt', $parts['hallo']);
    }
}
