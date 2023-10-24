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
		$request->checkCSRFToken();

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
			
			$middlewares = Route::getCurrentRouteMiddlewares();
			
			self::$route      = $route;
			self::$controller = Route::getController($method, $route);
			
			if ( isset($middlewares) AND Route::isCurrentRouteAuthorized($request) == false ) {
				response()->status(401, 'UNAUTHORISED_REQUEST_ERROR', 'Request Unauthorized');		// Unauthorized
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
			return $controller($request);
		}
		
		response()->status(403, 'FORBIDEN_REQUEST_ERROR', 'Access Denied');		// Forbiden
		exit;
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
				
				return (new $controller)->{$method}($obj, new Request);

			} else throw new ModelNotFoundException($resource);
		}

		$obj = new $resource;

		/**
		 * Bind resources
		 */
		self::bindRoutines($method, $obj);
		
		return (new $controller)->{$method}($obj, new Request);
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

	public static function test(string $action) : \Clicalmani\Flesco\TestUnits\Controllers\TestController
	{
		return with( new \Clicalmani\Flesco\TestUnits\Controllers\TestController )->new($action);
	}
}
