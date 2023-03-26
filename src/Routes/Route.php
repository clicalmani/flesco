<?php
namespace Clicalmani\Flesco\Routes;

use Clicalmani\Flesco\Providers\ServiceProvider;
use Clicalmani\Flesco\Exceptions\MiddlewareException;

class Route {
    
    public static $rountines;
    public static $route_middlewares = [];
    
    private const PARAM_TYPES = [
        'numeric',
        'int',
        'integer',
        'float',
        'string'
    ];
    private const VALIDATORS = [
        'pattern',
        'enum'
    ];
    public static $current_route;

    public static function currentRoute()
    {
        $url = parse_url(
            $_SERVER['REQUEST_URI']
        );

        $current_route = isset($url['path']) ? $url['path']: '/';
        return $current_route;
    }

    public static function get($route, $calback) 
    { 
        self::$rountines['get'][$route] = $calback;
    }

    public static function post($route, $callback) {
        self::$rountines['post'][$route] = $callback;
    }

    public static function options($route, $callback) {
        self::$rountines['options'][$route] = $callback;
    }

    public static function any($route, $callback) 
    {
        foreach (self::$rountines as $method => $arr) {
            self::$rountines[$method][$route] = $callback;
        }
    }

    public static function match($matches, $route, $callback)
    {
        if ( ! is_array($matches) ) return;

        foreach ($matches as $method) {
            $method = strtolower($method);
            if ( array_key_exists($method, self::$rountines) ) {
                self::$rountines[$method][$route] = $callback;
            }
        }
    }

    public static function group($args, $callback)
    {
        $routes = self::allRoutes();

        // Add grouped routes
        $callback();

        $grouped_routes = array_diff(self::allRoutes(), $routes);
        
        /**
         * Prefix routes
         */
        if ( isset($args['prefix']) AND $prefix = $args['prefix']) {
            self::setPrefix($grouped_routes, $prefix);
            return;
        }

        /**
         * Middleware
         */
        if ( isset($args['middleware']) AND $name = $args['middleware']) {
            self::middleware($name);
            $callback();
        }
    }

    public static function delete($route, $callback)
    {
        self::$rountines['delete'][$route] = $callback;
    }

    public static function allRoutes()
    {
        $routes = [];

        foreach (self::$rountines as $rountine) {
            foreach ($rountine as $route => $controller) {
                $routes[] = $route;
            }
        }

        return $routes;
    }

    public static function setPrefix($routes, $prefix)
    {
        if ( is_string($routes) ) {
            $routes = [$routes];
        }

        foreach (self::$rountines as $method => $rountine) {
            foreach ($rountine as $route => $controller) {
                if ( in_array($route, $routes) ) {
                    unset(self::$rountines[$method][$route]);
                    if (false == preg_match('/^\//', $route)) {
                        self::$rountines[$method][$prefix . '/' . $route] = $controller;
                    } else {
                        self::$rountines[$method][$prefix . $route] = $controller;
                    }
                }
            }
        }
    }

    public static function getGateway()
    {
        $gateway = 'web';

        if (preg_match('/^\/api/', current_route())) {
            $gateway = 'api';
        }

        return $gateway;
    }

    public static function middleware($name) 
    {
        if ( self::isMiddleware($name) ) {

            $gateway = self::getGateway();
            $middleware = new ServiceProvider::$providers['middleware'][$gateway][$name];
            
            self::registerMiddleware($middleware, $name);
        }
    }

    private static function isMiddleware($name)
    {
        $gateway = self::getGateway();

        if ( ! isset(ServiceProvider::$providers['middleware'][$gateway][$name]) ) 
            throw new MiddlewareException('Middleware can not be found');
        
        $middleware = new ServiceProvider::$providers['middleware'][$gateway][$name];
        
        /**
         * This allows to verify whether the current middleware inherited from Middleware class
         */
        if ( ! method_exists( $middleware, 'handler') ) 
            throw new MiddlewareException('Handler method not provided');
        if ( ! method_exists( $middleware, 'authorize') ) 
            throw new MiddlewareException('Authorize method not provided');

        return true;
    }

