<?php

namespace slow\util\html;

class soup {

    public function __construct(
        public object $model,
        public string $basename = "%s"
    ) {
    }

    public function validator(array|string $fields): tag {
        [$rules, $messages] = $this->model->validatable_get_validator()->js_component_rules($fields, $this->basename);
        return new tag('bob-validator', data: ['rules' => $rules, 'messages' => $messages]);
    }
    public function validator_close() {
        return '</bob-validator>';
    }

    public function form_field(string $name, string $type = "text", $value = null, string $error = ""): tag {
        $fname = sprintf($this->basename, $name);
        $modelvalue = (string) $this->model->$name;
        $id = $type == 'radio' ? self::id($fname, $value) : self::id($fname);

        [$tagname, $type] = match ($type) {
            'select', 'textarea' => [$type, null],
            default => ['input', $type]
        };
        $tag = new tag($tagname, $id, attrs: ['name' => $fname, 'value' => $modelvalue]);

        if ($type) $tag->attr("type", $type);
        if ($type == 'checkbox') {
            $hidden = new tag('input', attrs: ['type' => 'hidden', 'name' => $fname, 'value' => "0"]);
            $tag->attr("value", $value)->before($hidden);
            $tag->attr("checked", $modelvalue == $value);
        }
        if ($type == 'radio') {
            $tag->attr("value", $value);
            $tag->attr("checked", $modelvalue == $value);
        }
        return $tag;
    }

    public function input(string $name, string $label, string $type = "text", $value = null): forminput {
        $err = (string) $this->model->errors?->string_on($name);
        $field = $this->form_field($name, $type, $value);
        $inp = new forminput($field, $label, $err);
        return $inp;
    }

    public function selectbox(string $name, string $label, array $items, array $opts = []): forminput {
        $err = (string) $this->model->errors?->string_on($name);
        $field = $this->form_field($name, 'select')
            ->content(self::options_for_select($items, (string) $this->model->$name, $opts));

        $inp = new forminput($field, $label, $err);
        return $inp;
    }

    public function radios(string $name, array $items): formgroup {
        $err = $this->model->errors?->string_on($name);
        $group = new formgroup((string) $err);

        foreach ($items as $value => $label) {
            $field = $this->form_field($name, 'radio', $value);
            $inp = new forminput($field, $label, false);
            $group->add_item($inp);
        }
        return $group;
    }

    public static function id(string $name, ?string $value = null): string {
        $name = str_replace(['[', ']'], ['-', ''], $name);
        if ($value !== null) $name .= '-' . $value;
        return $name;
    }

    public static function words(string $line, string $delimiter = ' '): array {
        $words = array_filter(explode($delimiter, $line), 'trim');
        // TODO: if delimiter is not a white space char
        if ($delimiter !== ' ') {
            $words = array_map('trim', $words);
        }
        return array_values($words);
    }

    public static function options_for_select(array $items, $value = [], array $opts = []): string {
        $html = "";
        $opts['compare_strings'] = (isset($opts['compare_strings']) && $opts['compare_strings']) ? true : false;
        if (!is_array($value)) $value = array($value);
        if (isset($opts['nullentry'])) $html .= sprintf('<option value="">%s</option>' . "\n", $opts['nullentry']);

        if (isset($opts['group'])) {
            foreach ($items as $group => $groupitems) {
                $html .= sprintf('<optgroup %s>', self::opts_to_html(array("label" => $group)));
                $html .= self::options_list($groupitems, $value, $opts);
                $html .= "</optgroup>";
            }
        } else {
            $html .= self::options_list($items, $value, $opts);
        }
        return $html;
    }

    public static function options_list(array $items, $value, array $opts = []): string {
        $html = "";
        if (isset($opts['nohash'])) {
            foreach ($items as $v) {
                $hopts = array();
                if (is_array($v)) {
                    $hopts['class'] = $v[1];
                    $v = $v[0];
                }
                if (in_array($v, $value)) {
                    $hopts['selected'] = 'selected';
                }
                $html .= sprintf('<option%s>%s</option>' . "\n", self::opts_to_html($hopts), $v);
            }
        } else {
            foreach ($items as $k => $v) {
                $hopts = array();
                if (is_array($v)) {
                    $hopts['class'] = $v[1];
                    $v = $v[0];
                }

                if ($opts['compare_strings']) $k = (string) $k;
                if (in_array($k, $value)) {
                    //   if(isset($vkeys[$k])){   
                    $hopts['selected'] = 'selected';
                }
                $hopts['value'] = (string)$k;
                $html .= sprintf('<option%s>%s</option>' . "\n", self::opts_to_html($hopts), $v);
            }
        }
        return $html;
    }

    public static function opts_to_html(array $opts): string {
        $html = "";
        foreach ($opts as $k => $v) {
            $v = trim($v ?: "");
            if (!$v && !($v === 0 || $v == "0")) continue;
            $html .= ' ' . $k . '="' . htmlspecialchars($v) . '"';
        }
        return $html;
    }
}
