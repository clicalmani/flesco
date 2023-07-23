<?php

namespace Clicalmani\Flesco\Console;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class Console {


    private ?Application $application = null;

    public function __construct( string $appName = "Tonka Console", string $appVersion = "0.0.1") {

        $this->application = new Application($appName, $appVersion);

        $this->register();

    }

    public function register() {

        $commands = require(__DIR__ . '/Kernel.php');

        foreach($commands as $command) {
            
            $this->application->add(new $command);
        }

        $this->application->run();
    }
}