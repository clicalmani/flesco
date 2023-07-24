<?php

namespace Clicalmani\Flesco\Console\Commands\Make\Middlewares;

use Clicalmani\Flesco\Console\Commands\Make\MakeBase;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'make:middleware',
    description: 'Create a new middleware application',
    hidden: false
)]
class MakeMiddleware extends MakeBase
{
    protected string $help = 'Create a new middleware';

    protected string $_path = 'app/http/middleware/';

    protected string $prototype = __DIR__ . '/Prototype.php';

}