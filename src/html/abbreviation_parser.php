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
        $in_attr = $in_text = false;
        foreach ($chars as $char) {
            $t = token::tryFrom($char);
            if ($t) {
                if ($in_text && $t != token::text_end) {
                    $word .= $char;
                    continue;
                } elseif ($in_text) {
                    self::handle_node($root, $tag, $type, $word, $ref);
                    $in_text = false;
                    $word = "";
                    continue;
                }
                if ($in_attr && $t != token::attr_end) {
                    $word .= $char;
                    continue;
                } elseif ($in_attr) {
                    self::handle_node($root, $tag, $type, $word, $ref);
                    $in_attr = false;
                    $word = "";
                    continue;
                }

                if ($t->is_end()) {
                    // dbg("++ end", $char, $word, $type, $tag);
                    self::handle_node($root, $tag, $type, $word, $ref);
                    if ($tag) {
                        $tag->check_if_textnode();
                        // dbg("new tag", $tag, $mode);
                        if ($ref) {
                            $root->ref($ref, $tag);
                        }
                        if ($mode == 'sibling') {
                            $current_node->insert_after($tag);
                        } else {
                            $current_node->insert_append($tag);
                        }
                        // dbg("++end");
                        $current_node = $tag;
                    }
                    $mode = $t->next_insert_mode();
                    $tag = null;
                    $multi = $ref = null;
                    if ($t == token::up) {
                        $current_node = $current_node->parent();
                    }
                    $type = token::name;
                    $word = "";
                    $in_attr = $in_text = false;
                    continue;
                }
                // $word = trim($word);
                if ($type == token::multi) {
                    $multi = $word;
                } elseif ($type == token::ref) {
                    $ref = $word;
                } else {
                    if ($t == token::attr) $in_attr = true;
                    if ($t == token::text) $in_text = true;
                    self::handle_node($root, $tag, $type, $word, $ref);
                }
                $type = $t;
                $word = "";
            } else {
                $word .= $char;
            }
        }
        // dbg("++status-end", $word, $tag, $type);
        if ($tag || $word) {
            dbg("last word", $word);
            self::handle_node($root, $tag, $type, $word, $ref);

            if ($mode == 'sibling') {
                $current_node->insert_after($tag);
            } else {
                dbg("++ last", $tag);
                $current_node->insert_append($tag);
            }
        }
        if ($return_root) return $root;
        else return $tag;
    }

    public static function handle_node(node $root, ?node &$node, token $type, string $word, ?string &$ref) {
        if ($type != token::text) $word = trim($word);
        if (!$word) return;

        if (!$node) $node = new node('');
        if ($type == token::ref) {
            $ref = $word;
            return;
        }
        $node->set_by_token($type, $word);
    }
}
