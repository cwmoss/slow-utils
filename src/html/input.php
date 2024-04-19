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

    public static function selectbox(string $name, array $options = [], string $current_value, array $attrs = [], array $options_opts = []) {
        $attrs = ['name' => $name] + $attrs;
        $tag = new node('select', attrs: $attrs);
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

    public static function form_field(string $name, string $type = "text", $value = null, ?string $id = null): tag {
        $fname = sprintf($this->basename, $name);
        $modelvalue = (string) $this->model->$name ?? "";
        if (!$id)
            $id = $type == 'radio' ? self::id($fname, $value) : self::id($fname);

        $tag = match ($type) {
            'select' => new tag($type, $id, attrs: ['name' => $fname]),
            'textarea' => new tag($type, $id, attrs: ['name' => $fname], content: $modelvalue),
            'radio', 'checkbox' => (new tag('input', $id, attrs: ['name' => $fname, 'type' => $type, 'value' => $value]))
                ->attr("checked", $modelvalue == $value),
            default => new tag('input', $id, attrs: ['name' => $fname, 'type' => $type, 'value' => $modelvalue])
        };

        if ($type == 'checkbox') {
            $hidden = new tag('input', attrs: ['type' => 'hidden', 'name' => $fname, 'value' => "0"]);
            $tag->before($hidden);
        }

        return $tag;
    }

    public function xxform_field(string $name, string $type = "text", $value = null, ?string $id = null): tag {
        $fname = sprintf($this->basename, $name);
        $modelvalue = (string) $this->model->$name ?? "";
        if (!$id)
            $id = $type == 'radio' ? self::id($fname, $value) : self::id($fname);

        $tag = match ($type) {
            'select' => new tag($type, $id, attrs: ['name' => $fname]),
            'textarea' => new tag($type, $id, attrs: ['name' => $fname], content: $modelvalue),
            'radio', 'checkbox' => (new tag('input', $id, attrs: ['name' => $fname, 'type' => $type, 'value' => $value]))
                ->attr("checked", $modelvalue == $value),
            default => new tag('input', $id, attrs: ['name' => $fname, 'type' => $type, 'value' => $modelvalue])
        };

        if ($type == 'checkbox') {
            $hidden = new tag('input', attrs: ['type' => 'hidden', 'name' => $fname, 'value' => "0"]);
            $tag->before($hidden);
        }

        return $tag;
    }
}
