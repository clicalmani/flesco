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
			
			if ( @ isset($controller[1]) AND method_exists($obj, $controller[1])) {
				return self::invokeControllerMethod($class, $controller[1]);
			}

			return self::invokeControllerMethod($class);

		} elseif( is_string($controller) ) {

			return self::invokeControllerMethod($controller);			  // Controller with magic method invoke

		} elseif ($controller instanceof \Closure) {                      // Otherwise fallback to closure function
			                                                              // whith a default Request object
			return $controller($request);
		}
		
		http_response_code(403);		// Forbidden
		exit;
	}

	public static function invokeControllerMethod($controllerClass, $method = 'invoke')
	{
		/**
		 * Call controller method
		 */
		$reflect = new \ReflectionMethod($controllerClass, $method);

		$method_parameters = $reflect->getParameters();   // Controller method parameters
		$request = new Request;							  // Fallback to default request
		Request::$current_request = $request;

		// Check first parameter (Request object)
		// Method accepts request object
		$first_param = @ $method_parameters[0];
		$first_param_type = null;

		if ( $first_param ) {
			$first_param_type = $first_param->getType();  // Get method first parameter
														  // Which correspond to request object
														  // null if no parameter
		}

		/**
		 * Validate request
		 */
		if ( $first_param_type ) {
			
			$requestClass = $first_param_type->getName();
			$request = new $requestClass([]);                       // Request objet or an instance
																	// of class extending Request

			$request->validate();                                   // Call validate method
		}

		/**
		 * Appends route parameters to route method
		 */
		$method_parameters_names = [];
		unset($method_parameters[0]); 							   // Remove first parameter
																   // Request object
		
		$obj = new $controllerClass;

		if ( count($method_parameters) ) {

			foreach ($method_parameters as $param) {
				$method_parameters_names[] = $param->getName();
			}
		
			// Get parameters names
			// Current route parameters
			$mathes = [];
			preg_match_all('/:[^\/]+/', self::$route, $mathes);
			
			if ( count($mathes) ) {

				$mathes = $mathes[0];
				$parameters = [];

				// Parameters provided values through HttpRequest
				foreach ($mathes as $name) {
					$name = substr($name, 1);    				  // Remove starting two dots (:)
					$name = substr($name, 0, strpos($name, '@')); // Remove validation part
					
					if ($request->{$name} AND in_array($name, $method_parameters_names)) {
						$parameters[] = $request->{$name};
					}
				}
				
				// Call controller whith a Request object
				if (count($method_parameters) === count($parameters) ) {
					
					return $obj->{$method}($request, ...$parameters);
				}
				
				throw new \ArgumentCountError("Too few arguments");
			}
		}

		/**
		 * Method does not support request parameters
		 */
		return $obj->{$method}($request);
	}
}