<?php

namespace slow\util\html;

class formgroup extends component {

    public tag $error;

    // z.b. für radio button group
    //  liste von forminputs mit 1 error feld
    public array $items = [];

    public function __construct(
        string|bool $error,
    ) {
        if (false !== $error) {
            $this->add_error($error);
        }
    }

    public function add_item(forminput $item): self {
        $this->items[] = $item;
        return $this;
    }

    public function add_error(string $error): self {
        $this->error = tag::new('.error', $error);
        return $this;
    }

    public function get_content(): string {
        return $this->render_array($this->items) .
            ($this->error ?? "");
    }
}
