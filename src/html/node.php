<?php

namespace slow\util\html;

use slow\util\text;


class node {

    public static array $self_closing_tags = [
        "area", "base", "br", "col",
        "embed", "hr", "img", "input", "keygen", "link",
        "meta", "param", "source", "track", "wbr"
    ];

    public function __construct(
        public string $tagname = 'div',
        public ?self $parent = null,
        public string $id = "",
        string|array $class = [],
        array $attrs = [],
        public array $data = [],
        self|array|string|null $children = null,
    ) {
        $this->content($children);
        $this->class_add($class);
        $this->attrs($attrs);
    }

    public ?self $root = null;
    public array $attrs = [];
    public array $class = [];
    public array $children = [];
    public array $refs = [];
    public array $tpl_attr = [];
    public array $tpl_text = [];
    public string $raw_content = "";

    public static function new(string $definition, string|tag|array|null $content = null): self {
        $tag = self::parse_definition($definition);
        if ($content !== null) {
            $tag->content($content);
        }
        // return new self(...$tag, content: $content);
        return $tag;
    }

    public function set_by_token(token $token, $value): self {
        if ($token == token::attr) {
            $value = soup::words($value);
            // dbg("++ set attr", $value);
        }
        match ($token) {
            token::cls => $this->class_add($value),
            token::name => $this->tagname($value),
            token::id => $this->id($value),
            token::attr, token::attr_end => array_map(fn ($v) => $this->attr(...explode('=', $v)), $value),
            token::text, token::text_end => $this->children[] = $value,
            default => throw new \Exception("dont know about: " . $token->value)
        };
        return $this;
    }

    public static function parse_definition(string $definition): self {
        return abbreviation_parser::parse($definition, false);
    }

    public function check_if_textnode() {
        // return;
        if (
            $this->tagname == '' && count($this->children) == 1
            && is_string($this->children[0])
            && !$this->class && !$this->id
            && !$this->attrs  && !$this->data
        ) {
        } else {

            if (!$this->tagname) $this->tagname = 'div';
        }
    }

    public static function attr_from_string(string $attr_val): array {
        // remove []
        if ($attr_val[0] == '[') $attr_val = substr($attr_val, 1, strlen($attr_val) - 2);
        [$name, $value] = explode("=", $attr_val, 2) + [1 => true];
        return [$name, $value];
    }

    public function ref(string $name, self &$node) {
        $this->refs[$name] = $node;
        return $this;
    }

    public function get(string $refname) {
        return $this->root()->refs[$refname];
    }

    public function root() {
        return $this->root ?: $this;
    }

    public function parent() {
        return $this->parent;
    }
    public function parent_or_root() {
        return $this->parent ?: $this;
    }
    /*
    insertAdjacentElement()
    <!-- beforebegin -->        // before
    <p>
        <!-- afterbegin -->     // prepend
        foo
        <!-- beforeend -->      // append
    </p>
    <!-- afterend -->           // after
    */
    public function insert_before(self $node): self {
        $this->parent_or_root()->insert_prepend($node);
        return $this;
    }
    public function insert_after(self $node): self {
        $this->parent_or_root()->insert_append($node);
        return $this;
    }
    public function insert_prepend(self $node): self {
        $node->parent = &$this;
        if ($this->root) {
            $node->root = &$this->root;
        } else {
            $node->root = &$this;
        }
        array_unshift($this->children, $node);
        return $this;
    }
    public function insert_append(self $node): self {
        $node->parent = &$this;
        if ($this->root) {
            $node->root = &$this->root;
        } else {
            $node->root = &$this;
        }
        array_push($this->children, $node);
        return $this;
    }
    public function replace(self $old, self $node): self {
        foreach ($this->children as $idx => $c) {
            if ($c == $old) {
                $node->parent = &$this;
                $node->root = &$this->root;
                $this->children[$idx] = $node;
                break;
            }
        }
        return $this;
    }

    public function wrap(string|self $tags): self {
        if (is_string($tags)) {
            $tags = abbreviation_parser::parse($tags, false);
            $parent = $this->parent;
            $tags->insert_append($this);
            $parent->replace($this, $tags->root);
            //            $this->wrap = array_map(fn ($t) => tag::new($t), $tags);
        } else {
            //            $this->wrap = $tags;
            $parent = $this->parent_or_root();
            $tags->insert_append($this);
            $parent->replace($this, $tags->parent_or_root());
        }

        return $this;
    }

    public function content(self|string|null|array $content): self {
        // dbg("+++ add content", $content, $this->children);
        if ($content && !is_array($content)) {
            $content = [$content];
        }
        // dbg("++ new content", $content);
        if ($content) foreach ($content as $c) {
            if (is_object($c)) {
                $this->insert_append($c);
            } else {
                array_push($this->children, $c);
            }
            // $this->children = array_merge($this->children, $content);
        }
        // dbg("merged children", $this->children);
        return $this;
    }

    public function raw_content(string $content): self {
        $this->raw_content = $content;
        return $this;
    }

