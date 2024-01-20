<?php
namespace Clicalmani\Flesco\Events;

class Event 
{
    protected static $instance;
    protected $observers = [];

    public function __construct()
    {
        $this->observers = require_once config_path('/event.php');
    }

    public function getInstance()
    {
        if (self::$instance === NULL) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function dispatch(string $event, mixed $data)
    {
        $observers = @ $this->observers[$event] ?? [];

        foreach ($observers as $observer) {
            with( new $observer)->notify($data);
        }
    }
}
