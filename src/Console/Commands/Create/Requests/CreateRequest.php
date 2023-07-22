<?php

namespace Clicalmani\Flesco\Console\Commands\Create\Requests;

use Clicalmani\Flesco\Console\Commands\Create\CreateBase;

class CreateRequest extends CreateBase 
{

    protected static $defaultName = 'create:request';

    protected string $description = 'Create a new request';

    protected string $help = 'Create a new request';

    protected string $_path = '/app/http/requests/';

    protected string $prototype = __DIR__ . '/Prototype.php';

}