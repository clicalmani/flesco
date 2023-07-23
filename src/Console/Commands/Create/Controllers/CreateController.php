<?php

namespace Clicalmani\Flesco\Console\Commands\Create\Controllers;

use Clicalmani\Flesco\Console\Commands\Create\CreateBase;

$name = 'prefix';
class CreateController extends CreateBase 
{
    protected static $defaultName = 'make:controller';

    protected string $description = 'Create a new controller';

    protected string $help = 'Create a new controller';

    protected string $_path = '/app/http/controllers/';

    protected string $prototype = __DIR__ . '/Prototype.php';

}