<?php

namespace slow\util;

class text {

    public static function words(string $line, string $delimiter = ' ') {
        $words = array_filter(explode($delimiter, $line), 'trim');
        // TODO: if delimiter is not a white space char
        if ($delimiter !== ' ') {
            $words = array_map('trim', $words);
        }
        return array_values($words);
    }

    public static function switches(string $switches) {
        $switches = array_reduce(self::words($switches), function ($res, $item) {
            $res[trim($item, '-+')] = ($item[0] != '-');
            return $res;
        }, []);
        return $switches;
    }

    public static function text_for(string $text, array $data, array $modifiers = []) {
        $ok = preg_match_all('!\{(.*?)\}!', $text, $matches);
        $modified_data = [];
        foreach ($matches[1] as $m) {
            $mods = self::words($m, '|');

            $name = array_shift($mods);
            $value = @$data[$name];
            foreach ($mods as $mod) {
                if ($mod[0] == '%') {
                    $value = sprintf("$mod", $value);
                } elseif (isset($modifiers[$mod])) {
                    $value = $modifiers[$mod]($value);
                } else {
                    // $gmod = '\\' . $mod;
                    $value = $mod($value);
                    // $value = '~' . $mod . '~';
                }
            }
            $modified_data[$m] = $value;
        }
        return self::text_replace($text, $modified_data);
    }

    public static function text_replace(string $text, array $data = []) {
        $repl = [];
        foreach ($data as $k => $v) {
            $repl['{' . strtolower($k) . '}'] = $v;
        }
        $text = str_replace(array_keys($repl), $repl, $text);
        return $text;
    }

    public static function split_array_to_sections(array $input, string $marker = '==', string $comment = '#') {
        $marker = preg_quote($marker, '!');
        $sections = [];
        $cur = null;
        foreach ($input as $line) {
            $line = trim($line);
            if (!$line || $line[0] == $comment) continue;
            if (preg_match("!^{$marker}(.*?)$!", $line, $mat)) {
                $cur = trim($mat[1]);
                if (!$cur) $cur = null;
                continue;
            } else {
                if (is_null($cur)) $cur = '__undefined__';
                if (!isset($sections[$cur])) $sections[$cur] = [];
                $sections[$cur][] = $line;
            }
        }
        return $sections;
    }
    /*
        split text on named boundary to associative array
        ex:

        @@@ intro @@@

        everything until the end or next boundary will
        go into the key "intro"

        @@@   chapter one @@@

        this goes into the key "chapter one"

    */
    public static function split_boundary(string $str, string $boundary = '@@@') {
        $boundary = preg_quote($boundary, '!');
        $mat = preg_split("!^\s*$boundary\s*([-\w]+)\s*$boundary\s*$!ms", $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $splitted = [];
        for ($i = 0; $i < count($mat);) {
            $splitted[$mat[$i++]] =  $mat[$i++];
        }
        return $splitted;
    }

    /*
		kyff macro style lines
	*/
    function parse_macro_arr($lines, $special = '#~') {
        $ret = array();
        foreach ($lines as $line) {
            $m = $this->parse_macro($line, $special);
            $ret[$m['_id_']] = $m;
        }
        return $ret;
    }

    function parse_macro($line, $special = '#~') {
        $line = str_replace("\t", " ", $line);
        list($id, $text) = explode(' ', $line, 2);
        return array_merge(array('_id_' => $id), $this->parse_macro_attrs($text, $special));
    }

    function parse_macro_attrs($text, $special = '#~') {
        $text = trim($text);
        $special = str_split($special);

        $atts = array('_args_' => array());

        $pattern = '/([-+~#\w]+)\s*:\s*"([^"]*)"(?:\s|$)|' .
            '([-+~#\w]+)\s*:\s*\'([^\']*)\'(?:\s|$)|' .
            '([-+~#\w]+)\s*:\s*([^\s\'"]+)(?:\s|$)|' .
            '"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';

        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                #print_r($m);
                $v = "";
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes(rtrim($m[2], ','));
                elseif (!empty($m[3]))
                    $atts[strtolower($m[3])] = stripcslashes(rtrim($m[4], ','));
                elseif (!empty($m[5]))
                    $atts[strtolower($m[5])] = stripcslashes(rtrim($m[6], ','));
                elseif (isset($m[7]) and strlen(rtrim($m[7], ',')))
                    $v = stripcslashes(rtrim($m[7], ','));
                elseif (isset($m[8]))
                    $v = stripcslashes(rtrim($m[8], ','));

                if (!$v) continue;

                if ($v[0] == '+') {
                    $atts[ltrim($v, '+-')] = true;
                } elseif ($v[0] == '-') {
                    $atts[ltrim($v, '+-')] = false;
                } elseif (in_array($v[0], $special)) {
                    $atts[$v[0]] = substr($v, 1);
                } else {
                    $atts['_args_'][] = $v;
                }
            }
        } else {
            # $atts = ltrim($text);
        }
        return $atts;
    }

    /*
        ~(?P<shortcode>(?:(?:\s?\[))(?P<name>[\w\-]{3,})(?:\s(?P<attrs>[\w\d,\s=\"\'\-\+\#\%\!\~\`\&\.\s\:\/\?\|]+))?(?:\])(?:(?P<html>[\w\d\,\!\@\#\$\%\^\&\*\(\\)\s\=\"\'\-\+\&\.\s\:\/\?\|\<\>]+)(?:\[\/[\w\-\_]+\]))?)~ug
    */
}
