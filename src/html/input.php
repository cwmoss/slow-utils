<?php

namespace slow\util\html;

/*

general attrs:

autocapitalize, autocomplete, autofocus, disabled, placeholder, readonly, required


*/

class input {


    public static function input(string $name, mixed $value, string $type = 'text', array $attrs = []) {
        $attrs = ['type' => $type, 'name' => $name, 'value' => $value] + $attrs;
        return new node('input', attrs: $attrs);
    }

    public static function hidden(string $name, mixed $value, array $attrs = []) {
        return self::input($name, $value, 'hidden', $attrs);
    }

    public static function checkbox(string $name, mixed $value, string $current_value, array $attrs = []) {
        $tag = self::input($name, $value, 'checkbox', $attrs)->attr("checked", $current_value == $value);
        return $tag;
    }

    public static function radio(string $name, mixed $value, string $current_value, array $attrs = []) {
        $tag = self::input($name, $value, 'radio', $attrs)->attr("checked", $current_value == $value);
        return new node('input', attrs: $attrs);
    }

    public static function selectbox(
        string $name,
        array $options = [],
        string $current_value,
        ?string $nullentry = null,
        array $options_opts = [],
        array $attrs = []
    ) {
        $attrs = ['name' => $name] + $attrs;
        $tag = new node('select', attrs: $attrs);
        if ($nullentry) {
            $options_opts['nullentry'] = $nullentry;
        }
        $tag->raw_content(options::for_select($options, $current_value, $options_opts));
        return $tag;
    }

    public static function textarea(string $name, string $current_value, ?string $size = null, array $attrs = []) {
        $attrs = ['name' => $name] + $attrs;
        $tag = new node('textarea', attrs: $attrs, children: $current_value);
        if ($size) {
            [$rows, $cols] = explode("x", $size);
            if ($rows) $tag->attr('rows', $rows);
            if ($cols) $tag->attr('cols', $cols);
        }
        return $tag;
    }
}
