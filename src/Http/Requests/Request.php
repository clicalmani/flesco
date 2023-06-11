<?php
namespace Clicalmani\Flesco\Http\Requests;

use Clicalmani\Flesco\Http\Controllers\RequestController;
use Clicalmani\Flesco\Http\Requests\RequestFile;
use Clicalmani\Flesco\Http\Requests\RequestRedirect;
use Clicalmani\Flesco\Security\Security;
use Clicalmani\Flesco\Routes\Route;

class Request extends HttpRequest implements RequestInterface, \ArrayAccess, \JsonSerializable {

    private $signatures = [];

    static $current_request = null;

    /**
     * @see RequestController::render for implementation
     */
    public static function render() {}

    private function getVars()
    {
        if ( in_array($this->getMethod(), ['put', 'patch']) ) {
            return [...$_GET, ...$_POST];
        }
    }

    public function signatures() {
        // TODO: override
    }

    public function prepareForValidation() {
        // TODO: override
    }

    /**
     * @deprecated
     */
    public function validation($options = [])
    {
        $this->merge($options);
    }

    public function validate($options = [])
    {
        $this->merge($options);
    }

    public function __construct( $signatures = [] ) {
        $this->signatures = $signatures;

        if ('api' === Route::getGateway() AND in_array(static::getMethod(), ['patch', 'put'])) {
            $params = [];
            $parser = new \Clicalmani\Flesco\Http\Requests\ParseInputStream($params);
            
            /**
             * Header application/json
             */
            if ( array_key_exists('parameters', $params) ) $params = $params['parameters'];

            $_REQUEST = array_merge($_REQUEST, $params);
        }
    }

    public function __get($property)
    {
        $vars = static::all();

        $this->signatures = $this->signatures ? $this->signatures: [];
        $sanitized = Security::sanitizeVars($vars, $this->signatures);
        
        if ( array_key_exists($property, $vars) ) {
            if ( array_key_exists($property, $sanitized) ) {
                return $sanitized[$property];
            }

            return $vars[$property];
        }
		
		return null;
    }

    public function __set($property, $value)
    {
        $_REQUEST[$property] = $value;
    }

    public function hasFile($name) {
        return isset($_FILES[$name]);
    }

    public function file($name) {
        if ( $this->hasFile($name) ) {
            return new RequestFile($name);
        }

        return null;
    }

    public function offsetExists( mixed $property ) : bool {
        return ! is_null($this->$property);
    }

    public function offsetGet( mixed $property ) : mixed {
        return $this->$property;
    }

    public function offsetSet( mixed $property, mixed $value ) : void {
        $this->$property = $value;
    }

    public function offsetUnset( mixed $property ) : void {
        if ($this->$property) {
            $this->$property = null;
        }
    }

    public function download($filename, $filepath) 
    {
        header('Content-Type: ' . mime_content_type($filepath));
        header("Content-Disposition: attachment; filename=$filename");
        return readfile($filepath);
    }

    public function merge($new_signatures = [])
    {
        $this->signatures = $this->signatures ? $this->signatures: [];
        $this->signatures = array_merge($this->signatures, $new_signatures);
    }

    public function getHeaders()
    {
        return getallheaders();
    }

    public function getHeader($header_name)
    {
        foreach ($this->getHeaders() as $name => $header) {
            if (strtolower($name) == strtolower($header_name)) return $header;
        }

        return null;
    }

    public function setHeader($name, $value)
    {
        header("$name: $value");
    }

    public function getMethod()
    { 
        return strtolower( $_SERVER['REQUEST_METHOD'] );
    }

    public static function all()
    {
        return $_REQUEST;
    }

    public function checkCSRFToken()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $csrf = new \Clicalmani\Flesco\Security\CSRF;
        $token = $csrf->getToken();
    
        if ( ! isset($_SESSION['csrf-token']) ) {
            $_SESSION['csrf-token'] = $token;
        }

        $check_csrf = false;

        if (preg_match('/^\/api/', current_route())) {
            $check_csrf = false;
        } elseif ( strtolower( $_SERVER['REQUEST_METHOD'] ) !== 'get') {
            $check_csrf = true;
        }
        
        if ( $check_csrf ) {
            if ( @ $this->getHeader('X-CSRF-TOKEN') != $token) {
                http_response_code(403);
                die('403 Forbiden');

                /**
                 * Set errorDocument 403
                 */
            }
        }
    }

    public function createParametersHash($params)
    {
        $hash = Security::createParametersHash($params);
        $_REQUEST['hash'] = $hash;
        return $hash;
    }

    /**
     * The URL hash parameter should be named hsh
     */
    public function verifyParameters() 
    {
        return Security::verifyParameters();
    }

    public static function getCurrentRequest()
    {
        if (static::$current_request) {
            return static::$current_request;
        }

        return null;
    }

    public function session($key, $value = null)
    {
        if ( isset($value) ) {
            $_SESSION[$key] = $value;
            return;
        }

        return isset( $_SESSION[$key] ) ? $_SESSION[$key]: null;
    }

    public function cookie($name, $value = null, $expiry = 604800, $path = '/')
    {
        if ( ! is_null($value) ) {
            setcookie($name, $value, time() + $expiry, $path);
            return;
        }

        return $_COOKIE[$name];
    }

    public function getToken()
    {
        $authorization = $this->getHeader('Authorization');
        
        if ($authorization) {
            return preg_replace('/^(Bearer )/i', '', $authorization);
        }
    }

    public function user() 
    {
        return new \App\Authenticate\User( $this->session('user-id') );
    }

    public function jsonSerialize() : mixed
    {
        return $_REQUEST;
    }

    public function redirect()
    {
        return new RequestRedirect;
    }

    public function request($param = null)
    {
        return isset($param) ? request($param): request();
    }
}