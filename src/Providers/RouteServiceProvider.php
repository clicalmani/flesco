<?php
namespace Clicalmani\Flesco\Providers; 

use Clicalmani\Flesco\Routes\Route;

abstract class RouteServiceProvider extends ServiceProvider
{
    protected $api_prefix = 'api';

    protected $api_handler = 'routes/api.php';

    protected $web_handler = 'routes/web.php';

    abstract public function boot();

    public function routes($callable)
    {
        if ( Route::isApi() ) {
            $this->setHeaders();
        } else $this->storeCSRFToken();

        $callable();
    }

    public function getApiPrefix()
    {
        return $this->api_prefix;
    }

    public function setHeaders()
    {
        if ( isset($_SERVER['HTTP_ORIGIN']) ) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');				// Cache preflight response for one (1) day
        }
    
        /**
         * |-------------------------------------------------------------------
         * |                ***** Preflight Routes *****
         * |-------------------------------------------------------------------
         * 
         * API Request is composed of preflight request and request
         * Prefilght request is meant to check if the COORS protocol is understood
         */
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");         
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
                // Preflight
                response()->status(204, 'PREFLIGHT', '');
                exit;
        }
    }

    public function storeCSRFToken()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    
        $csrf = new \Clicalmani\Flesco\Security\CSRF;
        $token = $csrf->getToken();					// Generate CSRF token for the current session
    
        if ( ! isset($_SESSION['csrf-token']) ) {
            $_SESSION['csrf-token'] = $token;		// Stock the token in $_SESSION global variable
        }
    }
}