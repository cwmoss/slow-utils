<?php

namespace slow\util\html;

class forminput extends component {

    public string $id;
    public string $type = "text";

    public tag $label;
    public tag $error;

    // alternativ für radio buttons
    //  liste von forminputs mit 1 error feld
    public ?array $items;

    public function __construct(
        public tag $input,
        string $label,
        string|bool $error,
        string $id = ""
    ) {
        $this->init($label, $error, $id);
    }

    public function init(string $label, string|bool $error, string $id): void {
        $this->id = $id;
        $this->label = new tag('label', content: $label, attrs: ['for' => $this->input->id]);
        if ($error !== "") {
            $this->add_error($error);
        }
    }

    public function add_error(string $error): self {
        $this->input->class_add('is-invalid');
        // $this->error = tag::new('.invalid-feedback', $error);
        $this->input->after(tag::new('.invalid-feedback', $error));
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
