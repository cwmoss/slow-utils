<?php

namespace slow\util;

class arr {

    public static function dot_get($data, $path, $default = null) {
        $val = $data;
        $path = explode(".", $path);
        foreach ($path as $key) {
            if (!isset($val[$key])) {
                return $default;
            }
            $val = $val[$key];
        }
        return $val;
    }
}