    private static function registerMiddleware($middleware, $name)
    {
        // Routes to exclude in the middleware
        $routes = self::allRoutes();

        // Register middleware routes
        $handler = $middleware->handler();

        if (false != $handler) {
            if ( file_exists( $handler ) ) {
                include_once $handler;
            } else {
                throw new MiddlewareException('Can not find handler provided');
            }
        }

        $method = strtolower( $_SERVER['REQUEST_METHOD'] );
        $rountine = self::$rountines[$method];
            
        foreach ($rountine as $sroute => $controller) {
            
            if ( in_array($sroute, $routes)) continue;               // Exclude route

            if ( !isset(self::$route_middlewares[$sroute]) ) {
                self::$route_middlewares[$sroute]   = [];
                self::$route_middlewares[$sroute][] = $name;
            } else {
                self::$route_middlewares[$sroute][] = $name; 
            }
        }
    }

    static function getCurrentRouteMiddlewares()
    {
        $current_route = self::$current_route;
        
        if ( 'api' === self::getGateway() ) {
            if ( strpos(self::$current_route, 'api') === 1 ) {
                $current_route = substr(self::$current_route, 4);           // Remove api prefix
            }
        }

        if ( array_key_exists($current_route, self::$route_middlewares) ) {
            return self::$route_middlewares[$current_route];
        }

        return null;
    }

    /**
     * Verfify if current route is behind a middleware
     */
    static function isCurrentRouteAuthorized($request = null)
    {
        $gateway = self::getGateway();
        $authorize = true;

        if ($names = self::getCurrentRouteMiddlewares()) {
            foreach ($names as $name) {
                $middleware = new ServiceProvider::$providers['middleware'][$gateway][$name];
                $authorize = $middleware->authorize(
                    ( $gateway === 'web' ) ? ( new Request() )->user(): $request
                );

                if (false == $authorize) {
                    return false;
                }
            }
        }
        
        return $authorize;
    }

