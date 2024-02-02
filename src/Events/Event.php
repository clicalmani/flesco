<?php
namespace Clicalmani\Flesco\Events;

use App\Providers\EventServiceProvider;

/**
 * Class Event
 * 
 * @package Clicalmani\Flesco
 * @author @clicalmani
 */
abstract class Event 
{
    /**
     * Current instance
     * 
     * @var static
     */
    protected static $instance;
    
    /**
     * Create unique instance
     * 
     * @return static
     */
    public function getInstance() : static
    {
        if (self::$instance === NULL) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Dispatch event
     * 
     * @param mixed $data
     * @return void
     */
    public static function dispatch(mixed $data) : void
    {
        foreach (EventServiceProvider::getEventListeners(static::class) as $listener) {
            with( new $listener)->notify($data);
        }
    }

    /**
     * Add listeners
     * 
     * @param string ...$listeners
     * @return void
     */
    public static function listen(string ...$listeners) : void
    {
        foreach ($listeners as $listener) EventServiceProvider::listenEvent(self::class, $listener);
    }
}
