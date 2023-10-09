<?php
namespace Clicalmani\Flesco\Resources\Views;

use Clicalmani\Flesco\Sandbox\Sandbox;

class View 
{
    static function render( ...$args ) 
    {
        if ( ! isset( $args[0] ) ) {
            return '';
        }
        
        $template_path = resources_path( '/views/' . $args[0] . '.template.php' );

        if ( file_exists( $template_path ) AND is_readable( $template_path ) ) {

            $args = ( isset( $args[1] ) AND is_array( $args[1] ) ) ? $args[1]: [];
            return @ Sandbox::eval(file_get_contents($template_path), $args);
        }

        throw new \Clicalmani\Flesco\Exceptions\ResourceViewException('No resource found');
    }
}
