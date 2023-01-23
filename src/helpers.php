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

if ( ! function_exists( 'asset' ) ) {
    function asset($path) {
        echo $path;
    }
}

if ( ! function_exists( 'password' ) ) {
    function password($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}