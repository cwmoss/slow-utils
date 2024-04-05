<?php

namespace slow\util\html;

enum token: string {
    case name = 'name';
    case id = '#';
    case cls = '.';
    case attr = '[';
    case attr_end = ']';
    case text = '{';
    case text_end = '}';
    case child = '>';
    case sibling = '+';
    case up = '^';
    case multi = '*';
    case ref = "`";

    public function is_end() {
        return match ($this) {
            self::child, self::sibling, self::up => true,
            default => false
        };
    }

    public function next_insert_mode() {
        return match ($this) {
            self::child => 'child',
            default => 'sibling'
        };
    }

    public function tag_prop() {
        return match ($this) {
            self::name => 'name',
            self::attr => 'attr',
            self::text => 'child',
            self::id => 'id',
            self::cls => 'class'
        };
    }
}
