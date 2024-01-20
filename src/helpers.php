<?php

if ( ! function_exists( 'root_path' ) ) {

    /**
     * Get root path
     * 
     * @param ?string $subdirectory
     * @return string
     */
    function root_path(?string $subdirectory = '') : string {
        global $root_path;
        if (!$root_path) $root_path = $_SERVER['DOCUMENT_ROOT'];
        if (!preg_match('/.*\/$/', $root_path)) $root_path = $root_path . '/';
        return $root_path . trim($subdirectory, '/\\');
    }
}

if ( ! function_exists( 'app_path' ) ) {

    /**
     * Get App path
     * 
     * @param ?string $subdirectory
     * @return string
     */
    function app_path(?string $subdirectory = '') : string {
        if ($subdirectory) return root_path( 'app/' . trim($subdirectory, '/\\') );
        return root_path('app');
    }
}

if ( ! function_exists( 'public_path' ) ) {

    /**
     * Get publi path
     * 
     * @param ?string $subdirectory
     * @return string
     */
    function public_path(?string $subdirectory = '') : string {
        if ($subdirectory !== '') return root_path( 'public/' . trim($subdirectory, '/\\') );
        return root_path('public');
    }
}

if ( ! function_exists( 'bootstrap_path' ) ) {

    /**
     * Get bootstrap directory
     * 
     * @param ?string $subdirectory
     * @return string
     */
    function bootstrap_path(?string $subdirectory = '') : string {
        if ($subdirectory) return root_path( 'bootstrap/' . trim($subdirectory, '/\\') );
        return root_path('bootstrap');
    }
}

if ( ! function_exists( 'routes_path' ) ) {

    /**
     * Get routes path
     * 
     * @param ?string $subdirectory
     */
    function routes_path(?string $subdirectory = '') : string {
        if ($subdirectory) return root_path( 'routes/' . trim($subdirectory, '/\\') );
        return root_path('routes');
    }
}

if ( ! function_exists( 'resources_path' ) ) {

    /**
     * Get resources path
     * 
     * @param ?string $subdirectory
     * @return string
     */
    function resources_path(?string $subdirectory = '') : string {
        if ($subdirectory) return root_path( 'resources/' . trim($subdirectory, '/\\') );
        return root_path('resources');
    }
}

if ( ! function_exists( 'storage_path' ) ) {

    /**
     * Get storage path
     * 
     * @param ?string $subdirectory
     * @return string
     */
    function storage_path(?string $subdirectory = '') : string {
        if ($subdirectory) return root_path( 'storage/' . trim($subdirectory, '/\\') );
        return root_path('storage');
    }
}

if ( ! function_exists( 'config_path' ) ) {

    /**
     * Get config path
     * 
     * @param ?string $subdirectory
     * @return string
     */
    function config_path(?string $subdirectory = '') : string {
        if ($subdirectory) return root_path( 'config/' . trim($subdirectory, '/\\') );
        return root_path('config');
    }
}

if ( ! function_exists( 'database_path' ) ) {

    /**
     * Get database path
     * 
     * @param ?string $subdirectory
     * @return string
     */
    function database_path(?string $subdirectory = '') : string {
        if ($subdirectory) return root_path( 'database/' . trim($subdirectory, '/\\') );
        return root_path('database');
    }
}

if ( ! function_exists( 'view' ) ) {

    /**
     * Render a template view
     * 
     * @param string $template Template name
     * @param ?array $vars Variables
     * @return mixed
     */
    function view(string $template, ?array $vars = []) : mixed {
        return Clicalmani\Flesco\Resources\Views\View::render($template, $vars);
    }
}

if ( ! function_exists( 'current_route' ) ) {

    /**
     * Returns the current route
     * 
     * @return string
     */
    function current_route() : string {
        return Clicalmani\Routes\Route::currentRoute();
    }
}

if ( ! function_exists( 'csrf' ) ) {

    /**
     * Get CSRF token
     * 
     * @return mixed
     */
    function csrf() : mixed {
        if ( isset($_SESSION['csrf-token']) ) {
            return $_SESSION['csrf-token'];
        }

        return null;
    }
}

