<?php
require_once 'config.php'; 
require_once bootstrap_path( '/providers.php' ); 

$custom_helpers = \Clicalmani\Flesco\Providers\ServiceProvider::$providers['helpers'];

if ( !empty($custom_helpers) ) {
    foreach ($custom_helpers as $helper) {
        $helper = realpath( root_path( '/' . $helper ) );
        if (file_exists($helper) AND is_readable($helper)) {
            include_once $helper;
        }
    }
}

if (preg_match('/^\/api/', current_route())) {
	if ( isset($_SERVER['HTTP_ORIGIN']) ) {
		header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Max-Age: 86400');				// Save for 1 day
	}

	// Access-Control headers during OPTIONS requests
	if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

		if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
			header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");         

		if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
			header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

			// Preflight
			http_response_code(204);
			exit;
	}
	
	require_once routes_path( '/api.php' );
} else {
	require_once routes_path( '/web.php' );
}