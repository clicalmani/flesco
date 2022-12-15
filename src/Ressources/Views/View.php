<?php
namespace Clicalmani\Flesco\Ressources\Views;

class View 
{
    static function render( ...$args ) 
    {
        if ( ! isset( $args[0] ) ) {
            return '';
        }

        $template_path = ressources_path( '/views/' . $args[0] . '.template.php' );

        if ( file_exists( $template_path ) AND is_readable( $template_path ) ) {

            if ( isset( $args[1] ) AND is_array( $args[1] ) ) {
                extract( $args[1] );
            }

            include_once $template_path;
            return;
        }

        throw new \Clicalmani\Flesco\Exceptions\RessourceViewException('No ressource found');
    }
}