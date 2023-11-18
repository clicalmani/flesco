<?php

if ( ! function_exists( 'root_path' ) ) {
    function root_path( $path = '' ) {
        global $root_path;
        if (!$root_path) $root_path = $_SERVER['DOCUMENT_ROOT'];
        if (!preg_match('/.*\/$/', $root_path)) $root_path = $root_path . '/';
        return $root_path . trim($path, '/\\');
    }
}

if ( ! function_exists( 'app_path' ) ) {
    function app_path( $path = '' ) {
        if ($path) return root_path( 'app/' . trim($path, '/\\') );
        return root_path('app');
    }
}

if ( ! function_exists( 'public_path' ) ) {
    function public_path( $path = '' ) {
        if ($path !== '') return root_path( 'public/' . trim($path, '/\\') );
        return root_path('public');
    }
}

if ( ! function_exists( 'bootstrap_path' ) ) {
    function bootstrap_path( $path = '' ) {
        if ($path) return root_path( 'bootstrap/' . trim($path, '/\\') );
        return root_path('bootstrap');
    }
}

if ( ! function_exists( 'routes_path' ) ) {
    function routes_path( $path = '' ) {
        if ($path) return root_path( 'routes/' . trim($path, '/\\') );
        return root_path('routes');
    }
}

if ( ! function_exists( 'resources_path' ) ) {
    function resources_path( $path = '' ) {
        if ($path) return root_path( 'resources/' . trim($path, '/\\') );
        return root_path('resources');
    }
}

if ( ! function_exists( 'storage_path' ) ) {
    function storage_path( $path = '' ) {
        if ($path) return root_path( 'storage/' . trim($path, '/\\') );
        return root_path('storage');
    }
}

if ( ! function_exists( 'config_path' ) ) {
    function config_path( $path = '' ) {
        if ($path) return root_path( 'config/' . trim($path, '/\\') );
        return root_path('config');
    }
}

if ( ! function_exists( 'database_path' ) ) {
    function database_path( $path = '' ) {
        if ($path) return root_path( 'database/' . trim($path, '/\\') );
        return root_path('database');
    }
}

if ( ! function_exists( 'view' ) ) {
    function view( ...$args ) {
        return Clicalmani\Flesco\Resources\Views\View::render( ...$args );
    }
}

if ( ! function_exists( 'current_route' ) ) {
    function current_route() {
        return Clicalmani\Routes\Route::currentRoute();
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
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || @$_SERVER['SERVER_PORT'] === 443) ? 'https://': 'http://';
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
        
        $request = \Clicalmani\Flesco\Http\Requests\Request::currentRequest();

        if ( $request ) {
            return $request->{$param};
        }

        return null;
    }
}

if ( ! function_exists('redirect') ) {
    function redirect() {
        return with ( new \Clicalmani\Flesco\Http\Requests\Request )->redirect();
    }
}

if ( ! function_exists('response') ) {
    function response() {
        return new \Clicalmani\Flesco\Http\Response\HttpResponseHelper;
    }
}

if ( ! function_exists('route') ) {
    function route(mixed ...$args) {
        return \Clicalmani\Routes\Route::resolve(...$args);
    }
}

if ( ! function_exists('collection') ) {
    function collection() {
        return new \Clicalmani\Collection\Collection;
    }
}

if ( ! function_exists('sanitize_attribute') ) {
    function sanitize_attribute($attr) {
        return preg_replace('/[^0-9a-z-_]+/', '', \Clicalmani\Flesco\Support\Str::slug($attr));
    }
}

if ( ! function_exists('now') ) {
    function now() {
        return \Carbon\Carbon::now('Africa/Porto-Novo');
    }
}

if ( ! function_exists('slug') ) {
    function slug($str) {
        return \Clicalmani\Flesco\Support\Str::slug($str);
    }
}

