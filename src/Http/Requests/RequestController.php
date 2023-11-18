<?php
namespace Clicalmani\Flesco\Http\Requests;

use Clicalmani\Routes\Route;
use Clicalmani\Routes\Routing;
use Clicalmani\Routes\ResourceRoutines;
use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\Http\Requests\HttpRequest;
use Clicalmani\Routes\Exceptions\RouteNotFoundException;
use Clicalmani\Flesco\Exceptions\ModelNotFoundException;
use Clicalmani\Flesco\Models\Model;
use Clicalmani\Flesco\Providers\RouteServiceProvider;
use Clicalmani\Flesco\Support\Log;
use Clicalmani\Routes\RouteHooks;

require_once dirname( dirname( __DIR__ ) ) . '/bootstrap/index.php';

/**
 * RequestController class
 * 
 * @package clicalmani/flesco 
 * @author @clicalmani
 */
abstract class RequestController extends HttpRequest 
{
	/**
	 * Current request controller
	 * 
	 * @var mixed
	 */
	private static $controller;

	/**
	 * Current route
	 * 
	 * @var string
	 */
	private static $route;

	/**
	 * Render request response
	 * 
	 * @return void
	 */
	public static function render() : void
	{
		$request = new Request;

		/**
		 * Check CSRF protection
		 * 
		 * |----------------------------------------------------------
		 * | Note !!!
		 * |----------------------------------------------------------
		 * CSRF protection is only based csrf-token request parameter. No CSRF header will be expected
		 * because we asume ajax requests will be made through REST API.
		 */
		if ($_SERVER['REQUEST_METHOD'] !== 'GET' AND FALSE == $request->checkCSRFToken()) {
			response()->status(403, 'FORBIDEN', '403 Forbiden');

			EXIT;
		}

		$response = self::getResponse($request);
		
		// Run after navigation hook
		if ($hook = RouteHooks::getafterHook(static::$route)) $response = $hook($response);

		// Fire TPS
		RouteServiceProvider::fireTPS($response, 1);
		
		die($response);
	}

	/**
	 * Resolve request controller
	 * 
	 * @return mixed
	 */
    private static function getController() : mixed
	{
		if ( isset( self::$controller ) ) {
			return self::$controller;
		}
		
		$request = new Request([]);
		$method = $request->getMethod();
		
		if ($route = Routing::route()) { 
			
			$middlewares = Route::getRouteMiddlewares($route);
			
			self::$route      = $route;
			self::$controller = Route::getController($method, $route);
			
			Route::currentRouteSignature($route);
			
			if ( isset($middlewares) AND $response_code = Route::isRouteAuthorized($route, $request) AND 200 !== $response_code) {
				
				switch($response_code) {
					case 401: response()->status($response_code, 'UNAUTHORISED_REQUEST_ERROR', 'Request Unauthorized'); break;
					case 403: response()->status($response_code, 'FORBIDEN', 'Request Forbiden'); break;
					case 404: response()->status($response_code, 'NOT FOUND', 'Not Found'); break;
					default: response()->status($response_code); break;
				}
				
				exit;
			}
			
			return self::$controller;
		}
		
		throw new RouteNotFoundException( current_route() );
    }
	
