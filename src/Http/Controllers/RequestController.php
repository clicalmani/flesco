<?php
namespace Clicalmani\Flesco\Http\Controllers;

use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\Http\Requests\HttpRequest;
use Clicalmani\Flesco\Routes\Route;
use Clicalmani\Flesco\Routes\Routine;
use Clicalmani\Flesco\Routes\Routines;
use Clicalmani\Flesco\Exceptions\HttpRequestException;
use Clicalmani\Flesco\Exceptions\MethodNotFoundException;
use Clicalmani\Flesco\Exceptions\RouteNotFoundException;
use Clicalmani\Flesco\Exceptions\ModelNotFoundException;

require_once dirname( dirname( __DIR__ ) ) . '/bootstrap/index.php';

abstract class RequestController extends HttpRequest 
{
	static $controller;
	static $route;

	public static function render()
	{
		$request = new Request;
		$request->checkCSRFToken();

		die(self::getRoutine($request));
	}

    public static function getController() 
	{
		if ( isset( self::$controller ) ) {
			return self::$controller;
		}
		
		$request = new Request([]);
		$method = $request->getMethod();
		
		if ($route = Route::exists($method)) { 
			
			$middlewares = Route::getCurrentRouteMiddlewares();
			
			self::$route      = $route;
			self::$controller = Route::getController($method, $route);
			
			if ( isset($middlewares) AND Route::isCurrentRouteAuthorized($request) == false ) {
				response()->status(401, 'UNAUTHORISED_REQUEST_ERROR', 'Request Unauthorized');		// Unauthorized
				exit;
			}
			
			return self::$controller;
		}
		
		throw new RouteNotFoundException;
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
			
			if ( @ isset($controller[1]) ) {

				if ( ! method_exists($obj, $controller[1]) ) {
					response()->status(500, 'INTERNAL_SERVER_ERROR', 'Method ' . $controller[1] . ' does not exist on class ' . $controller[0]);		// Forbiden
					exit;
				}

				return self::invokeControllerMethod($class, $controller[1]);
			}

			return self::invokeControllerMethod($class);

		} elseif( is_string($controller) ) {

			return self::invokeControllerMethod($controller);			  // Controller with magic method invoke

		} elseif ($controller instanceof \Closure) {                      // Otherwise fallback to closure function
			                                                              // whith a default Request object
			return $controller($request);
		}
		
		response()->status(403, 'FORBIDEN_REQUEST_ERROR', 'Request Forbiden');		// Forbiden
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

			// Model binding request
			if ( self::isModelBind($requestClass) ) {
				try {
					return self::bindModel($requestClass, $controllerClass, $method);
				} catch(ModelNotFoundException $e) {

					$resource = self::getResource();

					if ( array_key_exists('missing', $resource->methods) ) {
						return $resource->methods['missing']['caller']();
					}

					return response()->status(404, 'NOT_FOUND', $e->getMessage());		// Not Found
				}
			}

			$request = new $requestClass([]);                       // Request objet or an instance
																	// of class extending Request

			if (method_exists($request, 'authorize')) {
				if (false == $request->authorize()) {
					return response()->status(403, 'FORBIDEN', 'Unauthorized Request');		// Forbiden
				}
			}

			if (method_exists($request, 'prepareForValidation')) {
				$request->prepareForValidation();                    // Call prepareForValidation method
			}
			
			if (method_exists($request, 'signatures')) {
				$request->signatures();                             // Call validate method
			}
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

			$parameters = self::getParameters($request);

