<?php

namespace slow\util\html;

class abbreviation_parser {

    /*
        > child
        + sibling
        ^ up to parent
        *3 3 times the node
        [attr=value]
        `refname reference
    */
    public static function parse(string $definition, bool $return_root = true): node {
        $chars = mb_str_split(trim($definition));

        $type = token::name;
        $word = "";

        $tag = null;
        $current_node = $root = new node('');
        $mode = 'sibling';
        $multi = $ref = null;
        $in_attr = $in_text = 0;
        foreach ($chars as $char) {
            $t = token::tryFrom($char);
            if ($t) {
                if ($in_text) {
                    if ($t != token::text_end) {
                        $word .= $char;
                        if ($t == token::text) $in_text++;
                    } else {
                        $in_text--;
                        if ($in_text) {
                            $word .= $char;
                        } else {
                            self::handle_word($root, $tag, $type, $word, $ref);
                        }
                    }
                    continue;
                }
                if ($in_attr) {
                    if ($t != token::attr_end) {
                        $word .= $char;
                        if ($t == token::attr) $in_attr++;
                    } else {
                        $in_attr--;
                        if ($in_attr) {
                            $word .= $char;
                        } else {
                            self::handle_word($root, $tag, $type, $word, $ref);
                        }
                    }
                    continue;
                }

                if ($t->is_end()) {
                    // dbg("++ end", $char, $word, $type, $tag);
                    self::handle_word($root, $tag, $type, $word, $ref);
                    if ($tag) {
                        $tag->check_if_textnode();
                        self::handle_insert($root, $current_node, $tag, $mode, $ref);
                    }
                    $mode = $t->next_insert_mode();
                    $tag = null;
                    $multi = $ref = null;
                    if ($t == token::up) {
                        $current_node = $current_node->parent();
                    }
                    $type = token::name;
                    $in_attr = $in_text = 0;
                    continue;
                }
                // $word = trim($word);
                if ($type == token::multi) {
                    $multi = $word;
                } elseif ($type == token::ref) {
                    $ref = $word;
                } else {
                    if ($t == token::attr) $in_attr++;
                    if ($t == token::text) $in_text++;
                    self::handle_word($root, $tag, $type, $word, $ref);
                }
                $type = $t;
                $word = "";
            } else {
                $word .= $char;
            }
        }
        // dbg("++status-end", $word, $tag, $type);
        if ($tag || $word) {
            // dbg("last word", $word);
            self::handle_word($root, $tag, $type, $word, $ref);
            self::handle_insert($root, $current_node, $tag, $mode, $ref);
        }
        if ($return_root) return $root;
        else return $current_node;
    }

    public static function handle_insert(node $root, node &$current_node, node $tag, string $mode, ?string &$ref) {
        $tag->check_if_textnode();
        if ($ref) {
            $root->ref($ref, $tag);
        }
        if ($mode == 'sibling') {
            $current_node->insert_after($tag);
        } else {
            // dbg("++ last", $tag);
            $current_node->insert_append($tag);
        }
        $current_node = $tag;
    }
    public static function handle_word(node $root, ?node &$node, token $type, string &$word, ?string &$ref) {
        if ($type != token::text) $word = trim($word);
        if (!$word) return;

        if (!$node) $node = new node('');
        if ($type == token::ref) {
            $ref = $word;
            $word = "";
            return;
        }
        $node->set_by_token($type, $word);
        $word = "";
    }
}
