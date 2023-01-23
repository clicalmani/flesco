<?php
namespace Clicalmani\Flesco\Http\Controllers;

use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\Http\Requests\HttpRequest;
use Clicalmani\Flesco\Routes\Route;
use Clicalmani\Flesco\Exceptions\HttpRequestException;
use Clicalmani\Flesco\Exceptions\MethodNotFoundException;
use Clicalmani\Flesco\Exceptions\RouteNotFoundException;

require_once dirname( dirname( __DIR__ ) ) . '/bootstrap/index.php';

abstract class RequestController extends HttpRequest 
{
	static $controller;
	static $route;

	public static function render()
	{
		$request = new Request;
		$request->checkCSRFToken();
		
		$controller = self::getController();
		
		if (is_array($controller) AND !empty($controller)) {
			$class = $controller[0];
		} elseif ($controller instanceof \Closure) {
			return self::getRoutine(
				new Request([])
			);
		}
		
		if (isset($class) AND class_exists($class)) {

			$obj = new $class();
			
			if(method_exists($obj, 'validate')) {
				return self::getRoutine(
					new Request(
						$obj->{'validate'}()
					)
				);
			}

			return self::getRoutine(
				new Request([])
			);
		}
		
		throw new MethodNotFoundException();
	}

    public static function getController() 
	{
		if ( isset( self::$controller ) ) {
			return self::$controller;
		}
		
		foreach (Route::$rountines as $method => $data) {
			if ($route = Route::exists($method)) { 

				$middlewares = Route::getCurrentRouteMiddlewares();
				
				self::$route      = $route;
				self::$controller = $data[$route];

				$request = new Request([]);
				
				if ('api' === Route::getGateway()) {
					if ( is_array(self::$controller) AND isset(self::$controller[0]) AND $obj = new self::$controller[0]) {
						$request = new Request(
							$obj->{'validate'}()
						);
					}

					if ( isset($middlewares) AND Route::isCurrentRouteAuthorized($request) == false ) {
						http_response_code(401);		// Unauthorized
						exit;
					}
				}
				
				if ( isset($middleware) AND Route::isCurrentRouteAuthorized($request) == false ) {
					http_response_code(401);
					exit;
				}
				
				return self::$controller;
			}
		}
		
		http_response_code(404);		// Not Found
		exit;
    }

	public static function getRoutine($request)
	{
		$controller = self::getController();
		
		/**
		 * Checks for controller
		 */
		if (is_array($controller) AND !empty($controller)) {
			
			$class = $controller[0];
			$obj   = new $class;                                             // An instance of the controller
			
			if (method_exists($obj, $controller[1])) {

				/**
				 * Call controller method
				 */
				$reflect = new \ReflectionMethod($class, $controller[1]);

				$method_parameters = $reflect->getParameters();             // Controller method parameters

				// Check first parameter (Request object)
				if ( isset($method_parameters[0]) ) {
					$first_param_type = $method_parameters[0]->getType();  // Get method first parameter
				                                                    	   // Which correspond to request object
																		   // null if no parameter
				}
				
				if (isset($first_param_type)) {

					$requestClass = $first_param_type->getName();
					$ro = new $requestClass([]);                           // Request objet or an instance
					                                                       // of class extending Request

					$ro->validate();                                       // Call validate method
					
					/**
					 * Appends route parameters to controller method
					 */

					$method_parameters_names = [];
					unset($method_parameters[0]); // Unset first parameter

					foreach ($method_parameters as $param) {
						$method_parameters_names[] = $param->getName();
					}

					// Get parameters names
					$mathes = [];
					preg_match('/:[^\/]+/', self::$route, $mathes);
					
					$parameters = [];

					// Parameters provided values through HttpRequest
					foreach ($mathes as $name) {
						$name = substr($name, 1);
						if (isset($_REQUEST[$name]) AND in_array($name, $method_parameters_names)) {
							$parameters[] = $_REQUEST[$name];
						}
					}
					
					// Call controller whith a Request object
					if (count($method_parameters) === count($parameters) ) {
						return $obj->{$controller[1]}($ro, ...$parameters);  
					}
					
					throw new \ArgumentCountError("Too few arguments");
				}
				
				return $obj->{$controller[1]}($request);
			}
		} elseif ($controller instanceof \Closure) {                      // Otherwise fallback to closure function
			                                                              // whith a default Request object
			return $controller($request);
		}
		
		http_response_code(403);		// Forbidden
		exit;
	}
}