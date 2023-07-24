<?php

namespace Clicalmani\Flesco\Console\Commands\Make\Migrations;

use Clicalmani\Flesco\Console\Commands\Make\MakeBase;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'make:migration',
    description: 'Create a new migration file',
    hidden: false
)]
class MakeMigration extends MakeBase
{
    protected string $help = 'Create a new migration';

    protected string $_path = 'database/migrations/';

    protected string $prototype = __DIR__ . '/Prototype.php';

    protected bool $filenameDatePrefix = true;

}