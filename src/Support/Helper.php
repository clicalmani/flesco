<?php
namespace Clicalmani\Flesco\Support;

/**
 * Helper class
 * 
 * @package Clicalmani\Flesco
 * @author clicalmani
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
