<?php

namespace Clicalmani\Flesco\Console\Commands\Create\Models;

use Clicalmani\Flesco\Console\Commands\Create\CreateBase;

class CreateModel extends CreateBase 
{

    protected static $defaultName = 'create:model';

    protected string $description = 'Create a new model';

    protected string $help = 'Create a new model';

    protected string $_path = '/app/models/';

    protected string $prototype = __DIR__ . '/Prototype.php';

}