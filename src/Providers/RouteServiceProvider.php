<?php
namespace Clicalmani\Flesco\Providers; 

use Clicalmani\Routes\Route;

/**
 * RouteServiceProvider class
 * 
 * @package clicalmani/flesco 
 * @author @clicalmani
 */
abstract class RouteServiceProvider extends ServiceProvider
{
    /**
     * API prefix
     * 
     * @var string
     */
    protected $api_prefix = 'api';

    /**
     * Default api handler
     * 
     * @var string
     */
    protected $api_handler = 'routes/api.php';

    /**
     * Default web handler
     * 
     * @var string
     */
    protected $web_handler = 'routes/web.php';
    
    /**
     * Initialize route service
     * 
     * @param callable $callback
     */
    public function routes(callable $callback)
    {
        if ( Route::isApi() ) {
            $this->setHeaders();
        } else $this->storeCSRFToken();

        $callback();
    }

    /**
     * Get api prefix
     * 
     * @return string
     */
    public function getApiPrefix()
    {
        return $this->api_prefix;
    }

    /**
     * Set response headers
     * 
     * @return void
     */
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
         * Prefilght request is meant to check wether the CORS protocol is understood
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

    /**
     * Store CSRF token
     * 
     * @return void
     */
    public function storeCSRFToken()
    {
        // Escape console mode
        if ( inConsoleMode() ) return;
        
        // Start a session
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate CSRF token and Store it in $_SESSION global variable
        if ( ! isset($_SESSION['csrf-token']) ) {
            $_SESSION['csrf-token'] = with ( new \Clicalmani\Flesco\Security\CSRF )->getToken(); 
        }
    }
}
