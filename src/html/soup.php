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
            ->content(options::for_select($items, (string) $this->model->$name, $opts));

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
}
