<?php

namespace Clicalmani\Flesco\Console\Commands\Create\Providers;

use Clicalmani\Flesco\Console\Commands\Create\CreateBase;

class CreateServiceProvider extends CreateBase 
{

    protected static $defaultName = 'create:provider';

    protected string $description = 'Create a new service provider';

    protected string $help = 'Create a new provider';

    protected string $_path = '/app/providers/';

    protected string $prototype = __DIR__ . '/Prototype.php';

}