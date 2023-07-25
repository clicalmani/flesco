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

    /** --------- MAKER COMMANDS -------- */

    \Clicalmani\Flesco\Console\Commands\Make\Controllers\MakeController::class,
    \Clicalmani\Flesco\Console\Commands\Make\Models\MakeModel::class,
    \Clicalmani\Flesco\Console\Commands\Make\Middlewares\MakeMiddleware::class,
    \Clicalmani\Flesco\Console\Commands\Make\Requests\MakeRequest::class,
    \Clicalmani\Flesco\Console\Commands\Make\Providers\MakeServiceProvider::class,
    \Clicalmani\Flesco\Console\Commands\Make\Migrations\MakeMigration::class,

    /** --------- ABOUT COMMANDS ---------- */

    \Clicalmani\Flesco\Console\Commands\About\AboutAll::class,
    \Clicalmani\Flesco\Console\Commands\About\AboutPackages::class,

    /** RUN COMMANDS */

    \Clicalmani\Flesco\Console\Commands\Run\RunMigration::class,

    /** ROUTE COMMANDS */

    \Clicalmani\Flesco\Console\Commands\Route\RouteList::class,


 ];
