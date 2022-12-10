<?php
namespace Clicalmani\Flesco\App\Controllers;

use Clicalmani\Flesco\App\Http\Request;
use Clicalmani\Flesco\App\Http\HttpRequest;
use Clicalmani\Flesco\App\Routes\Route;
use Clicalmani\Flesco\Exceptions\HttpRequestException;

abstract class RequestController extends HttpRequest {
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
		$url = parse_url($_SERVER['REQUEST_URI']);
		$path = isset($url['path']) ? $url['path']: '/';

		foreach (Route::$rountines as $method => $data) {
			if (isset($data[$path])) {
				return $data[$path];
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