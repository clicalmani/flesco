<?php
namespace Clicalmani\Flesco\Routes;

class Routine
{
    function __construct(
        private string $method,
        private string $route,
        private ? string $action,
        private ? string $controller,
        private ? \Closure $callback
    ) {
        if ( '' !== $this->route AND 0 !== strpos($this->route, '/') ) {
            $this->route = "/$this->route";
        }

        if ( Route::$grouping_started ) {
            $this->route = "%PREFIX%$this->route";
        }
    }

    function __get($parameter)
    {
        switch ($parameter) {
            case 'method': return $this->method;
            case 'route': return $this->route;
            case 'action': return $this->action;
            case 'controller': return $this->controller;
            case 'callback': return $this->callback;
        }
    }

    function __set($parameter, $value)
    {
        switch ($parameter) {
            case 'method': $this->method = $value; break;
            case 'route': $this->route = $value; break;
            case 'action': $this->action = $value; break;
            case 'controller': $this->controller = $value; break;
            case 'callback': $this->callback = $value; break;
        }
    }

    function bind()
    {
        if ( $this->method AND $this->route ) {

            // duplicate
            if ( array_key_exists($this->route, Route::$routines[$this->method]) ) {
                throw new \Exception("Duplicate route $this->route => " . json_encode(Route::$routines[$this->method][$this->route]));
            }

            if ( $this->action ) {
                Route::$routines[$this->method][$this->route] = [$this->controller, $this->action];
            } elseif ( $this->callback ) {
                Route::$routines[$this->method][$this->route] = $this->callback;
            }
        }
    }

    function unbind()
    {
        if ( $this->method ) {
            $routine = Route::$routins[$this->method];

            foreach ($routine as $route => $controller) {
                if ($route = $this->route) {
                    unset(Route::$routines[$this->method][$this->route]);
                    break;
                }
            }
        }
    }
}