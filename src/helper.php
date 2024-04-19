<?php

if (!function_exists('dbg')) {
    function dbg($txt, ...$vars) {

        $log = [];
        if (!is_string($txt)) {
            array_unshift($vars, $txt);
        } else {
            $log[] = $txt;
        }
        $log[] = join(' ~ ', array_map(fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $vars));

        error_log(join(' ', $log));
    }
}
if (!function_exists('console_log')) {
    function console_log(...$data) {
        //func_get_args()
        $out = ["<script>", "console.info('%cPHP console', 'font-weight:bold;color:green;');"];
        foreach ($data as $d) {
            $out[] = 'console.log(' . json_encode($d) . ');';
        }
        $out[] = "</script>";
        print(join("", $out));
    }
}
if (!function_exists('println')) {
    function println($str) {
        return print($str . PHP_EOL);
    }
}
if (!function_exists('d')) {
    function d(...$args) {
        echo '<pre>';
        foreach ($args as $arg) {
            print_r($arg);
        }
        echo '</pre>';
    }
}

if (!function_exists('dd')) {
    function dd(...$args) {
        d(...$args);
        die;
    }
}

if (!function_exists('v')) {
    function v(...$args) {
        echo '<pre>';
        foreach ($args as $arg) {
            var_dump($arg);
        }
        echo '</pre>';
    }
}

if (!function_exists('vd')) {
    function vd(...$args) {
        v(...$args);
        die;
    }
}

if (!function_exists('l')) {
    function l(...$args) {
        foreach ($args as $arg) {
            error_log(print_r($arg, true));
        }
    }
}

if (!function_exists('vl')) {
    function vl(...$args) {
        ob_start();
        foreach ($args as $arg) {
            var_dump($arg);
        }
        error_log(ob_get_clean());
    }
}

function benchmark_time($start) {
    $elapsed = microtime(true) - $start;
    $time = [
        'time' => $elapsed,
        'ms' => (int)($elapsed * 1000),
        'microsec' => (int)($elapsed * 1000 * 1000),
        'print' => null
    ];
    $time['print'] = $time['ms'] ? $time['ms'] . ' ms' : $time['microsec'] . ' μs';
    return $time;
}

function hum_size($size) {
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}
