<?php

if ( ! function_exists( 'is_serialized' ) ) {
    function is_serialized( $data, $strict = true ) { 
        // If it isn't a string, it isn't serialized.
        if ( ! is_string( $data ) ) {
            return false;
        }

        $data = trim( $data );

        if ( 'N;' === $data ) {
            return true;
        }

        if ( strlen( $data ) < 4 ) {
            return false;
        }

        if ( ':' !== $data[1] ) {
            return false;
        }

        if ( $strict ) {
            $lastc = substr( $data, -1 );
            if ( ';' !== $lastc && '}' !== $lastc ) {
                return false;
            }
        } else {
            $semicolon = strpos( $data, ';' );
            $brace     = strpos( $data, '}' );
            // Either ; or } must exist.
            if ( false === $semicolon && false === $brace ) {
                return false;
            }
            // But neither must be in the first X characters.
            if ( false !== $semicolon && $semicolon < 3 ) {
                return false;
            }
            if ( false !== $brace && $brace < 4 ) {
                return false;
            }
        }

        $token = $data[0];

        switch ( $token ) {
            case 's':
                if ( $strict ) {
                    if ( '"' !== substr( $data, -2, 1 ) ) {
                        return false;
                    }
                } elseif ( false === strpos( $data, '"' ) ) {
                    return false;
                }
                // Or else fall through.
            case 'a':
            case 'O':
                return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
        }

        return false;
    }
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
    function maybe_unserialize( $data ) {
        if ( is_serialized( $data ) ) { 
            return @unserialize( trim( $data ) );
        }
    
        return $data;
    }
}

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