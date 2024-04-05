<?php

namespace slow\util;

use slow\util\text;

class html {

    public static function h($attr) {
        return htmlspecialchars($attr);
    }

    public static function tag(string $name, array $attrs, $close = false) {
        $attrs = array_reduce($attrs, function ($res, $item) {
            if (is_array($item)) {
                $item = sprintf('%s="%s"', $item[0], self::h($item[1]));
            }
            return $res . " " . $item;
        }, "");
        return sprintf('<%s%s></%s>', $name, $attrs, $name);
    }

    public static function script_tag($src, $attrs = "", $cb = null) {
        if ($cb == 'ts') $src .= '?ts=' . \time();
        $attrs = text::words($attrs);
        $attrs = array_reduce($attrs, function ($res, $attr) {
            if ($attr == 'module') $attr = ['type', $attr];
            $res[] = $attr;
            return $res;
        }, [['src', $src]]);
        return self::tag('script', $attrs, true);
    }

    public static function style_tag($src, $cb = null) {
        if ($cb == 'ts') $src .= '?ts=' . \time();
        return self::tag('link', [['rel', 'stylesheet'], ['href', $src]]);
    }
}