    /**
     * Comapre two routes
     * @param [string] $sroute Synthetic route
     * @param [string] $nroute Navigation route
     * @return [integer] 
     * returns 0 if matched, otherwise -1
     */
    static function compare($sroute, $nroute)
    {
        // Home
        if ($nroute == '/' AND $sroute != '/') {
            return -1;
        }
        
        // If there is no parameters the two route match in structure.
        if ($sroute == $nroute) {
            return 0;
        }

        $sseq = preg_split('/\//', $sroute, -1, PREG_SPLIT_NO_EMPTY);
        $nseq = preg_split('/\//', $nroute, -1, PREG_SPLIT_NO_EMPTY);
        
        // The two routes should have same number of sequences
        // if there is no optional parameters
        if (false == strpos($sroute, '?') AND count($sseq) !== count($nseq)) {
            return -1;
        }

        $strack = $ntrack = '/';

        for($i=0; $i<count($sseq); $i++) {
            $spart = $sseq[$i];
            $npart = isset($nseq[$i]) ? $nseq[$i]: null;
            
            // different parts does not contain parameter
            if ($spart == $npart) continue;
            
            // Patterns againts synthetic route
            $patterns = [
                '/^:(\w+(:?.*))$/',  // :name@{type: number}
                '/^:(\w+)-(\w+)$/', // :from-to
                '/^:(\w+)\?$/'      // :optional?
            ];
            
            foreach ($patterns as $index => $pattern) {
                
                if (preg_match($pattern, $spart)) {
                    
                    if (strpos($spart, '@')) { // Has validators

                        $arr = explode('@', $spart);
                        $validator = json_decode($arr[1]);
                        
                        if ($validator) {

                            $valid = false;

                            if (@ $validator->type) {

                                if (in_array(@ $validator->type, self::PARAM_TYPES)) {
                                    switch($validator->type) {
                                        case 'numeric':
                                            if (is_numeric($npart)) {
                                                $valid = true;
                                            }
                                        break;

                                        case 'int':
                                        case 'integer':
                                            if (is_int($npart)) {
                                                $valid = true;
                                            }
                                        break;

                                        case 'float':
                                            if (is_float($npart)) {
                                                $valid = true;
                                            }
                                        break;

                                        case 'string':
                                            if (is_string($npart)) {
                                                $valid = true;
                                            }
                                        break;
                                    }
                                }
                            } elseif(@ $validator->enum) {
                                $enum = explode(',', $validator->enum);

                                if (in_array($npart, $enum)) {
                                    $valid = true;
                                }
                            } elseif(@ $validator->pattern) {
                                if (@preg_match('/' . $validator->pattern . '/', $npart)) {
                                    $valid = true;
                                }
                            } 

                            if (false === $valid) {
                                return -1;
                            }
                        }

                        $spart = $arr[0]; // Remove validation part
                    }

                    $ntrack .= "/$npart";
                    $strack .= "/$spart";
                    $matched = true;
                    
                    switch($index) {
                        case 0:
                            if (false == self::isEligible($nroute) AND preg_match('/^(\S+)$/', $npart)) {
                                $param = substr($spart, 1);
                                $_GET[$param] = $npart;
                                $_REQUEST[$param] = $npart;
                                continue 3;
                            }  
                            
                            $matched = false;
                        break;

                        case 1:
                            if (false == self::isEligible($nroute) AND preg_match('/^(\w+)-(\w+)$/', $npart)) {
                                $na = explode('-', $npart);
                                $sa = explode('-', substr($spart, 1));
                                $from = $sa[0];
                                $to = $sa[1];
                                $_GET[$from] = $na[0];
                                $_GET[$to] = $na[1];
                                $_REQUEST[$from] = $na[0];
                                $_REQUEST[$to] = $na[1];
                                continue 3;
                            }

                            $matched = false;
                        break;

                        case 2:
                            if (false == self::isEligible($nroute) AND (!$npart OR preg_match('/^(.*)$/', $npart))) {
                                $param = rtrim(ltrim($spart, ':'), '?');
                                $_GET[$param] = $npart;
                                $_REQUEST[$param] = $npart;
                                continue 3;
                            } 

                            $matched = false;
                        break;
                    }

                    if (false == $matched) {
                        
                        $_GET = [];

                        return -1;
                    }
                }

                // In cas there is no parameter
                // the different parts should match
                // Escape optional parameters
                if (false == strpos($spart, '?') AND $spart != $npart) {
                    return -1;
                }
            }
        }
        
        return 0;
    }

    static function exists($method = null)
    {
        if ( isset($method) ) {
            $rountine = self::$rountines[$method];
            
            foreach ($rountine as $sroute => $controller) {
                if (-1 !== self::compare($sroute, current_route())) {
                    self::$current_route = $sroute;
                    return $sroute;
                }
            }
        } else {
            foreach (self::$rountines as $rountine) {
                foreach ($rountine as $sroute => $controller) {
                    if (-1 !== self::compare($sroute, current_route())) {
                        self::$current_route = $sroute;
                        return $sroute;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Route without parameters and route with parameter that resemble in structure with same length
     * have the same priority. Ex: api/rooms/add and api/rooms/:promo. To differenciate them, we prioritize
     * route without parameter.
     * 
     * @param $route [string] 
     * @return Boolean true on success, false on failure.
     */
    private static function isEligible($route)
    {
        foreach (self::$rountines as $rountine) {
            foreach ($rountine as $sroute => $controller) {
                if ($sroute == $route) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Partially match a route with a subroute. This will allow to prioritize route without param at a given position
     * over route route with param at a given position. Ex: /rooms/:name/teachers and /room/name/teachers
     * 
     * @param $strack [string]
     * @return Boolean true on success, false on failure.
     */
    private static function isPartiallyElligible($ntrack)
    {
        foreach (self::$rountines as $rountine) {
            foreach ($rountine as $sroute => $controller) {
                if (strpos($sroute, $ntrack) == 0) {
                    return true;
                }
            }
        }
    }
}