    public function tagname(string $tag): self {
        $this->tagname = $tag;
        return $this;
    }

    public function id(string $id): self {
        $this->id = $id;
        return $this;
    }

    public function type(): string|null {
        return $this->attr_get('type');
    }

    public function class_add(array|string $class, mixed $condition = null) {
        if (is_string($class)) $class = soup::words($class);
        if (is_null($condition) || $condition)
            $this->class = array_unique(array_merge($this->class, $class));
        return $this;
    }

    public function attr(string $name, bool|string|null $value = true): self {
        if ($name == 'id') {
            $this->id = $value;
        } elseif ($name == 'class') {
            $this->class_add($value);
        } elseif (str_starts_with($name, 'data-')) {
            $this->data(substr($name, 5), $value);
        } else {
            $this->attrs[$name] = $value;
        }
        return $this;
    }

    public function attrs(array $attributes): self {
        foreach ($attributes as $name => $value) {
            $this->attr($name, $value);
        }
        return $this;
    }

    public function attr_get(string $name): bool|string|null {
        return $this->attrs[$name] ?? null;

        //foreach ($this->attrs as $attr) {
        //    if ($attr[0] == $name) return $attr[1];
        //}
        //return false;
    }


    public function data(string $name, bool|string|null $value = true): self {
        $this->data[$name] = $value;
        return $this;
    }

    public function datas(array $datas): self {
        foreach ($datas as $name => $value) {
            $this->data($name, $value);
        }
        return $this;
    }

    public function data_get(string $name): bool|string|null {
        return $this->data[$name] ?? null;
    }

    public function to_attrs(): array {
        $attrs = [];
        if ($this->id) $attrs['id'] = $this->id;
        if ($this->class) $attrs['class'] = join(" ", $this->class);
        return $attrs + $this->attrs + $this->data_to_attrs();
    }

    public function data_to_attrs(): array {
        $data = [];
        foreach ($this->data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $v = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $data['data-' . $k] = $v;
        }
        return $data;
    }
    function with(string $property, $function): static {
        $prop = is_array($this->$property) ? $this->$property[0] : $this->$property;
        $function($prop);
        return $this;
    }

    function each($function): static {
        foreach ($this->children as $prop) {
            $function($prop);
        }
        return $this;
    }


    public function get_content(): string {
        if ($this->raw_content) return $this->raw_content;
        return $this->render_array($this->children);
    }

    public function open(): string {
        return self::tag_open($this->tagname, $this->to_attrs(), $this->root());
    }

    public function close(): string {
        return self::tag_close($this->tagname);
    }

    public static function h($attr): string {
        return htmlspecialchars($attr);
    }

    public static function tag_open(string $name, array $attrs, node $root): string {
        if (!$name) return "";
        $attr = [];
        foreach ($attrs as $aname => $avalue) {
            if ($aname[0] == ':') {
                $aname = strtolower(substr($aname, 1));
                $defer = new defer_render($aname, $avalue);
                $root->tpl_attr[] = $defer;
                $attr[] = (string) $defer;
                continue;
            }
            if (is_bool($avalue)) {
                if ($avalue) {
                    $attr[] = " " . $aname;
                }
            } else {
                $attr[] = sprintf(' %s="%s"', $aname, self::h($avalue));
            }
        }
        /*
        $attrs = array_reduce($attrs, function ($res, $item) {
            if (is_array($item)) {
                $item = sprintf('%s="%s"', $item[0], self::h($item[1]));
            }
            return $res . " " . $item;
        }, "");
        */
        return sprintf('<%s%s>', $name, join("", $attr));
    }

    public static function tag_close(string $name): string {
        if (!$name) return "";
        if (in_array($name, self::$self_closing_tags)) return "";
        return sprintf('</%s>', $name);
    }

    public static function tag(string $name, array $attrs, string $content = "", ?node $root = null): string {
        $start = self::tag_open($name, $attrs, $root);
        return sprintf('%s%s%s', $start, $content, self::tag_close($name));
    }

    public function render_array(array $elements): string {
        return join("", array_map(fn ($el) => is_string($el) ? self::h($el) : (string) $el, $elements));
    }

    public function template() {
        $tpl = $this->root()->render();
        $attrs = $this->root()->tpl_attr;
        return function (array $data) use ($tpl, $attrs) {
            $repl = [];
            foreach ($attrs as $defer) {
                // dbg("++defer", $defer->var_name);
                [$k, $v] = $defer->replacement($data[$defer->var_name] ?? null);
                $repl[$k] = $v;
            }
            foreach ($data as $k => $v) {
                $repl['{' . strtolower($k) . '}'] = $v;
            }
            // print_r($repl);
            $text = str_replace(array_keys($repl), $repl, $tpl);
            return $text;
        };
    }
    public function render_doc(): string {
        return $this->root()->render();
    }
    public function render(): string {
        $html = "";
        $html .=
            $this->open() .
            $this->get_content() .
            $this->close();
        return $html;
    }

    public function __toString(): string {
        return $this->render();
    }
}
