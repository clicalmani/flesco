<?php
namespace Clicalmani\Flesco\Logic\Internal;

use Clicalmani\Routes\Route;
use Clicalmani\Routes\Routing;
use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\Http\Requests\HttpRequest;
use Clicalmani\Routes\Exceptions\RouteNotFoundException;
use Clicalmani\Flesco\Exceptions\ModelNotFoundException;
use Clicalmani\Database\Factory\Models\Model;
use Clicalmani\Flesco\Http\Requests\RequestReflection;
use Clicalmani\Flesco\Providers\RouteServiceProvider;
use Clicalmani\Flesco\Test\Controllers\TestController;
use Clicalmani\Fundation\Validation\AsValidator;
use Clicalmani\Routes\Internal\ResourceRoutines;
use Clicalmani\Routes\RouteHooks;

require_once dirname( dirname( __DIR__ ) ) . '/bootstrap/index.php';

/**
 * RequestController class
 * 
 * @package Clicalmani\Flesco/flesco 
 * @author @Clicalmani\Flesco
 */
class RequestController extends HttpRequest
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
	 * @return never
	 */
	public function render() : never
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

		$response = $this->getResponse($request);
		
		// Run after navigation hook
		if ($hook = RouteHooks::getAfterHook(static::$route)) $response = $hook($response);

		// Fire TPS
		RouteServiceProvider::fireTPS($response, 1);
		
		die($response);
	}

	/**
	 * Resolve request controller
	 * 
	 * @return mixed
	 */
    private function getController() : mixed
	{
		if ( isset( static::$controller ) ) {
			return static::$controller;
		}
		
		$request = new Request([]);
		$method = $request->getMethod();
		
		if ($route = Routing::route()) { 
			
			$middlewares = Route::getRouteMiddlewares($route);
			
			static::$route      = $route;
			static::$controller = Route::getController($method, $route);
			
			Route::currentRouteSignature($route);
			
			if ( isset($middlewares) AND $response_code = Route::isRouteAuthorized($route, $request) AND 200 !== $response_code) {
				
				switch($response_code) {
					case 401: response()->status($response_code, 'UNAUTHORIZED_REQUEST_ERROR', 'Request Unauthorized'); break;
					case 403: response()->status($response_code, 'FORBIDEN', 'Request Forbiden'); break;
					case 404: response()->status($response_code, 'NOT FOUND', 'Not Found'); break;
					default: response()->status($response_code); break;
				}
				
				exit;
			}
			
			return static::$controller;
		}
		
		throw new RouteNotFoundException( current_route() );
    }
	
	/**
	 * Get request response
	 * 
	 * @param \Clicalmani\Flesco\Http\Requests\Request
	 * @return mixed
	 */
	private function getResponse(Request $request) : mixed
	{
		$controller = $this->getController();
		
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
				
				return $this->invokeControllerMethod($class, $controller[1]);
			}

			return $this->invokeControllerMethod($class);

		} elseif( is_string($controller) ) {

			return $this->invokeControllerMethod($controller);			  // Controller with magic method invoke

		} elseif ($controller instanceof \Closure) {                      // Otherwise fallback to closure function
			                                                              // whith a default Request object
			return $controller(...($this->getParameters($request)));
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
	public function invokeControllerMethod($controllerClass, $method = '__invoke') : mixed
	{
		$request = new Request;							  // Fallback to default request
		$reflect = new RequestReflection($controllerClass, $method);
		
		/**
		 * Validate request
		 */
		if ( $requestClass = $reflect->getParamTypeAt(0) ) {
			
			// Model binding request
			if ( $this->isResourceBound($requestClass) ) {
				try {
					return $this->bindResource($requestClass, $controllerClass, $method);
				} catch(ModelNotFoundException $e) {

					$resource = $this->getResource();

					if (array_key_exists('missing', (array) $resource?->methods) ) {
						return $resource->methods['missing']['caller']();
					}

					return response()->status(404, 'NOT_FOUND', $e->getMessage());		// Not Found
				}
			}
			
			$request = new $requestClass;
			$this->validateRequest(new $request);
		}
		
		$params_types = $reflect->getParamsTypes();
		$params_values = $this->getParameters($request);

		array_shift($params_types);
		
		$this->setRequestParameterTypes($params_types, $params_values, $method, $controllerClass);
		Request::currentRequest($request); // Current request

		if ($attribute = (new \ReflectionMethod($controllerClass, $method))->getAttributes(AsValidator::class)) {
            $request->merge($attribute[0]->newInstance()->args);
        }
		
		if ($method !== '__invoke') return (new $controllerClass)->{$method}($request, ...$params_values);

        return (new $controllerClass)($request, ...$params_values);
	}

	/**
	 * Validate request
	 * 
	 * @param \Clicalmani\Flesco\Http\Requests\Request
	 * @return mixed
	 */
	private function validateRequest(Request $request) : mixed
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
			$request->signatures();                             // Set parameters signatures
		}

		return null;
	}

	/**
	 * Set parameters types
	 * 
	 * @param string[] $types
	 * @param string[] $values
	 * @param string $method Controller method
	 * @param string $controller Controller class
	 * @return void
	 */
	private function setRequestParameterTypes(array $types, array &$values, string $method, string $controller) : void
	{
		$tmp = [];
		foreach ($types as $name => $type) {
			if (in_array($type, ['boolean', 'bool', 'integer', 'int', 'float', 'double', 'string', 'array', 'object'])) {
				$tmp[$name] = @ $values[$name];
				settype($tmp[$name], $type);
			} elseif ($type) {
				$obj = new $type;

				if (is_subclass_of($obj, \Clicalmani\Flesco\Http\Requests\Request::class)) {
					$this->validateRequest($obj);
					Request::currentRequest($obj); // Current request

					if ($attribute = (new \ReflectionMethod($controller, $method))->getAttributes(AsValidator::class)) {
						$obj->merge($attribute[0]->newInstance()->args);
					}
				}

				$tmp[$name] = $obj;
			} else $tmp[$name] = @ $values[$name];
		}

		$values = $tmp;
	}

	/**
	 * Gather request parameters
	 * 
	 * @param \Clicalmani\Flesco\Http\Requests\Request
	 * @return array
	 */
	private function getParameters(Request $request) : array
    {
		if ( inConsoleMode() ) return $request->all();
		
        preg_match_all('/:[^\/]+/', (string) static::$route, $mathes);

        $parameters = [];
        
        if ( count($mathes) ) {

            $mathes = $mathes[0];
            
            foreach ($mathes as $name) {
                $name = substr($name, 1);    				      // Remove starting two dots (:)
                
                if (preg_match('/@/', $name)) {
                    $name = substr($name, 0, strpos($name, '@')); // Remove validation part
                }
                
                if ($request->{$name}) {
                    $parameters[$name] = $request->{$name};
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
	private function isResourceBound(mixed $resource) : bool
	{
		return is_subclass_of($resource, \Clicalmani\Database\Factory\Models\Model::class);
	}

	/**
	 * Bind a model resource
	 * 
	 * @param mixed $resource
	 * @param mixed $controller
	 * @param mixed $method
	 * @return mixed
	 */
	private function bindResource(mixed $resource, mixed $controller, mixed $method) : mixed
	{
		$request = new Request;
		$obj     = new $resource;
		$reflect = new RequestReflection($controller, $method);
		
		$params_types = $reflect->getParamsTypes();
		$params_values = $this->getParameters($request);
		
		array_shift($params_types);

		$this->setRequestParameterTypes($params_types, $params_values, $method, $controller);
		Request::currentRequest($request); // Current request

		if ($attribute = (new \ReflectionMethod($controller, $method))->getAttributes(AsValidator::class)) {
            $request->merge($attribute[0]->newInstance()->args);
        }
		
		if ( in_array($method, ['create', 'show', 'update', 'destroy']) ) {

			// Request parameters
			$parameters = explode(',', (string) $request->id);
			
			if ( count($parameters) ) {
				if ( count($parameters) === 1 ) $parameters = $parameters[0];	// Single primary key
				
				$obj = new $resource($parameters);
				
				/**
				 * Bind resources
				 */
				$this->bindRoutines($method, $obj);
				
				$collection = $obj->get();

				if ( $collection->isEmpty() ) throw new ModelNotFoundException($resource);
				
				return (new $controller)->{$method}($obj, ...$params_values);

			} else throw new ModelNotFoundException($resource);
		}

		$obj = new $resource;

		/**
		 * Bind resources
		 */
		$this->bindRoutines($method, $obj);
		
		return (new $controller)->{$method}($obj, ...$params_values);
	}

	/**
	 * Bind resource routines
	 * 
	 * @param mixed $method
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function bindRoutines(mixed $method, Model $obj) : void
	{
		if ($resource = $this->getResource()) {
			
			/**
			 * Select distinct
			 */
			$this->getResourceDistinct($resource, $method, $obj);

			/**
			 * Insert ignore
			 */
			$this->createResourceIgnore($resource, $method, $obj);

			/**
			 * Delete multiple
			 */
			$this->resourceDeleteFrom($resource, $method, $obj);

			/**
			 * Pagination
			 */
			$this->resourceCalcRows($resource, $method, $obj);

			/**
			 * Limit rows
			 */
			$this->resourceLimit($resource, $method, $obj);

			/**
			 * Row order by
			 */
			$this->resourceOrderBy($resource, $method, $obj);
		}
	}

	/**
	 * Get a model resource name
	 * 
	 * @return mixed
	 */
	private function getResource()
	{
		if ( inConsoleMode() ) return null;

		// Resource
		$sseq = preg_split('/\//', static::$route, -1, PREG_SPLIT_NO_EMPTY);
		
		$resources = ResourceRoutines::getRoutines(
			Route::isApi() ? $sseq[1]: $sseq[0]
		);
		
		return $resources;
	}

	/**
	 * Distinct rows
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function getResourceDistinct(mixed $resource, mixed $method, Model $obj) : void
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
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function createResourceIgnore(mixed $resource, mixed $method, $obj) : void
	{
		if ( $method == 'create' AND array_key_exists('ignore', $resource->properties) ) {
			$obj->ignore($resource->properties['ignore']);
		}
	}

	/**
	 * Delete from
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function resourceDeleteFrom(mixed $resource, mixed $method, Model $obj) : void
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
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function resourceCalcRows(mixed $resource, mixed $method, Model $obj) : void
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
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function resourceLimit(mixed $resource, mixed $method, Model $obj) : void
	{
		if ( $method == 'index' AND array_key_exists('limit', $resource->properties) AND  array_key_exists('offset', $resource->properties) ) {
			$obj->limit($resource->properties['offset'], $resource->properties['limit']);
		}
	}

	/**
	 * Order by
	 * 
	 * @param mixed $resource
	 * @param mixed $method
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function resourceOrderBy(mixed $resource, mixed $method, Model $obj) : void
	{
		if ( $method == 'index' AND array_key_exists('order_by', $resource->properties) ) {
			$obj->orderBy($resource->properties['order_by']);
		}
	}

	/**
	 * Controller test
	 * 
	 * @param string $action Test action
	 * @return \Clicalmani\Flesco\Test\Controllers\TestController
	 */
	public function test(string $action)
	{
		return with( new TestController )->new($action);
	}
}
