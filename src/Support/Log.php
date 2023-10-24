<?php 
namespace Clicalmani\Flesco\Support;

/**
 * Log Class
 * 
 * @package clicalmani/flesco 
 * @author @clicalmani
 */
class Log extends Mock
{
    /**
     * Log file name
     * 
     * @var string
     */
    protected const ERROR_LOG = 'errors.log';

    /**
     * Log errors to file
     * 
     * @return void
     */
    public function _init() : void
    {
        $errors_path = storage_path('/errors');

        if ( !file_exists($errors_path) ) {
            mkdir($errors_path);
        }

        ini_set('log_errors', 1);
        ini_set('error_log', "$errors_path/errors.log" );
    }

    /**
     * Log custom error
     * 
     * @param string $error_message
     * @param ?int $error_level PHP error level
     * @param ?string $file Error file name
     * @param ?int $line Error line
     * @return void
     */
    public function _error(string $error_message, ?int $error_level = E_ERROR, ?string $file = 'Unknow', ?int $line = null) : void
    {
        switch ($error_level) {
            case E_NOTICE: 
            case E_USER_NOTICE: 
                $error_type = 'PHP Notice'; 
                break;  
            case E_WARNING: 
            case E_USER_WARNING: 
                $error_type = 'PHP Warning'; 
                break; 

            case E_ERROR: 
            case E_USER_ERROR: 
                $error_type = 'PHP Fatal Error'; 
                $EXIT = TRUE; 
                break; 

            case E_PARSE:
                $error_type = 'PHP Parse Error';
                break;

            # Handle the possibility of new error constants being added 
            default: 
                $error_type = 'PHP Unknown'; 
                $EXIT = TRUE; 
                break; 
        } 

        error_log(sprintf("[%s] %s: %s in %s on line %d\n", date('Y-M-d H:i:s T', time()), $error_type, $error_message, $file, $line), 3, $this->maybeCreateLog());

        if (true === @ $EXIT) exit;
    }

    /**
     * Log custom warning
     * 
     * @param string $warning_message
     * @param ?string $file Error file name
     * @param ?int $line Error line
     * @return void
     */
    public function _warning(string $warning_message, ?string $file = 'Unknow', ?int $line = null) : void
    {
        $this->_error($warning_message, E_WARNING, $file, $line);
    }

    /**
     * Log custom notice
     * 
     * @param string $notice_message
     * @param ?string $file Error file name
     * @param ?int $line Error line
     * @return void
     */
    public function _notice(string $notice_message, ?string $file = 'Unknow', ?int $line = null)
    {
        $this->_error($notice_message, E_NOTICE, $file, $line);
    }
    
    /**
     * May create error log file
     * 
     * @return string Log file path
     */
    private function maybeCreateLog()
    {
        if ( ! file_exists( storage_path('/errors') ) ) {
            mkdir( storage_path('/errors') );
        }

        return storage_path('/errors/' . static::ERROR_LOG);
    }
}
