<?php

declare(strict_types=1);

error_reporting(\E_ALL);

use PHPUnit\Framework\TestCase;
use slow\util\template\tiny;

final class TinytemplateTest extends TestCase {

    function testAdhoc() {
        $tiny = new tiny();
        $res = $tiny('hello {name}!', ['name' => 'world']);
        $this->assertSame('hello world!', $res);

        $res = $tiny('hello {name}!', ['xname' => 'world']);
        $this->assertSame('hello !', $res);

        $res = $tiny('hello {if name}{name}!{/if name}', ['xname' => 'world']);
        $this->assertSame('hello ', $res);

        $res = $tiny('hello {if name}{name}!{/if name}', ['name' => 'world']);
        $this->assertSame('hello world!', $res);

        $res = $tiny('hello {not name}stranger!{/not name}', ['name' => 'world']);
        $this->assertSame('hello ', $res);

        $res = $tiny('hello {not name}stranger!{/not name}', ['xname' => 'world']);
        $this->assertSame('hello stranger!', $res);
    }

    function testEach() {
        $tiny = new tiny();
        $res = $tiny('say {each lines}{line}{/each lines}!', ['lines' => [['line' => 'hello'], ['line' => 'hello']]]);
        $this->assertSame('say hellohello!', $res);

        $res = $tiny('say {each lines}{@num}{line}{/each lines}!', ['lines' => [['line' => 'hello'], ['line' => 'hello']]]);
        $this->assertSame('say 1hello2hello!', $res);

        $res = $tiny('say {each lines}{@num}{line}{if @last}FIN{/if @last}{/each lines}!', ['lines' => [['line' => 'hello'], ['line' => 'hello']]]);
        $this->assertSame('say 1hello2helloFIN!', $res);

        $res = $tiny('say {each lines}{if @last}last: {/if @last}{not @last}{@num}: {/not @last}{line} {/each lines}!', ['lines' => [['line' => 'hello'], ['line' => 'hello']]]);
        $this->assertSame('say 1: hello last: hello !', $res);
    }
}
