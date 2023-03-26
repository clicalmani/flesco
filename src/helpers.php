<?php

if ( ! function_exists( 'root_path' ) ) {
    function root_path( $path = '/' ) {
        return $_SERVER['DOCUMENT_ROOT'] . $path;
    }
}

if ( ! function_exists( 'app_path' ) ) {
    function app_path( $path = '/' ) {
        return root_path( '/app' . $path );
    }
}

if ( ! function_exists( 'bootstrap_path' ) ) {
    function bootstrap_path( $path = '/' ) {
        return root_path( '/bootstrap' . $path );
    }
}

if ( ! function_exists( 'routes_path' ) ) {
    function routes_path( $path = '/' ) {
        return root_path( '/routes' . $path );
    }
}

if ( ! function_exists( 'ressources_path' ) ) {
    function ressources_path( $path = '/' ) {
        return root_path( '/ressources' . $path );
    }
}

if ( ! function_exists( 'storage_path' ) ) {
    function storage_path( $path = '/' ) {
        return root_path( '/storage' . $path );
    }
}

if ( ! function_exists( 'config_path' ) ) {
    function config_path( $path = '/' ) {
        return root_path( '/config' . $path );
    }
}

if ( ! function_exists( 'database_path' ) ) {
    function database_path( $path = '/' ) {
        return root_path( '/database' . $path );
    }
}

if ( ! function_exists( 'view' ) ) {
    function view( ...$args ) {
        return Clicalmani\Flesco\Ressources\Views\View::render( ...$args );
    }
}

if ( ! function_exists( 'current_route' ) ) {
    function current_route() {
        return Clicalmani\Flesco\Routes\Route::currentRoute();
    }
}

if ( ! function_exists( 'csrf' ) ) {
    function csrf() {
        if ( isset($_SESSION['csrf-token']) ) {
            return $_SESSION['csrf-token'];
        }

        return null;
    }
}

if ( ! function_exists( 'env' ) ) {
    function env($key, $default = '') {
        return isset($_ENV[$key]) ? $_ENV[$key]: $default;
    }
}

if ( ! function_exists( 'assets' ) ) {
    function assets($path = '/') {
        $app_url = env('APP_URL', '127.0.0.1:8000');
        $protocol = '';
        if (preg_match('/^http/', $app_url) == false) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] === 443) ? 'https://': 'http://';
        }
        return $protocol . env('APP_URL', 'http://127.0.0.1:8000') . $path;
    }
}

if ( ! function_exists( 'password' ) ) {
    function password($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

if ( ! function_exists( 'temp_dir' ) ) {
    function temp_dir($path = '/') {

        if ( function_exists( 'sys_get_temp_dir' ) ) {

            $temp = sys_get_temp_dir();

            if ( @is_dir( $temp ) ) {
                return $temp . $path;
            }
        }

        $temp = ini_get( 'upload_tmp_dir' );

        if ( @is_dir( $temp ) ) {
            return $temp . $path;
        }

        return "/tmp$path";
    }
}

if ( ! function_exists('request') ) {
    function request($param = '') {

        if (empty($param)) {
            return \Clicalmani\Flesco\Http\Requests\Request::all();
        }
        
        $request = \Clicalmani\Flesco\Http\Requests\Request::$current_request;

        if ( $request ) {
            return $request->{$param};
        }

        return null;
    }
}

if ( ! function_exists('response') ) {
    function response($data = null, $status = 'success') {
        return \Clicalmani\Flesco\Http\Response\Response::{$status}($data);
    }
}

if ( ! function_exists('route') ) {
    function route($route = '/', $params = []) {
        if ( empty($params) ) {
            return $route;
        }

        $mathes = [];
        preg_match_all('/:[^\/]+/', $route, $mathes);
        
        if ( count($mathes) ) {
            $mathes = $mathes[0];
            $parameters = [];

            foreach ($mathes as $param) {
                $name = substr($param, 1);    				    // Remove starting two dots (:)
                $name = substr($param, 0, strpos($param, '@')); // Remove validation part
                
                if (array_key_exists($name, $params)) {
                    $route = str_replace($param, $params[$name], $route);
                }
            }

            return $route;
        }

        return null;
    }
}
