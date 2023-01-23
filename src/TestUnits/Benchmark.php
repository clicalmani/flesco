<?php
namespace Clicalmani\Flesco\TestUnits;

use Clicalmani\Flesco\Security\Security;

class Benchmark
{
    private static $log;
    private static $log_id;

    public static function watchValue($value)
    {
        $date = strftime("%c", time());
        $log = self::getLog();
        fputs($log, "[$date] $value");
        // fclose($log);
    }

    public static function maybeCreateLog()
    {
        if ( !static::$log ) {
            if ( ! file_exists( storage_path('/test') ) ) {
                mkdir( storage_path('/test') );
            }

            static::$log_id = static::$log_id ? static::$log_id: bin2hex( Security::hash( time() ) );
            $handle = fopen( storage_path('/test/' . static::$log_id . '.log'), 'a+');
            // chmod( storage_path('/test/' . static::$log_id . '.log'), 0644 ); // Read and write for owner read for everbody else
            static::$log = $handle;
        }
    }

    public static function getLog()
    {
        self::maybeCreateLog();
        return static::$log;
    }
}