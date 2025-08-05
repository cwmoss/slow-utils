<?php

namespace slow\util\html;

class form {

    public array $remaining_errors = [];

    public function __construct(
        public object $model,
        public string $basename = "%s",
        public string $validator_element = "bob-validator",
        public string $error_class = "invalid-feedback server",
        public string $error_input_class = 'is-invalid'
    ) {
        // dbg("+++ form model", $model);
        $this->set_remaining_errors($model);
    }

    public function set_remaining_errors($model) {
        foreach ($model->errors->cols as $name => $errs) {
            $this->remaining_errors[$name] = array_unique($model->errors->on($name));
        }
        $base = $model->errors->on_base();
        if ($base) {
            // uralt fehler: base kann auch col:"", msg:"..." objekte enthalten
            $base = array_map(fn($err) => is_object($err) ? $err->msg : $err, $base);
            $this->remaining_errors['_'] = array_unique($base);
        }
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
        // dbg("++ remaining +++", $this->remaining_errors);
        return join($separator, array_map(
            fn($field) =>
            join($separator, $field),
            $this->remaining_errors
        ));
    }

    public function validator(array|string $fields): node {
        [$rules, $messages] = $this->model->validatable_get_validator()->js_component_rules($fields, $this->basename);
        return new node($this->validator_element, data: ['rules' => $rules, 'messages' => $messages]);
    }

    public function validator_close() {
        return "</{$this->validator_element}>";
    }

    public function form_node(node $field, string $label_text, string $error_text): node {
        $error_id = $field->id . '-error';
        $label = new node('label', children: $label_text, attrs: ['for' => $field->id]);
        $error = new node(class: $this->error_class, id: $error_id, children: $error_text);
        $field->attr('aria-describedby', $error_id)->attr('aria-invalid', $error_text ? 'true' : 'false');
        return new node(children: [$label, $field, $error]);
    }

    public function base_attrs(string $name, array $attrs = [], ?string $extended_id = null): array {
        $fname = sprintf($this->basename, $name);
        if (isset($attrs['id']) && $attrs['id']) {
            $id = $attrs['id'];
        } else {
            $id = self::id($fname, $extended_id);
        }
        return ['name' => $fname, 'id' => $id] + $attrs;
    }

    public function input(string $name, string $label, string $type = "text", array $attrs = []): node {
        $err = $this->get_and_burn_errors_on($name);
        $attrs = $this->base_attrs($name, $attrs);
        $field = input::input($attrs['name'], $this->model->$name, $type, $attrs);
        return $this->form_node($field, $label, $err);
    }

    public function textarea(string $name, string $label, ?string $size = null, array $attrs = []): node {
        $err = $this->get_and_burn_errors_on($name);
        $attrs = $this->base_attrs($name, $attrs);
        $field = input::textarea($attrs['name'], $this->model->$name, $size, $attrs);
        return $this->form_node($field, $label, $err);
    }

    public function checkbox(string $name, string $label, $value, $unchecked_value = "0", array $attrs = []): node {
        $err = $this->get_and_burn_errors_on($name);
        $attrs = $this->base_attrs($name, $attrs);
        $field = input::checkbox($attrs['name'], $value, $this->model->$name, $attrs);
        $node = $this->form_node($field, $label, $err);
        $node->insert_prepend(input::hidden($attrs['name'], $unchecked_value));
        // $field = new node("", children: []);
        return $node;
    }

    public function selectbox(string $name, string $label, array $items, ?string $nullentry = null, array $options_opts = [], array $attrs = []): node {
        $err = $this->get_and_burn_errors_on($name);
        $attrs = $this->base_attrs($name, $attrs);

        $field = input::selectbox($attrs['name'], $items, $this->model->$name, $nullentry, $options_opts, $attrs);
        #$field = $this->form_field($name, 'select')
        #    ->content(options::for_select($items, (string) $this->model->$name, $opts));

        $inp = $this->form_node($field, $label, $err);
        return $inp;
    }

    public function radio_group(
        string $name,
        array $items,
        string $legend = "",
        string $style = 'wrapped',
        array $label_attrs = [],
        array $input_attrs = [],
        array $item_attrs = [],
        $container = null
    ): node {
        $err = $this->get_and_burn_errors_on($name);
        $attrs = $this->base_attrs($name, $input_attrs);
        $error_id = $name . '-error';

        $nodes = [];
        foreach ($items as $value => $label) {
            $field = input::radio($attrs['name'], $value, $this->model->$name);
            // dbg("radio+++", $field);

            $field->attrs($input_attrs);
            if ($item_attrs[$value] ?? null) {
                $field->attrs($item_attrs[$value]);
            }
            if ($err) {
                $field->class_add($this->error_input_class);
            }
            $field
                ->attr('aria-describedby', $error_id)
                ->attr('aria-invalid', $err ? 'true' : 'false');

            $label_el = new node("label", id: $field->id, attrs: $label_attrs);
            if ($style == 'wrapped') {
                $label_el->content([$field, $label]);
                $nodes[] = $label_el;
            } else {
                $nodes[] = $label_el->content($label);
                $nodes[] = $field;
            }
        }
        $nodes[] = new node(class: $this->error_class, id: $error_id, children: $err);
        if ($legend) {
            array_unshift($nodes, (new node('legend'))->raw_content($legend));
        }
        if (!$container) $container = new node("fieldset");
        else {
            if ($err) $container->class_add("is-invalid");
        }
        $container->content($nodes);
        return $container;
    }

    public function xradios(string $name, array $items, array $attrs = []): node {
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
