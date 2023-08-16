<?php
namespace Clicalmani\Flesco\Maker;

use Symfony\Component\Console\Command\Command;

class Application extends \Symfony\Component\Console\Application
{
    function __construct(private $root_path = null)
    {
        parent::__construct();
    }

    function make()
    {
        // Console Kernel
        $kernel = require_once dirname(__DIR__) . '/Console/Kernel.php';

        foreach ($kernel as $command) {
            $this->add(new $command($this->root_path));
        }
    }
}
