<?php
namespace Cliclamani\Flesco\App\Routes;

class Route {
    
    public static $rountines;

    public static function get($route, $calback) {
        self::$rountines['get'][$route] = $calback;
    }

    public static function post($route, $callback) {
        self::$rountines['post'][$route] = $callback;
    }
}