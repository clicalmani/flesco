<?php
namespace Clicalmani\Flesco\App\Http\Controllers;

use Clicalmani\Flesco\App\Http\Requests\Request;
use Clicalmani\Flesco\App\Http\Requests\HttpRequest;
use Clicalmani\Flesco\Routes\Route;
use Clicalmani\Flesco\App\Exceptions\HttpRequestException;
use Clicalmani\Flesco\Security\CSRF;

$vendor_dir = dirname( dirname( dirname( __DIR__ ) ) );

require_once $vendor_dir . '/config/config.php';
require_once $vendor_dir . '/helpers.php';
require_once $vendor_dir . '/auto.php';

ini_set('log_errors', 1);
ini_set('error_log', storage_path( '/errors/errors.log' ) );

if (session_status() == PHP_SESSION_NONE) {
    session_start();

	if ( ! isset($_SESSION['csrf-token']) ) {
		$csrf = new CSRF;
		$_SESSION['csrf-token'] = $csrf->getToken();
	}
}

require_once config_path( '/providers.php' );
require_once routes_path( '/web.php' );

abstract class RequestController extends HttpRequest 
{
    protected $route;
    
    protected static function renderGetRequest($request) {
        return Route::$rountines['get'][$_GET['route']](new Request( $request ));
    }

    protected static function renderPostRequest($request) {
        return Route::$rountines['post'][$_POST['route']](new Request( $request ));
    }

	public static function render()
	{
		$controller = self::getController();

		if (is_array($controller) AND !empty($controller)) {
			$class = 'App\Controllers\\' . $controller[0];
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
			} else {
				return self::getRoutine(
					new Request([])
				);
			}
		}

		throw new HttpRequestException('No render method');
	}

    public static function getController() 
	{
		$route = current_route();
		$http = new HttpRequest;
		$headers = $http->getHeaders();
		echo '<pre>'; print_r($headers); echo '</pre>';
		foreach (Route::$rountines as $method => $data) {
			if ($route = Route::exists($route, $method)) { 

				$middleware = Route::getCurrentRouteMiddleware();
				
				if ( isset($middleware) AND Route::isCurrentRouteAuthorized() == false ) {
					throw new HttpRequestException('Request not authorized !');
				}

				return $data[$route];
			}
		}
		
		throw new HttpRequestException('Request not associated to any controller !');
    }

	public static function getRoutine($request)
	{
		$controller = self::getController();

		if (is_array($controller) AND !empty($controller)) {
			
			$class = 'App\Controllers\\' . $controller[0];
			$obj = new $class;
			
			if (method_exists($obj, $controller[1])) {

				$ref = new \ReflectionMethod($class, $controller[1]);
				$paramType = $ref->getParameters()[0]->getType();

				if ($paramType) {
					$requestClass = $paramType->getName();
					$ro = new $requestClass([]);
					$ro->validate(); // Validate request
					return $obj->{$controller[1]}($ro);
				}
				
				return $obj->{$controller[1]}($request);
			}
		} elseif ($controller instanceof \Closure) {
			return $controller($request);
		}
		
		throw new HttpRequestException('Request without routine !');
	}
}