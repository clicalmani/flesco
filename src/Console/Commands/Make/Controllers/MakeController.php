<?php

namespace Clicalmani\Flesco\Console\Commands\Make\Controllers;

use Clicalmani\Flesco\Console\Commands\Make\MakeBase;
use Symfony\Component\Console\Attribute\AsCommand;


#[AsCommand(
    name: 'make:controller',
    description: 'Create a new controller',
    hidden: false
)]
class MakeController extends MakeBase 
{
    protected string $help = 'Create a new controller';

    protected string $_path = 'app/http/controllers/';

    protected string $prototype = __DIR__ . '/Prototype.php';

}