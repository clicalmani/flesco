<?php

/**
 * |---------------------------------------------------------------
 * |            ***** Register Console Commands *****
 * |---------------------------------------------------------------
 * 
 * Register Console commands
 */

 return [
    \Clicalmani\Flesco\Console\Commands\Local\StartCommand::class,
    \Clicalmani\Flesco\Console\Commands\Makes\MakeMigrationCommand::class,
    \Clicalmani\Flesco\Console\Commands\Makes\MakeModelCommand::class,
    \Clicalmani\Flesco\Console\Commands\Makes\MakeControllerCommand::class,
    \Clicalmani\Flesco\Console\Commands\Makes\MakeRequestCommand::class,
    \Clicalmani\Flesco\Console\Commands\Makes\MakeMiddlewareCommand::class
 ];
