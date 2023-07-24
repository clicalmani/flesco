<?php

namespace Clicalmani\Flesco\Console\Commands\Make\Providers;

use Clicalmani\Flesco\Console\Commands\Make\MakeBase;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'make:provider',
    description: 'Create a new service provider',
    hidden: false
)]
class MakeServiceProvider extends MakeBase
{

    protected string $help = 'Create a new service provider';

    protected string $_path = 'app/providers/';

    protected string $prototype = __DIR__ . '/Prototype.php';

}