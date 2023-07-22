<?php

namespace Clicalmani\Flesco\Console;

/**
 * |-------------------------------------------------------------------------------
 * |                ***** Register Console Commands *****
 * |-------------------------------------------------------------------------------
 * 
 * Import the new commands here for registration
 * 
 */

return [
    
    /** --------- LOCAL COMMANDS ----------- */

    \Clicalmani\Flesco\Console\Commands\Local\Server::class,

    /** --------- CREATE COMMANDS -------- */

    \Clicalmani\Flesco\Console\Commands\Create\Controllers\CreateController::class,
    \Clicalmani\Flesco\Console\Commands\Create\Models\CreateModel::class,
    \Clicalmani\Flesco\Console\Commands\Create\Middlewares\CreateMiddleware::class,
    \Clicalmani\Flesco\Console\Commands\Create\Requests\CreateRequest::class,
    \Clicalmani\Flesco\Console\Commands\Create\Providers\CreateServiceProvider::class,

];