	/**
	 * Get request response
	 * 
	 * @param \Clicalmani\Flesco\Http\Requests\Request
	 * @return mixed
	 */
	private static function getResponse(Request $request) : mixed
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
			return $controller(...(self::getParameters($request)));
		}

		throw new RouteNotFoundException(current_route());
	}

	/**
	 * Run route action
	 * 
	 * @param mixed $controllerClass
	 * @param mixed $method
	 * @return mixed
	 */
	public static function invokeControllerMethod($controllerClass, $method = 'invoke') : mixed
	{
		$request = new Request;							  // Fallback to default request
		Request::currentRequest($request);
		
		$reflect = new RequestReflection($controllerClass, $method);
		
		/**
		 * Validate request
		 */
		if ( $requestClass = $reflect->getParamTypeAt(0) ) {
			
			// Model binding request
			if ( self::isResourceBind($requestClass) ) {
				try {
					return self::bindResource($requestClass, $controllerClass, $method);
				} catch(ModelNotFoundException $e) {

					$resource = self::getResource();

					if (array_key_exists('missing', (array) $resource?->methods) ) {
						return $resource->methods['missing']['caller']();
					}

					return response()->status(404, 'NOT_FOUND', $e->getMessage());		// Not Found
				}
			}
			
			$request = new $requestClass;
			self::validateRequest(new $request);
		}
		
		$params_types = $reflect->getParamsTypes();
		$params_values = self::getParameters($request);

		array_shift($params_types);

		self::setTypes($params_types, $params_values);

		return (new $controllerClass)->{$method}($request, ...$params_values);
	}

	/**
	 * Validate request
	 * 
	 * @param \Clicalmani\Flesco\Http\Requests\Request
	 * @return mixed
	 */
	private static function validateRequest(Request $request) : mixed
	{
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

		return null;
	}

	/**
	 * Set parameters types
	 * 
	 * @param string[] $types
	 * @param string[] $values
	 * @return void
	 */
	private static function setTypes(array $types, array &$values) : void
	{
		foreach ($types as $index => $type) {
			if (in_array($type, ['boolean', 'bool', 'integer', 'int', 'float', 'double', 'string', 'array', 'object']))
				settype($values[$index], $type);
			elseif ($type) {
				$obj = new $type;

				if (is_subclass_of($obj, \Clicalmani\Flesco\Http\Requests\Request::class)) self::validateRequest($obj);

				$values[$index] = $obj;
			}
		}
	}

	/**
	 * Gather request parameters
	 * 
	 * @param \Clicalmani\Flesco\Http\Requests\Request
	 * @return array
	 */
	private static function getParameters(Request $request) : array
    {
		if ( inConsoleMode() ) return $request->all();
		
        preg_match_all('/:[^\/]+/', (string) self::$route, $mathes);

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

	/**
	 * Is resource bind
	 * 
	 * @param mixed $resource
	 * @return bool
	 */
	private static function isResourceBind(mixed $resource) : bool
	{
		return is_subclass_of($resource, \Clicalmani\Flesco\Models\Model::class);
	}

	/**
	 * Bind a model resource
	 * 
	 * @param mixed $resource
	 * @param mixed $controller
	 * @param mixed $method
	 * @return mixed
	 */
	private static function bindResource(mixed $resource, mixed $controller, mixed $method) : mixed
	{
		$request = new Request;
		$obj     = new $resource;
		$reflect = new RequestReflection($controller, $method);
		
		$params_types = $reflect->getParamsTypes();
		$params_values = self::getParameters($request);
		
		array_shift($params_types);

		self::setTypes($params_types, $params_values);
		
		if ( in_array($method, ['create', 'show', 'update', 'destroy']) ) {

			// Request parameters
			$parameters = explode(',', (string) $request->id);
			
			if ( count($parameters) ) {
				if ( count($parameters) === 1 ) $parameters = $parameters[0];	// Single primary key
				
				$obj = new $resource($parameters);
				
				/**
				 * Bind resources
				 */
				self::bindRoutines($method, $obj);
				
				$collection = $obj->get();

				if ( $collection->isEmpty() ) throw new ModelNotFoundException($resource);
				
				return (new $controller)->{$method}($obj, ...$params_values);

			} else throw new ModelNotFoundException($resource);
		}

		$obj = new $resource;

		/**
		 * Bind resources
		 */
		self::bindRoutines($method, $obj);
		
		return (new $controller)->{$method}($obj, ...$params_values);
	}

	/**
	 * Bind resource routines
	 * 
	 * @param mixed $method
	 * @param \Clicalmani\Flesco\Models\Model $obj
	 * @return void
	 */
	private static function bindRoutines(mixed $method, Model $obj) : void
	{
		if ($resource = self::getResource()) {
			
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

	/**
	 * Get a model resource name
	 * 
	 * @return mixed
	 */
	private static function getResource()
	{
		if ( inConsoleMode() ) return null;

		// Resource
		$sseq = preg_split('/\//', self::$route, -1, PREG_SPLIT_NO_EMPTY);
		
		return ResourceRoutines::getRoutines(
			Route::isApi() ? $sseq[1]: $sseq[0]
		);
	}

	/**
	 * Distinct rows
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Flesco\Models\Model $obj
	 * @return void
	 */
	private static function getResourceDistinct(mixed $resource, mixed $method, Model $obj) : void
	{
		if ( $method == 'index' AND array_key_exists('distinct', $resource?->properties) ) {
			$obj->distinct($resource?->properties['distinct']);
		}
	}

	/**
	 * Ignore duplicates
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Flesco\Models\Model $obj
	 * @return void
	 */
	private static function createResourceIgnore(mixed $resource, mixed $method, $obj) : void
	{
		if ( $method == 'create' AND array_key_exists('ignore', $resource->properties) ) {
			$obj->ignore($resource->properties['ignore']);
		}
	}

	/**
	 * Join
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Flesco\Models\Model $obj
	 * @return void
	 */
	private static function resourceJoin(mixed $resource, mixed $method, Model $obj) : void
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

	/**
	 * Delete from
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Flesco\Models\Model $obj
	 * @return void
	 */
	private static function resourceDeleteFrom(mixed $resource, mixed $method, Model $obj) : void
	{
		if ( $method == 'destroy' AND array_key_exists('from', $resource->properties) ) {
			$obj->from($resource->properties['from']);
		}
	}

	/**
	 * Calc rows
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Flesco\Models\Model $obj
	 * @return void
	 */
	private static function resourceCalcRows(mixed $resource, mixed $method, Model $obj) : void
	{
		if ( $method == 'index' AND array_key_exists('calc', $resource->properties) ) {
			$obj->calcFoundRows($resource->properties['calc']);
		}
	}

	/**
	 * Limit rows
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Flesco\Models\Model $obj
	 * @return void
	 */
	private static function resourceLimit(mixed $resource, mixed $method, Model $obj) : void
	{
		if ( $method == 'index' AND array_key_exists('limit', $resource->properties) ) {
			$obj->limit($resource->properties['limit']);
		}
	}

	/**
	 * Offset rows
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Flesco\Models\Model $obj
	 * @return void
	 */
	private static function resourceOffset(mixed $resource, mixed $method, Model $obj) : void
	{
		if ( $method == 'index' AND array_key_exists('offset', $resource->properties) ) {
			$obj->offset($resource->properties['offset']);
		}
	}

	/**
	 * Order by
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Flesco\Models\Model $obj
	 * @return void
	 */
	private static function resourceOrderBy(mixed $resource, mixed $method, Model $obj) : void
	{
		if ( $method == 'index' AND array_key_exists('order_by', $resource->properties) ) {
			$obj->orderBy($resource->properties['order_by']);
		}
	}

	/**
	 * Controller test
	 * 
	 * @param string $action Test action
	 * @return \Clicalmani\Flesco\TestUnits\Controllers\TestController
	 */
	public static function test(string $action) : \Clicalmani\Flesco\TestUnits\Controllers\TestController
	{
		return with( new \Clicalmani\Flesco\TestUnits\Controllers\TestController )->new($action);
	}
}
