<?php

namespace Clicalmani\Flesco\Console\Commands\Create\Middlewares;

use Clicalmani\Flesco\Console\Commands\Create\CreateBase;

class CreateMiddleware extends CreateBase 
{

    protected static $defaultName = 'make:middleware';

    protected string $description = 'Create a new middleware';

    protected string $help = 'Create a new middleware';

    protected string $_path = '/app/http/middleware/';

    protected string $prototype = __DIR__ . '/Prototype.php';

}