if ( ! function_exists('recursive_unlink') ) {
    function recursive_unlink($path) {
	
	    if (is_dir($path) === true) {
		
		    $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path), 
                \RecursiveIteratorIterator::CHILD_FIRST
            );
			
			foreach ($files as $file) {
			    
				if (in_array($file->getBaseName(), array('.', '..')) !== true) {
				    
					if ($file->isDir() === true) { 
					    
						@ rmdir($file->getPathName());
					} elseif (($file->isFile() === true) || ($file->isLink() === true)) {
					    
						@ unlink($file->getPathName());
					}
				}
			}
			
			return @ rmdir($path);
		} elseif ((is_file($path) === true) || (is_link($path) === true)) {
		    
			return @ unlink($path);
		}
		
		return false;
	}
}

if ( ! function_exists('mail_smtp') ) {
    function mail_smtp($to, $from, $subject, $body, $cc = [], $bc = [])
    {
        $mail = new \Clicalmani\Flesco\Mail\MailSMTP;

        $mail->setSubject($subject);
        $mail->setBody($body);
        $mail->setFrom($from['email'], $from['name']);

        foreach ($to as $data) {
            $mail->addAddress($data['email'], $data['name']);
        }

		if ($cc) {
            foreach ($cc as $data) {
                $mail->addCC($data['email'], $data['name']);
            }
		}

		if ($bc) {
            foreach ($bc as $data) {
                $mail->addBC($data['email'], $data['name']);
            }
		}

		$mail->isHTML(true);
        
        return $mail->send();
    }
}

if ( ! function_exists('with') ) {
    function with($obj) {
        return $obj;
    }
}

if ( ! function_exists('instance') ) {
    /**
     * Class instance creator
     * 
     * @param string $class
     * @param ?callable $callback A callback function that receive an instance of the class as it's first argument.
     * @return mixed $class Object
     */
    function instance(string $class, ?callable $callback = null)
    {
        $instance = new $class;
        $callback($instance);
        return $instance;
    }
}

if ( ! function_exists('factory') ) {
    /**
     * Create a new model factory
     * 
     * @param string $model Model class
     * @return \Clicalmani\Database\Factory\Factory Object
     */
    function factory(string $model) {
        return \Clicalmani\Database\Factory\Factory::fromModel( $model );
    }
}

if ( ! function_exists('inConsoleMode') ) {
    function inConsoleMode() {
        return defined('CONSOLE_MODE_ACTIVE') && CONSOLE_MODE_ACTIVE;
    }
}

if ( ! function_exists('tap') ) {
    function tap($value, $callback) {
        $callback($value);
        return $value;
    }
}

if ( ! function_exists('value') ) {
    function value($value, $param = null) {
        if ( ! is_callable($value) ) return $value;
        if ( $param ) return $value($param);
        return $value();
    }
}

if ( ! function_exists('call') ) {
    function call($fn, ...$args) {
        return $fn( ...$args );
    }
}

if ( ! function_exists('nocall') ) {
    function nocall($fn) {
        return $fn;
    }
}

if ( ! function_exists('faker') ) {
    function faker() {
        return new \Clicalmani\Database\Faker\Faker;
    }
}

if ( ! function_exists('xdt') ) {
    function xdt() {
        return new \Clicalmani\XPower\XDT;
    }
}

if ( ! function_exists('token') ) {
    function token(mixed $data, int $seconds) {
        $jwt = new \Clicalmani\Flesco\Auth\JWT;
        $jwt->setJti( json_encode($data) );
        $jwt->setExpiry($seconds/(60*60*24)); // expiry in days
        return $jwt->generateToken();
    }
}

if ( ! function_exists('get_payload') ) {
    function get_payload(string $token) {
        return with ( new \Clicalmani\Flesco\Auth\JWT )->verifyToken($token);
    }
}

if ( ! function_exists('tree') ) {
    function tree(iterable $iterable, callable $callback) {
        $ret = [];
        foreach ($iterable as $item) {
            $ret[] = $item;
            $ret = array_merge($ret, [...tree($callback($item), $callback)]);
        }

        return $ret;
    }
}
