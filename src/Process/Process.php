<?php
namespace Clicalmani\Flesco\Process;

/**
 * Class Process
 * 
 * @package Clicalmani\Flesco
 * @author @clicalmani
 */
abstract class Process 
{
    /**
     * Prcess route
     * 
     * @var string
     */
    protected string $route;

    /**
     * Reconnection time
     * 
     * @var int
     */
    protected static int $reconnectDelay = 5000;

    /**
     * Callable
     * 
     * @var callable
     */
    protected static $beforeExit;

    /**
     * No cache
     * 
     * @var bool
     */
    protected static bool $nocache = true;

    protected function setEvent($event)
    {
        print "event: $event\n";
        print "id: " . uniqid() . "\n";
    }

    /**
     * Lunch the process
     * 
     * @return void
     */
    public function lunch() : void
    {
        if (static::$nocache) header('Cache-Control: no-cache');
        header('Content-Type: text/event-stream');

        print "retry: " . self::$reconnectDelay . "\n";
    }

    /**
     * Send message
     * 
     * @param string $message
     * @return void
     */
    public static function send(string $message) : void
    {
        print "data: $message\n";
        ob_end_flush();
        flush();

        // if (connection_aborted()) break;

        // sleep(1);
    }

    /**
     * Kill the process
     * 
     * @return void
     */
    public static function kill() : void
    {
        if (NULL !== static::$beforeExit) call(static::$beforeExit);

        self::send(-1);
    }
}
