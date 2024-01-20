<?php
namespace Clicalmani\Flesco\Process;

class Process 
{
    protected string $event = 'data';
    protected static $beforeExit;
    protected static bool $nocache = true;

    public function lunch()
    {
        if (static::$nocache) header('Cache-Control: no-cache');
        header('Content-Type: text/event-stream');
    }

    public function send(string $message)
    {
        print self::$event . ": $message";
        flush();
    }

    public function exit()
    {
        if (NULL !== static::$beforeExit) call(static::$beforeExit);

        $this->send(-1);
    }
}
