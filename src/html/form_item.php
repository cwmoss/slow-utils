<?php

namespace slow\util\html;

class form_item extends node {

    public node $label;
    public node $error;

    // alternativ für radio buttons
    //  liste von forminputs mit 1 error feld
    public ?array $items;

    public function __construct(
        public node $input,
        string $label,
        string|bool $error,
    ) {
        $this->init($label, $error);
    }

    public function init(string $label, string $error): void {
        // $this->id = $id;
        $this->label = new node('label', children: $label, attrs: ['for' => $this->input->id]);
        if ($error !== "") {
            $this->add_error($error);
        }
    }

    public function add_error(string $error): self {
        $this->input->class_add('is-invalid');
        // $this->error = tag::new('.invalid-feedback', $error);
        $this->input->insert_after(new node(class: '.invalid-feedback', children: $error));
        return $this;
    }

    public function get_content(): string {
        if ($this->input->attr_get('type') == 'checkbox') {
            return (string) $this->input .
                (string) $this->label .
                ($this->error ?? "");
        }
        return (string) $this->label .
            (string) $this->input .
            ($this->error ?? "");
    }
}
