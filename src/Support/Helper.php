<?php
namespace Clicalmani\Flesco\Support;

/**
 * Class Helper
 * 
 * @package Clicalmani\Flesco
 * @author @Clicalmani\Flesco
 */
class Helper 
{
    /**
     * Include helper functions
     * 
     * @return void
     */
    public static function include()
    {
        include_once dirname( __DIR__ ) . '/helpers.php';
    }
}
