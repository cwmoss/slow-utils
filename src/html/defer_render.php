<?php

namespace slow\util\html;

class defer_render {


    public function __construct(public string $attr_name, public string $var_name) {
    }

    public function replacement(bool|string|null $var) {
        // dbg("++repl", $var);
        $val = "";
        if (is_bool($var)) {
            $val = $var ? (" " . $this->attr_name) : "";
        } else {
            $val = sprintf(' %s="%s"', $this->attr_name, (string) $var);
        }
        return [(string) $this, $val];
    }

    public function __toString() {
        return "{__a__{$this->attr_name}.{$this->var_name}}";
    }
}