if ( ! function_exists( 'env' ) ) {

    /**
     * Get env value
     * 
     * @param string $key Env key
     * @param ?string $default Default value if key does not exists.
     * @return string
     */
    function env(string $key, ?string $default = '') : string {
        return isset($_ENV[$key]) ? $_ENV[$key]: $default;
    }
}

if ( ! function_exists( 'assets' ) ) {

    /**
     * Get asset
     * 
     * @param ?string $path Asset path
     * @return string
     */
    function assets(?string $path = '/') : string {
        $app_url = env('APP_URL', '127.0.0.1:8000');
        $protocol = '';
        if (preg_match('/^http/', $app_url) == false) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || @$_SERVER['SERVER_PORT'] === 443) ? 'https://': 'http://';
        }
        return $protocol . env('APP_URL', 'http://127.0.0.1:8000') . $path;
    }
}

if ( ! function_exists( 'password' ) ) {

    /**
     * Create a password hash
     * 
     * @param string $password Password to hash
     * @return string
     */
    function password(string $password) : string {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

if ( ! function_exists( 'temp_dir' ) ) {

    /**
     * Get temp dir
     * 
     * @param ?string $path
     * @return string
     */
    function temp_dir(?string $path = '/') : string {

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

    /**
     * Get request param
     * 
     * @param ?string $param
     * @return mixed
     */
    function request(?string $param = '') : mixed {

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

    /**
     * Do a redirect
     * 
     * @return never
     */
    function redirect() : never {
        with ( new \Clicalmani\Flesco\Http\Requests\Request )->redirect();
    }
}

if ( ! function_exists('response') ) {

    /**
     * Return an Http response
     * 
     * @return \Clicalmani\Flesco\Http\Response\HttpResponseHelper
     */
    function response() {
        return new \Clicalmani\Flesco\Http\Response\HttpResponseHelper;
    }
}

if ( ! function_exists('route') ) {

    /**
     * Do route
     * 
     * @param mixed ...$args
     * @return mixed
     */
    function route(mixed ...$args) : mixed {
        return \Clicalmani\Routes\Route::resolve(...$args);
    }
}

if ( ! function_exists('collection') ) {

    /**
     * Create a collection
     * 
     * @return \Clicalmani\Collection\Collection
     */
    function collection() {
        return new \Clicalmani\Collection\Collection;
    }
}

if ( ! function_exists('sanitize_attribute') ) {

    /**
     * Sanitize attribute
     * 
     * @param string $attr
     * @return mixed
     */
    function sanitize_attribute($attr) : mixed {
        return preg_replace('/[^0-9a-z-_]+/', '', \Clicalmani\Flesco\Support\Str::slug($attr));
    }
}

if ( ! function_exists('now') ) {

    /**
     * Get current date
     * 
     * @param ?string $time_zone
     * @return \Carbon\Carbon
     */
    function now(?string $time_zone = 'Africa/Porto-Novo') {
        return \Carbon\Carbon::now($time_zone);
    }
}

if ( ! function_exists('slug') ) {

    /**
     * Slugify a string
     * 
     * @param string $str
     * @return string
     */
    function slug(string $str) : string {
        return \Clicalmani\Flesco\Support\Str::slug($str);
    }
}

if ( ! function_exists('recursive_unlink') ) {

    /**
     * Unlink path
     * 
     * @param string $path
     * @return bool True on success, false on failure.
     */
    function recursive_unlink(string $path) : bool {
	
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

    /**
     * Send mail through SMTP protocol
     * 
     * @param array $to
     * @param array $from
     * @param string $subject
     * @param string $body
     * @param array $cc Carbon copy
     * @param array $bc Back copy
     * @return mixed
     */
    function mail_smtp(array $to, array $from, string $subject, string $body, ?array $cc = [], ?array $bc = [])
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

    /**
     * Return the given value or pass it to a callback.
     * 
     * @param mixed $value
     * @param ?callbable $callback
     * @return mixed
     */
    function with(mixed $value, ?callable $callback = null) : mixed {
        return is_null($callback) ? $value: $callback($value);
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

    /**
     * Verify if console mode is active
     * 
     * @return bool
     */
    function inConsoleMode() : bool {
        return defined('CONSOLE_MODE_ACTIVE') && CONSOLE_MODE_ACTIVE;
    }
}

if ( ! function_exists('tap') ) {

    /**
     * Call the given closure with the given value and then return the value.
     * 
     * @param mixed $value
     * @param callable $callback
     * @return mixed
     */
    function tap(mixed $value, callable $callback) : mixed {
        $callback($value);
        return $value;
    }
}

if ( ! function_exists('value') ) {

    /**
     * Call a value with given parameter or return the value.
     * 
     * @param mixed $value
     * @param mixed $param
     * @return mixed
     */
    function value(mixed $value, mixed $param = null) {
        if ( ! is_callable($value) ) return $value;
        if ( $param ) return $value($param);
        return $value();
    }
}

if ( ! function_exists('call') ) {

    /**
     * Call a value with specified arguments.
     * 
     * @param callable $value
     * @param mixed ...$args
     * @return mixed
     */
    function call(mixed $value, mixed ...$args) : mixed {
        return $value( ...$args );
    }
}

if ( ! function_exists('nocall') ) {

    /**
     * Return a value without calling it.
     * 
     * @param mixed $value
     * @return mixed
     */
    function nocall(mixed $fn) : mixed {
        return $fn;
    }
}

if ( ! function_exists('faker') ) {
    function faker() {
        return new \Clicalmani\Database\Faker\Faker;
    }
}

if ( ! function_exists('xdt') ) {

    /**
     * Create a new XDT object.
     * 
     * @return \Clicalmani\XPower\XDT
     */
    function xdt() {
        return new \Clicalmani\XPower\XDT;
    }
}

if ( ! function_exists('token') ) {

    /**
     * Generate a token
     * 
     * @param mixed $jti
     * @param int $expiry Expiry in seconds
     * @return string
     */
    function token(mixed $jti, int $expiry) : string {
        $jwt = new \Clicalmani\Flesco\Auth\JWT;
        $jwt->setJti( json_encode($jti) );
        $jwt->setExpiry($expiry/(60*60*24)); // expiry in days
        return $jwt->generateToken();
    }
}

if ( ! function_exists('get_payload') ) {

    /**
     * Get payload
     * 
     * @param string $token
     * @return mixed
     */
    function get_payload(string $token) : mixed {
        return with ( new \Clicalmani\Flesco\Auth\JWT )->verifyToken($token);
    }
}

if ( ! function_exists('tree') ) {

    /**
     * Flaten a tree
     * 
     * @param iterable $iterable
     * @param callable $callback
     * @return mixed
     */
    function tree(iterable $iterable, callable $callback) : mixed {
        $ret = [];
        foreach ($iterable as $item) {
            $ret[] = $item;
            $ret = array_merge($ret, [...tree($callback($item), $callback)]);
        }

        return $ret;
    }
}

if ( ! function_exists('jwt') ) {

    /**
     * Create a JWT object. 
     * 
     * @param ?string $jti
     * @param mixed $expiry
     * @return \Clicalmani\Flesco\Auth\JWT
     */
    function jwt(?string $jti = null, mixed $expiry = 1) {
        return new \Clicalmani\Flesco\Auth\JWT($jti, $expiry);
    }
}

if ( ! function_exists('encrypt') ) {

    /**
     * Encrypt a value
     * 
     * @param string $value
     * @return mixed
     */
    function encrypt(string $value) : mixed {
        return \Clicalmani\Flesco\Security\Security::encrypt($value);
    }
}

if ( ! function_exists('decrypt') ) {

    /**
     * Decrypt a value
     * 
     * @param string $value
     * @return mixed
     */
    function decrypt(string $value) : mixed {
        return \Clicalmani\Flesco\Security\Security::decrypt($value);
    }
}
