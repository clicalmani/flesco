<?php
namespace Clicalmani\Flesco\Resources\Views;

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
            return @ self::eval(file_get_contents($template_path), $args);
        }

        throw new \Clicalmani\Flesco\Exceptions\ResourceViewException('No resource found');
    }

    static function eval($exec, $args) {
        
        $args     = serialize($args);
        $tmp_name = '__.php';

        $content = <<<EVAL
        <?php
        \$serialized = <<<ARGS
        $args
        ARGS;
        extract(unserialize(\$serialized));

        return <<<DELIMITER
            $exec
        DELIMITER;
        EVAL;
        
        file_put_contents(temp_dir() . '/' . $tmp_name, $content);

        return include temp_dir() . '/' . $tmp_name;
    }
}