			if ( $parameters ) {
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

	private static function getParameters($request)
    {
        preg_match_all('/:[^\/]+/', self::$route, $mathes);

        $parameters = [];
        
        if ( count($mathes) ) {

            $mathes = $mathes[0];
            
            foreach ($mathes as $name) {
                $name = substr($name, 1);    				      // Remove starting two dots (:)
                
                if (preg_match('/@/', $name)) {
                    $name = substr($name, 0, strpos($name, '@')); // Remove validation part
                }
                
                if ($request->{$name}) {
                    $parameters[] = $request->{$name};
                }
            }
        }

        return $parameters;
    }

	private static function isModelBind($model)
	{
		return is_subclass_of($model, \Clicalmani\Flesco\Models\Model::class);
	}

	private static function bindModel($model, $controller, $method)
	{
		$request = new Request;
		$obj     = new $model;

		// Primary keys
		$keys = $obj->getKey();

		if ( in_array($method, ['create', 'show', 'update', 'destroy']) ) {

			// Request parameters
			$parameters = explode(',', $request->id);
			
			if ( count($parameters) ) {
				if ( count($parameters) === 1 ) $parameters = $parameters[0];	// Single primary key
				
				$obj = new $model($parameters);
				
				/**
				 * Bind resources
				 */
				self::bindResources($method, $obj);
				
				$collection = $obj->get();

				if ( $collection->isEmpty() ) throw new ModelNotFoundException($model);
				
				return (new $controller)->{$method}($obj, new Request);

			} else throw new ModelNotFoundException($model);
		}

		$obj = new $model;

		/**
		 * Bind resources
		 */
		self::bindResources($method, $obj);
		
		return (new $controller)->{$method}($obj, new Request);
	}

	private static function bindResources($method, $obj)
	{
		$resource = self::getResource();
		
		if ( $resource ) {

			/**
			 * Select distinct
			 */
			self::getResourceDistinct($resource, $method, $obj);

			/**
			 * Insert ignore
			 */
			self::createResourceIgnore($resource, $method, $obj);

			/**
			 * Join to other models
			 */
			self::resourceJoin($resource, $method, $obj);

			/**
			 * Delete multiple
			 */
			self::resourceDeleteFrom($resource, $method, $obj);

			/**
			 * Pagination
			 */
			self::resourceCalcRows($resource, $method, $obj);

			/**
			 * Limit rows
			 */
			self::resourceLimit($resource, $method, $obj);

			/**
			 * Row offset
			 */
			self::resourceOffset($resource, $method, $obj);

			/**
			 * Row order by
			 */
			self::resourceOrderBy($resource, $method, $obj);
		}
	}

	private static function getResource()
	{
		// Resource
		$sseq = preg_split('/\//', self::$route, -1, PREG_SPLIT_NO_EMPTY);
		$resource = Route::getGateway() == 'api' ? $sseq[1]: $sseq[0];
		
		if ( array_key_exists($resource, Routines::$resources) ) {
			return (object) Routines::$resources[$resource];
		}

		return null;
	}

	private static function getResourceDistinct($resource, $method, $obj)
	{
		if ( $method == 'index' AND array_key_exists('distinct', $resource->properties) ) {
			$obj->distinct($resource->properties['distinct']);
		}
	}

	private static function createResourceIgnore($resource, $method, $obj)
	{
		if ( $method == 'create' AND array_key_exists('ignore', $resource->properties) ) {
			$obj->ignore($resource->properties['ignore']);
		}
	}

	private static function resourceJoin($resource, $method, $obj)
	{
		$methods = ['index', 'create', 'show', 'edit', 'update', 'destroy'];

		if ( in_array($method, ['index', 'show', 'update']) AND array_key_exists('joints', $resource->joints) ) {
			foreach ($resource->joints as $joint) {
				$stack = [];

				if ( $joint['includes'] ) $stack = $joint['includes'];
				else $stack = $methods;

				if ( $joint['excludes'] ) $stack = array_diff($joint['excludes'], $stack);

				if ( in_array($method, $stack) ) 
					$obj->join($joint['class'], $joint['foreign'], $joint['original']);
			}
		}
	}

	private static function resourceDeleteFrom($resource, $method, $obj)
	{
		if ( $method == 'destroy' AND array_key_exists('from', $resource->properties) ) {
			$obj->from($resource->properties['from']);
		}
	}

	private static function resourceCalcRows($resource, $method, $obj)
	{
		if ( $method == 'index' AND array_key_exists('calc', $resource->properties) ) {
			$obj->calcRoundRows($resource->properties['calc']);
		}
	}

	private static function resourceLimit($resource, $method, $obj)
	{
		if ( $method == 'index' AND array_key_exists('limit', $resource->properties) ) {
			$obj->limit($resource->properties['limit']);
		}
	}

	private static function resourceOffset($resource, $method, $obj)
	{
		if ( $method == 'index' AND array_key_exists('offset', $resource->properties) ) {
			$obj->offset($resource->properties['offset']);
		}
	}

	private static function resourceOrderBy($resource, $method, $obj)
	{
		if ( $method == 'index' AND array_key_exists('order_by', $resource->properties) ) {
			$obj->orderBy($resource->properties['order_by']);
		}
	}
}