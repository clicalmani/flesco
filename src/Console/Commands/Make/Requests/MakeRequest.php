<?php

namespace Clicalmani\Flesco\Console\Commands\Make\Requests;

use Clicalmani\Flesco\Console\Commands\Make\MakeBase;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'make:request',
    description: 'Create a new request',
    hidden: false
)]
class MakeRequest extends MakeBase
{
    protected string $help = 'Create a new request';

    protected string $_path = 'app/http/requests/';

    protected string $prototype = __DIR__ . '/Prototype.php';

}