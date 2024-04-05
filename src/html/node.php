<?php

namespace slow\util\html;



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
        public array $attrs = [],
        public array $data = [],
        self|array|string|null $children = null,
    ) {
        $this->content($children);
        $this->class_add($class);
    }

    public array $class = [];
    public array $children = [];
    public array $refs = [];

    public static function new(string $definition, string|tag|array|null $content = null): self {
        $tag = self::parse_definition($definition);
        // return new self(...$tag, content: $content);
        return $tag;
    }

    public function set_by_token(token $token, $value): self {
        match ($token) {
            token::class => $this->class_add($value),
            token::name => $this->tagname($value),
            token::id => $this->id($value),
            token::attr, token::attr_end => $this->attr(...explode('=', $value)),
            token::text, token::text_end => $this->children[] = $value,
            default => throw new \Exception("dont know about: " . $token->value)
        };
        return $this;
    }

    public static function parse_definition(string $definition): self {
        return abbreviation_parser::parse($definition);
    }

    public static function xxparse_definition(string $definition): self {
        $parts = soup::words($definition);
        $tag = [];
        $current_node = $root = new node('');
        $mode = 'sibling';
        $multi = $ref = null;
        while ($part = array_shift($parts)) {
            if (in_array($part[0], ['>', '+', '^'])) {

                // when multiple ^ ups, we create node only once
                if ($tag) {
                    $node = self::make_node($tag);
                    dbg("new tag", $tag, $node, $mode);
                    if ($ref) {
                        $root->ref($ref, $node);
                    }
                    if ($mode == 'sibling') {
                        $current_node->insert_after($node);
                    } else {
                        $current_node->insert_append($node);
                    }
                    dbg("++end");
                    $current_node = $node;
                }
                if ($part[0] == '>') {
                    $mode = 'child';
                } else {
                    $mode = 'sibling';
                }
                $tag = [];
                $multi = $ref = null;
                if ($part[0] == '^') {
                    $current_node = $current_node->parent();
                }
                $part = substr($part, 1);
                if (!$part) continue;
            }
            // dbg("++++ tag", $tag);
            if ($part[0] == '^') {
                $current_node = $current_node->parent();
                while ($part = substr($part, 1)) {
                    if ($part[0] == '^') {
                        $current_node = $current_node->parent();
                    } else {
                        break;
                    }
                }
                if (!$part) continue;
            }
            if ($part[0] == '*') {
                $multi = substr($part, 1);
                continue;
            }
            if ($part[0] == '`') {
                $ref = substr($part, 1);
                continue;
            }
            $tag[] = $part;
        }
        if ($tag) {
            $node = self::make_node($tag);
            if ($mode == 'sibling') {
                $current_node->insert_after($node);
            } else {
                $current_node->insert_append($node);
            }
            $current_node = $node;
        }
        return $root;
    }

    public static function make_node(array $parts): self {
        $tag = ['tagname' => 'div', 'id' => "", 'class' => [], 'attrs' => [], 'children' => null];
        if ($parts[0][0] != '#' && $parts[0][0] != '.') {
            $tag['tagname'] = array_shift($parts);
        }
        $attrs = [];
        foreach ($parts as $part) {
            match ($part[0]) {
                '#' => $tag['id'] = ltrim($part, '#'),
                '.' => $tag['class'][] = ltrim($part, '.'),
                '[' => $attrs[] = self::attr_from_string($part),
                '{' => $tag['children'] = substr($part, 1, strlen($part) - 2),
                default => $attrs[] = $part
            };
        }
        foreach ($attrs as $attr) {
            $tag['attrs'][$attr[0]] = $attr[1];
        }
        return new self(...$tag);
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
        return $this->refs[$refname];
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
        array_unshift($this->children, $node);
        return $this;
    }
    public function insert_append(self $node): self {
        $node->parent = &$this;
        array_push($this->children, $node);
        return $this;
    }

    public function wrap(string|array $tags): self {
        if (is_string($tags)) {
            $tags = explode(">", $tags);
            $this->wrap = array_map(fn ($t) => tag::new($t), $tags);
        } else {
            $this->wrap = $tags;
        }

        return $this;
    }

    public function content(self|string|null|array $content): self {
        // dbg("+++ add content", $content, $this->children);
        if ($content && !is_array($content)) {
            $content = [$content];
        }
        // dbg("++ new content", $content);
        if ($content) {
            $this->children = array_merge($this->children, $content);
        }
        // dbg("merged children", $this->children);
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
            $this->class = array_merge($this->class, $class);
        return $this;
    }

    public function attr(string $name, bool|string|null $value = true): self {
        $this->attrs[$name] = $value;
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

    function each(string $property, $function): static {
        foreach ($this->$property as $prop) {
            $function($prop);
        }
        return $this;
    }


    public function get_content(): string {
        return $this->render_array($this->children);
    }

    public function open(): string {
        return self::tag_open($this->tagname, $this->to_attrs());
    }

    public function close(): string {
        return self::tag_close($this->tagname);
    }

    public static function h($attr): string {
        return htmlspecialchars($attr);
    }

    public static function tag_open(string $name, array $attrs): string {
        if (!$name) return "";
        $attr = [];
        foreach ($attrs as $aname => $avalue) {
            if (is_bool($avalue)) {
                if ($avalue) {
                    $attr[] = $aname;
                }
            } else {
                $attr[] = sprintf('%s="%s"', $aname, self::h($avalue));
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
        return sprintf('<%s%s%s>', $name, ($attr ? ' ' : ''), join(" ", $attr));
    }

    public static function tag_close(string $name): string {
        if (!$name) return "";
        if (in_array($name, self::$self_closing_tags)) return "";
        return sprintf('</%s>', $name);
    }

    public static function tag(string $name, array $attrs, string $content = ""): string {
        $start = self::tag_open($name, $attrs);
        return sprintf('%s%s%s', $start, $content, self::tag_close($name));
    }

    public function render_array(array $elements): string {
        return join("", array_map(fn ($el) => (string) $el, $elements));
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
