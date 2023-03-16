<?php

namespace slow\util;

class http {

    public static function header_nocache() {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Content-Type: text/html');
    }
    /*
        https://github.com/hansott/psr7-cookies/blob/master/src/SetCookie.php
    */
    public static function cookie_value(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Strict'
    ): string {
        $headerValue = sprintf('%s=%s', $name, urlencode($value));

        if ($expires !== 0) {
            $headerValue .= sprintf(
                '; expires=%s',
                gmdate('D, d M Y H:i:s T', $expires)
            );
        }

        if (empty($path) === false) {
            $headerValue .= sprintf('; path=%s', $path);
        }

        if (empty($domain) === false) {
            $headerValue .= sprintf('; domain=%s', $domain);
        }

        if ($secure) {
            $headerValue .= '; secure';
        }

        if ($httponly) {
            $headerValue .= '; httponly';
        }

        if ($samesite !== '') {
            $headerValue .= sprintf('; samesite=%s', $samesite);
        }

        return $headerValue;
    }
}
