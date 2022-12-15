<?php
namespace Clicalmani\Flesco\Routes;

use Clicalmani\Flesco\Providers\ServiceProvider;
use Clicalmani\Flesco\Exceptions\MiddlewareException;

class Route {
    
    public static $rountines;
    public static $route_middleware = [];

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

    public static function middleware($name)
    {
        if ( ! isset(ServiceProvider::$providers['middleware']['web'][$name]) ) throw new MiddlewareException('Middleware can not be found');
        
        $middleware = new ServiceProvider::$providers['middleware']['web'][$name];

        if ( ! method_exists( $middleware, 'handler') ) throw new MiddlewareException('Handler method not provided');
        if ( ! method_exists( $middleware, 'authorize') ) throw new MiddlewareException('Authorize method not provided');

        // Routes before middleware
        $routes = [];

        foreach (self::$rountines as $rountine) {
            foreach ($rountine as $route => $controller) {
                $routes[] = $route;
            }
        }

        // Register middleware routes
        $handler = $middleware->handler();

        if ( file_exists( $handler ) ) {
            include_once $handler;
        } else {
            throw new MiddlewareException('Can not find handler provided');
        }

        if ( ! in_array(current_route(), $routes) ) {

            /**
             * Check if the current route is part of the middleware routes
             */
            foreach (self::$rountines as $rountine) {
                foreach ($rountine as $route => $controller) {
                    if ( current_route() == $route AND $middleware->authorize() == false ) {
                        self::$route_middleware[current_route()] = $name;
                    }
                }
            }
        }
    }

    static function getCurrentRouteMiddleware()
    {
        if ( isset(self::$route_middleware[current_route()]) ) {
            return self::$route_middleware[current_route()];
        }

        return null;
    }

    /**
     * Verfify if current rout is behind a middleware
     */
    static function isCurrentRouteAuthorized()
    {
        $name = self::getCurrentRouteMiddleware();
        $middleware = new ServiceProvider::$providers['middleware']['web'][$name];
        return $middleware->authorize();
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

        // If there is not parameters the two route match in structure.
        if ($sroute == $nroute) {
            return 0;
        }

        $sseq = preg_split('/\//', $sroute, -1, PREG_SPLIT_NO_EMPTY);
        $nseq = preg_split('/\//', $nroute, -1, PREG_SPLIT_NO_EMPTY);
        
        // The two routes should have same number of sequences
        if (count($sseq) !== count($nseq)) {
            return -1;
        }
        
        for($i=0; $i<count($sseq); $i++) {
            $spart = $sseq[$i];
            $npart = $nseq[$i];
            
            // different parts does not contain parameter
            if ($spart == $npart) continue;
            
            // Patterns againts synthetic route
            $patterns = [
                '/^:(\w+)$/',       // :name
                '/^:(\w+)-(\w+)$/'  // :from-to
            ];
            
            foreach ($patterns as $index => $pattern) {
                
                if (preg_match($pattern, $spart)) {
                    
                    switch($index) {
                        case 0:
                            if (preg_match('/^(\S+)$/', $npart)) {
                                $_GET[substr($spart, 1)] = $npart;
                                $_REQUEST = $_GET;
                                continue 3;
                            }  
                            
                            return -1;
                        break;

                        case 1:
                            if (preg_match('/^([0-9\.,-]+)$/', $npart)) {
                                $na = explode('-', $npart);
                                $sa = explode('-', substr($spart, 1));
                                $_GET[$sa[0]] = $na[0];
                                $_GET[$sa[1]] = $na[1];
                                $_REQUEST = $_GET;
                                continue 3;
                            } 

                            return -1;
                        break;
                    }
                }

                // In cas there is no parameter
                // the different parts should match
                if ($spart != $npart) {
                    return -1;
                }
            }
        }
        
        return 0;
    }

    static function exists($nroute, $method = null)
    {
        if ( isset($method) ) {
            $rountine = self::$rountines[$method];

            foreach ($rountine as $sroute => $controller) {
                if (-1 !== self::compare($sroute, $nroute)) {
                    return $sroute;
                }
            }
        } else {
            foreach (self::$rountines as $rountine) {
                foreach ($rountine as $sroute => $controller) {
                    if (-1 !== self::compare($sroute, $nroute)) {
                        return $sroute;
                    }
                }
            }
        }
        
        return false;
    }
}