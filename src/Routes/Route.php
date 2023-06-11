<?php
namespace Clicalmani\Flesco\Routes;

use Clicalmani\Flesco\Providers\ServiceProvider;
use Clicalmani\Flesco\Exceptions\MiddlewareException;

class Route {
    
    public static $routines;
    public static $route_middlewares = [];
    public static $registered_guards = [];
    
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
    public static $grouping_started = false;

    public static function currentRoute()
    {
        $url = parse_url(
            $_SERVER['REQUEST_URI']
        );

        $current_route = isset($url['path']) ? $url['path']: '/';
        return $current_route;
    }

    public static function get($route, $callable) 
    { 
        return self::bindRoutine('get', $route, $callable);
    }

    public static function post($route, $callable) {
        return self::bindRoutine('post', $route, $callable);
    }

    public static function patch($route, $callable) {
        return self::bindRoutine('patch', $route, $callable);
    }

    public static function put($route, $callable) {
        return self::bindRoutine('put', $route, $callable);
    }

    public static function options($route, $callable) {
        return self::bindRoutine('options', $route, $callable);
    }

    public static function any($route, $callback) 
    {
        foreach (self::$routines as $method => $arr) {
            self::$routines[$method][$route] = $callback;
        }
    }

    public static function match($matches, $route, $callable)
    {
        if ( ! is_array($matches) ) return;

        $routines = new Routines;

        foreach ($matches as $method) {
            $method = strtolower($method);
            if ( array_key_exists($method, self::$routines) ) {
                $routines[] = self::bindRoutine($method, $route, $callable);
            }
        }

        return $routines;
    }

