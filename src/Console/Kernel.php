<?php

namespace Clicalmani\Flesco\Console;


return [
    
    /** --------- LOCAL COMMANDS ----------- */

    \Clicalmani\Flesco\Console\Commands\Local\Server::class,

    /** --------- CREATE COMMANDS -------- */

    \Clicalmani\Flesco\Console\Commands\Create\Controllers\CreateController::class,
    \Clicalmani\Flesco\Console\Commands\Create\Models\CreateModel::class,
    \Clicalmani\Flesco\Console\Commands\Create\Middlewares\CreateMiddleware::class,

];