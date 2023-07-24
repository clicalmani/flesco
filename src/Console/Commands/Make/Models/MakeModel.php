<?php

namespace Clicalmani\Flesco\Console\Commands\Make\Models;

use Clicalmani\Flesco\Console\Commands\Make\MakeBase;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'make:model',
    description: 'Create a new model instance',
    hidden: false
)]
class MakeModel extends MakeBase
{
    protected string $help = 'Create a new model';

    protected string $_path = 'app/models/';

    protected string $prototype = __DIR__ . '/Prototype.php';

}