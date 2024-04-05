<?php

namespace slow\util\html;

class tag {

    public static array $self_closing_tags = [
        "area", "base", "br", "col",
        "embed", "hr", "img", "input", "keygen", "link",
        "meta", "param", "source", "track", "wbr"
    ];

    public array $class = [];
    public array $wrap = [];
    public array $before = [];
    public array $after = [];

    public function __construct(
        public string $tagname = 'div',
        public string $id = "",
        string|array $class = [],
        public array $attrs = [],
        public array $data = [],
        public tag|array|string|null $content = null,
        string|array $wrap = []
    ) {
        if (is_null($content)) {
            $this->content = [];
        } elseif (!is_array($content)) {
            $this->content = [$content];
        }
        $this->class_add($class);
        $this->wrap($wrap);
    }

    public static function new(string $definition, string|tag|array|null $content = null): self {
        $tag = self::parse_definition($definition);
        return new self(...$tag, content: $content);
    }

    public static function parse_definition(string $definition): array {
        $tags = explode(">", $definition);
        $me = array_pop($tags);
        $parts = soup::words($me);
        $tag = ['tagname' => 'div', 'id' => "", 'class' => [], 'attrs' => []];
        if ($parts[0][0] != '#' && $parts[0][0] != '.') {
            $tag['tagname'] = array_shift($parts);
        }
        $attrs = [];
        foreach ($parts as $part) {
            match ($part[0]) {
                '#' => $tag['id'] = ltrim($part, '#'),
                '.' => $tag['class'][] = ltrim($part, '.'),
                '[' => $attrs[] = self::attr_from_string($part),
                default => $attrs[] = $part
            };
        }
        foreach ($attrs as $attr) {
            $tag['attrs'][$attr[0]] = $attr[1];
        }
        if ($tags) {
            $tag['wrap'] = join(">", $tags);
        }
        return $tag;
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

    public function content(tag|string|null $content): self {
        $this->content[] = $content;
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

    public function before(string|tag $tag, tag|array|string|null $content = null): self {
        $this->before[] = is_string($tag) ? tag::new($tag, $content) : $tag;
        return $this;
    }

    public function after(string|tag $tag, tag|array|string|null $content = null): self {
        array_unshift($this->after, is_string($tag) ? tag::new($tag, $content) : $tag);
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

    public static function attr_from_string(string $attr_val): array {
        // remove []
        if ($attr_val[0] == '[') $attr_val = substr($attr_val, 1, strlen($attr_val) - 2);
        [$name, $value] = explode("=", $attr_val, 2) + [1 => true];
        return [$name, $value];
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
        return $this->render_array($this->content);
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
        foreach ($this->wrap as $tag) {
            $html .= $tag->open();
        }
        $html .=  $this->render_array($this->before) .
            $this->open() .
            $this->get_content() .
            $this->close() .
            $this->render_array($this->after);

        foreach (array_reverse($this->wrap) as $tag) {
            $html .= $tag->close();
        }
        return $html;
    }

    public function __toString(): string {
        return $this->render();
    }
}
