<?php 
namespace Clicalmani\Flesco\Support;

class Log 
{
    public static function init()
    {
        ini_set('log_errors', 1);
        ini_set('error_log', storage_path( '/errors/errors.log' ) );
    }
}