    public static function group($args, $callback)
    {
        $routes = self::allRoutes();

        /**
         * |--------------------------------------
         * | Start route grouping
         * |----------------------------------------
         * |
         * | Prepend a prefix placeholder to the route (%PREFIX%)
         * | which will be replaced by the correct prefix.
         * |
         * |
         */
        static::$grouping_started = true;

        // Add grouped routes
        $callback();

        static::$grouping_started = false;              // Terminate grouping

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

    public static function delete($route, $callable)
    {
        return self::bindRoutine('delete', $route, $callable);
    }

    public static function resource(string $resource, string $controller = null) : Routines
    {
        $routines = new Routines;

        $routes = [
            'get'    => ['index' => '', 'create' => 'create', 'show' => ':id', 'edit' => ':id/edit'],
            'post'   => ['store' => ''],
            'put'    => ['update' => ':id'],
            'patch'  => ['update' => ':id'],
            'delete' => ['destroy' => ':id']
        ];

        foreach ($routes as $method => $sigs) {
            foreach ($sigs as $action => $sig) {
                $routines[] = self::bindRoutine($method, $resource . '/' . $sig, [$controller, $action]);
            }
        }

        return $routines;
    }

    public static function resources(mixed $resources) : Routines
    {
        $routines = new Routines;

        foreach ($resources as $resource => $controller) {
            $routines->merge(self::resource($resource, $controller));
        }

        return $routines;
    }

    /**
     * Binds resource routes
     * 
     * Query id: comma separated values for resources with multiple keys
     * 
     * @param $resource [mixed] string or array
     * @param $controller [string] string a class extending \Clicalmani\Flesco\Http\Controllers\RequestController::class
     * @return \Clicalmani\Flesco\Routes\Routines
     */
    public static function apiResource(mixed $resource, string $controller = null) : Routines
    {
        $routines = new Routines;

        $routes = [
            'get'    => ['index' => '', 'create' => ':id'],
            'post'   => ['store' => ''],
            'put'    => ['update' => ':id'],
            'patch'  => ['update' => ':id'],
            'delete' => ['destroy' => ':id']
        ];

        foreach ($routes as $method => $sigs) {
            foreach ($sigs as $action => $sig) {
                $routines[] = self::bindRoutine($method, $resource . '/' . $sig, [$controller, $action]);
            }
        }

        $routines->addResource($resource, $routines);

        return $routines;
    }

    public static function apiResources(mixed $resources) : Routines
    {
        $routines = new Routines;

        foreach ($resources as $resource => $controller) {
            $routines->merge(self::apiResource($resource, $controller));
        }

        return $routines;
    }

    public static function allRoutes()
    {
        $routes = [];

        foreach (self::$routines as $routine) {
            foreach ($routine as $route => $controller) {
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

        foreach (self::$routines as $method => $routine) {
            foreach ($routine as $route => $controller) {
                if ( in_array($route, $routes) ) {

                    unset(self::$routines[$method][$route]);

                    if (false == preg_match('/^\//', $route)) {
                        $route = "/$route";
                    }

                    if ( '/api' !== $prefix ) {
                        $route = str_replace('%PREFIX%', $prefix, $route);
                    } else {
                        $route = $prefix . $route;
                    }

                    self::$routines[$method][$route] = $controller;
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
        $routine = self::$routines[$method];
            
        foreach ($routine as $sroute => $controller) {
            
            if ( in_array($sroute, $routes)) continue;               // Exclude route

            if ( !isset(self::$route_middlewares[$sroute]) ) {
                self::$route_middlewares[$sroute]   = [];
                self::$route_middlewares[$sroute][] = $name;
            } else {
                self::$route_middlewares[$sroute][] = $name; 
            }
        }
    }

    public static function getCurrentRouteMiddlewares()
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
     * @deprecated
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
        if (false == preg_match("/\?/", $sroute) AND count($sseq) !== count($nseq)) {
            return -1;
        }

        $strack = $ntrack = '/';

        for($i=0; $i<count($sseq); $i++) {
            $spart = $sseq[$i];
            $npart = isset($nseq[$i]) ? $nseq[$i]: null;
            
            // different parts does not contain parameter
            if ($spart == $npart) continue;
            if ( ! in_array($npart, self::getParamesters($sroute)) AND $npart == $spart ) continue;
            
            // Patterns againts synthetic route
            $patterns = [
                '/^:(\w+(:?.*))[^?]$/',  // :name@{type: number}
                '/^:(\w+)-(\w+)$/', // :from-to
                '/^:(\w+(:?.*))\?$/'      // :optional?
            ];
            
            foreach ($patterns as $index => $pattern) {

                if (preg_match($pattern, $spart)) {

                    // if (false == self::isParameter($npart, $sroute)) continue;
                    
                    if (strpos($spart, '@')) { // Has validators

                        $arr = explode('@', $spart);
                        $validator = json_decode($arr[1]);

                        $param = substr($arr[0], 1);
                        
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
                                if (@preg_match('/^' . $validator->pattern . '$/', $npart)) {
                                    $valid = true;
                                }
                            } elseif (@ $validator->uid) { // Route guard
                                $guard = @ self::$registered_guards[$validator->uid];

                                if ( $guard AND $guard['param'] == $param ) {
                                    $valid = $guard['callback']($npart);
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

                    if (! in_array($npart, self::getParamesters($sroute))) {
                        echo $npart . ' => ' . $sroute . '<br>';
                    }
                    
                    switch($index) {
                        case 0:
                            if (false == self::isEligible($nroute) AND ($npart AND preg_match('/^(\S+)$/', $npart))) {
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

                    // if (false == $matched) {
                        
                    //     $_GET = [];
                        
                    //     return -1;
                    // }
                }

                // In cas there is no parameter
                // the different parts should match
                // Escape optional parameters
                if (false == preg_match("/\?$/", $spart) AND $spart != $npart) {
                    return -1;
                }
            }
        }
        
        return 0;
    }

    static function exists($method = null)
    {
        $alpha = self::getAlpha($method);
        $sroute = self::compareSequences($alpha);
        
        if ($sroute) {
            self::$current_route = $sroute;
            return $sroute;
        }

        return false;
    }

    /**
     * @deprecated
     */
    private static function isEligible($route)
    {
        foreach (self::$routines as $routine) {
            foreach ($routine as $sroute => $controller) {
                if ($sroute == $route) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @deprecated
     */
    private static function getParamesters($sroute)
    {
        $sseq = preg_split('/\//', $sroute, -1, PREG_SPLIT_NO_EMPTY);
        
        $params = [];

        foreach ($sseq as $seq) {
            if (preg_match('/^:/', $seq)) {
                $arr = explode('@', $seq);
                $params[] = substr($arr[0], 1);
            }
        }
        
        return $params;
    }

    /**
     * @deprecated
     */
    private static function isParameter($part, $sroute)
    {
        return ! in_array($part, self::getParamesters($sroute));
    }

    /**
     * @deprecated
     */
    private static function isPartiallyElligible($ntrack)
    {
        foreach (self::$routines as $routine) {
            foreach ($routine as $sroute => $controller) {
                if (strpos($sroute, $ntrack) == 0) {
                    return true;
                }
            }
        }
    }

    /**
     * @deprecated
     */
    private static function bindRoutine(string $method, string $route, mixed $callable, bool $bind = true) : mixed
    {
        if ( is_array($callable) AND count($callable) == 2 ) {
            $routine = new Routine($method, $route, $callable[1], $callable[0], null);
        } elseif ( is_string($callable) ) {
            $routine = new Routine($method, $route, 'invoke', $callable, null);
        } elseif ( 'Closure' === get_class($callable) ) {
            $routine = new Routine($method, $route, null, null, $callable);
        }
        
        if ( isset($routine) AND $bind ) {
            $routine->bind();
            return $routine;
        }

        return null;
    }

    public static function getController($method, $route)
    {
        return self::$routines[$method][$route];
    }

    /**
     * Explodes route against back slashes (/)
     * 
     * @param $route [string] the route to explode
     * @return array result
     */
    private static function getSequence($route)
    {
        $seq = preg_split('/\//', $route, -1, PREG_SPLIT_NO_EMPTY);

        return collection()->exchange($seq)->map(function($part) {
            return explode('@', $part)[0];
        })->toArray();
    }

    /**
     * Computes routes with same length as the current route
     * 
     * The recipe here is to grab the most relevant routes, to avoid searching all the mesh
     * The current route will be compared to each of the selected routes
     * We first search for differences by comparing all the part broked against back slashes (/)
     * that allows us to find easily route parameters for the first try (that means some sequence may not be probabily a parameter)
     * that's not a matter for now because will deal with them later.
     * After replacing all the parameters, will try to rebuild the route and then compare it to the current route.
     * 
     * @param $method [string] Request method
     * @return Array containing the routes
     */
    private static function getAlpha($method)
    {
        $alpha = [];

        $nseq = self::getSequence( current_route() );
        
        $len     = count($nseq);
        $routine = self::$routines[$method];
            
        foreach ($routine as $sroute => $controller) {
            $sseq = self::getSequence($sroute);

            if ($len !== count($sseq)) continue;

            $alpha[$sroute] = $sseq;
        }

        return $alpha;
    }

    /**
     * Each route is exploded against back slash (/), that allows to find easily the different parts of the route
     * 
     * @param $alpha [array] an array of the routes with the same length as the current route
     * @return mixed string on success or false on failure
     */
    private static function compareSequences($alpha)
    {
        $nseq  = self::getSequence( current_route() );
        $beta  = [];
        
        foreach ($alpha as $sroute => $sseq) {
            if ( self::isSameRoute( self::build($sroute, $sseq) ) ) {
                $beta[$sroute] = $sseq;
            } else unset($beta[$sroute]);
        }

        // Find real params (indexes and values)
        $a = array_diff($nseq, ...array_values($beta));
        
        if ( count($beta) == 1) {
            // $bench = new \Clicalmani\Flesco\TestUnits\Benchmark;
            // $bench->watchValue(json_encode([current_route(), $a]));
            $sroute = array_keys($beta)[0];
            $sseq   = $beta[$sroute];

            foreach ($a as $key => $value) {
                self::registerParameter($sseq[$key], $value);
            }

            return $sroute;
        }
        
        if ( !empty($a) ) {
            $params_keys = [];
            
            foreach ($a as $key => $value) {
                foreach ($beta as $sroute => $sseq) {
                    // $pos = array_search($value, $nseq);
                    $b = array_splice($sseq, $key, 1, $value);
                    if (self::isSameRoute($sseq)) {
                        self::registerParameter($b[0], $value);
                        return $sroute;
                    }
                }
            }
        } else {
            foreach ($beta as $sroute => $sseq) {
                if (self::isSameRoute($sseq)) return $sroute;
            }
        }
        
        return false;
    }

    /**
     * Builds route parts to be comparable to the current route. This method is capable of finding 
     * the exact parameters passed to the route. It replace each parameter to the appropriate position 
     * for it to be comparable to the current route.
     * 
     * @param $sroute [string] The syntical route to be matched with
     * @param $sseq [array] The route sequences 
     * @see \Clicalmani\Flesco\Routes::getSequence for mor details
     * @return array different sequences of the route after parameters replaced.
     */
    private static function build($sroute, $sseq)
    {
        $nseq  = self::getSequence( current_route() );
        $beta  = [];
        
        foreach ($nseq as $index => $part) {

            if ( in_array($sseq[$index], array_diff($sseq, $nseq)) ) {
                $beta[] = $part . '@' . $index;
            }
        }

        if (empty($beta)) return $nseq;
        
        foreach ($beta as $param) {
            $arr = explode('@', $param);
            $param = $arr[0];
            $index = $arr[1];

            if (preg_match('/^:/', $sseq[$index])) {
                array_splice($sseq, $index, 1, $param);
            }
        }
        
        return $sseq;
    }

    /**
     * compares to the current route
     * 
     * @param $sequences [array] 
     * @see \Clicalmani\Flesco\Routes::getSequence for mor details
     * @return boolean true on success, of false on failure.
     */
    private static function isSameRoute($sequences)
    {
        return '/' . join('/', $sequences) == current_route();
    }

    private static function registerParameter($name, $value)
    {
        $_REQUEST[substr($name, 1)] = $value;
    }
}
