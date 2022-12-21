<?php
namespace Clicalmani\Flesco\Http\Requests;

use Clicalmani\Flesco\Http\Controllers\RequestController;
use Clicalmani\Flesco\Http\Requests\RequestFile;
use Clicalmani\Flesco\Http\Requests\RequestRedirect;
use Clicalmani\Flesco\Security\Security;

class Request extends HttpRequest implements \ArrayAccess {

    private $signatures;

    public static function render() {
        /**
         * Does not need to be implemented here
         * @see RequestController::render for implementation
         */
    }

    public function validate($options = [])
    {
        $this->merge($options);
    }

    public function __construct( $signatures = [] ) {
        $this->signatures = $signatures;
    }

    public function __get($property) 
    {
        $sanitized = Security::sanitizeVars($_REQUEST, $this->signatures);
        
		if ( isset($sanitized[$property])) {
			return $sanitized[$property];
		} elseif (isset($_REQUEST[$property])) {
            return $_REQUEST[$property];
        }

        switch( $property ) {
            case 'redirect':
                return new RequestRedirect;
            break;
        }

		return null;
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

    public function offsetExists( $property ) {
        return ! is_null($this->$property);
    }

    public function offsetGet( $property ) {
        return $this->$property;
    }

    public function offsetSet( $property, $value ) {
        $this->$property = $value;
    }

    public function offsetUnset( $property ) {
        if ($this->$property) {
            $this->$property = null;
        }
    }

    public function redirect() {
        return new RequestRedirect;
    }

    public function download($filename, $filepath) 
    {
        header('Content-Type: ' . mime_content_type($filepath));
        header("Content-Disposition: attachment; filename=$filename");
        return readfile($filepath);
    }

    public function merge($new_signatures = [])
    {
        $this->signatures = array_merge($this->signatures, $new_signatures);
    }

    public function getHeaders()
    {
        return getallheaders();
    }

    public function getHeader($header_name)
    {
        foreach ($this->getHeaders() as $name => $header) {
            if ($name == $header_name) return $header;
        }
    }

    public function setHeader($name, $value)
    {
        header("$name: $value");
    }

    public function geMethod()
    { 
        return strtolower( $_SERVER['REQUEST_METHOD']);
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

        if (preg_match('/^api/', current_route())) {
            $check_csrf = true;
        } elseif ( strtolower( $_SERVER['REQUEST_METHOD'] ) !== 'get') {
            $check_csrf = true;
        }
        
        if ( $check_csrf ) {
            if ( @ $this->getHeader('X-CSRF-TOKEN') != $token) {
                http_response_code(403);
                die('Forbiden');

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

    public function session($key, $value = null)
    {
        if ( isset($value) ) {
            $_SESSION[$key] = $value;
            return;
        }

        return $_SESSION[$key];
    }

    public function user()
    {
        // Check provider
        $user_manage = \Clicalmani\Flesco\Providers\ServiceProvider::$providers;

        if ( isset($user_manage['users']) AND isset($user_manage['users']['manage']) ) {
            // $provider = new $user_manage['users']['manage']( $this->session('user-id') );
            $provider = new $user_manage['users']['manage']( 1 );

            return $provider;
        }
    }
}