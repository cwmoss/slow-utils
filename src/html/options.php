<?php

namespace slow\util\html;

class options {

    public static function for_select(array $items, $value = [], array $opts = []): string {
        $html = "";
        $opts['compare_strings'] = (isset($opts['compare_strings']) && $opts['compare_strings']) ? true : false;
        if (!is_array($value)) $value = array($value);
        if (isset($opts['nullentry'])) $html .= sprintf('<option value="">%s</option>' . "\n", $opts['nullentry']);

        if (isset($opts['group'])) {
            foreach ($items as $group => $groupitems) {
                $html .= sprintf('<optgroup %s>', self::opts_to_html(array("label" => $group)));
                $html .= self::options_list($groupitems, $value, $opts);
                $html .= "</optgroup>";
            }
        } else {
            $html .= self::options_list($items, $value, $opts);
        }
        return $html;
    }

    public static function options_list(array $items, $value, array $opts = []): string {
        $html = "";
        if (isset($opts['nohash'])) {
            foreach ($items as $v) {
                $hopts = array();
                if (is_array($v)) {
                    $hopts['class'] = $v[1];
                    $v = $v[0];
                }
                if (in_array($v, $value)) {
                    $hopts['selected'] = 'selected';
                }
                $html .= sprintf('<option%s>%s</option>' . "\n", self::opts_to_html($hopts), $v);
            }
        } else {
            foreach ($items as $k => $v) {
                $hopts = array();
                if (is_array($v)) {
                    $hopts['class'] = $v[1];
                    $v = $v[0];
                }

                if ($opts['compare_strings']) $k = (string) $k;
                if (in_array($k, $value)) {
                    //   if(isset($vkeys[$k])){   
                    $hopts['selected'] = 'selected';
                }
                $hopts['value'] = (string)$k;
                $html .= sprintf('<option%s>%s</option>' . "\n", self::opts_to_html($hopts), $v);
            }
        }
        return $html;
    }

    public static function opts_to_html(array $opts): string {
        $html = "";
        foreach ($opts as $k => $v) {
            $v = trim($v ?: "");
            if (!$v && !($v === 0 || $v == "0")) continue;
            $html .= ' ' . $k . '="' . htmlspecialchars($v) . '"';
        }
        return $html;
    }
}
