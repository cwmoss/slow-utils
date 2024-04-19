<?php

namespace slow\util\html;

class form {

    public array $remaining_errors = [];

    public function __construct(
        public object $model,
        public string $basename = "%s",
        public string $validator_element = "bob-validator"
    ) {
        $this->set_remaining_errors($model);
    }

    public function set_remaining_errors($model) {
        foreach ($model->errors->cols as $name => $errs) {
            $this->remaining_errors[$name] = array_unique($model->errors->on($name));
        }
        $base = $model->errors->base;
        if ($base) $$this->remaining_errors['_'] = array_unique($base);
    }

    public function get_and_burn_errors_on(string $fieldname = '_', string $separator = '<br>'): string {
        if (isset($this->remaining_errors[$fieldname])) {
            $err = join($separator, $this->remaining_errors[$fieldname]);
            unset($this->remaining_errors[$fieldname]);
            return $err;
        }
        return "";
    }

    public function get_remaining_errors(string $separator = '<br>'): string {
        return join($separator, $this->remaining_errors);
    }

    public function validator(array|string $fields): node {
        [$rules, $messages] = $this->model->validatable_get_validator()->js_component_rules($fields, $this->basename);
        return new node($this->validator_element, data: ['rules' => $rules, 'messages' => $messages]);
    }

    public function validator_close() {
        return "</{$this->validator_element}>";
    }

    public function base_attrs(string $name, array $attrs = [], ?string $extended_id = null): array {
        $fname = sprintf($this->basename, $name);
        $id = self::id($fname, $extended_id);
        return ['name' => $fname, 'id' => $id] + $attrs;
    }

    public function input(string $name, string $label, string $type = "text", array $attrs = []): form_item {
        $err = $this->get_and_burn_errors_on($name);
        $attrs = $this->base_attrs($name, $attrs);
        $field = input::input($attrs['name'], $this->model->$name, $type, $attrs);
        return new form_item($field, $label, $err);
    }

    public function text(string $name, string $label, ?string $size = null, array $attrs = []): form_item {
        $err = $this->get_and_burn_errors_on($name);
        $attrs = $this->base_attrs($name, $attrs);
        $field = input::textarea($attrs['name'], $this->model->$name, $size, $attrs);
        return new form_item($field, $label, $err);
    }

    public function checkbox(string $name, string $label, $value, $unchecked_value = "0", array $attrs = []): form_item {
        $err = $this->get_and_burn_errors_on($name);
        $attrs = $this->base_attrs($name, $attrs);
        $field = input::checkbox($attrs['name'], $value, $this->model->$name, $attrs);
        $field->insert_before(input::hidden($attrs['name'], $unchecked_value));
        return new form_item($field, $label, $err);
    }

    public function selectbox(string $name, string $label, array $items, array $attrs = [], array $options_opts = []): form_item {
        $err = $this->get_and_burn_errors_on($name);
        $attrs = $this->base_attrs($name, $attrs);

        $field = input::selectbox($attrs['name'], $items, $this->model->$name, $attrs, $options_opts);
        #$field = $this->form_field($name, 'select')
        #    ->content(options::for_select($items, (string) $this->model->$name, $opts));

        $inp = new form_item($field, $label, $err);
        return $inp;
    }

    public function radios(string $name, array $items, array $attrs = []): node {
        $err = $this->get_and_burn_errors_on($name);

        $group = new node;

        foreach ($items as $value => $label) {
            $attrs = $this->base_attrs($name, $attrs, $value);
            $field = input::radio($attrs['name'], $value, $this->model->$name, $attrs);
            $inp = new form_item($field, $label, "");
            $group->insert_append($inp);
        }
        if ($err) {
            $group->insert_append(new node(class: "invalid-feedback server", children: $err));
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
