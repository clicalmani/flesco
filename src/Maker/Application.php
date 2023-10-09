<?php
namespace Clicalmani\Flesco\Maker;

/**
 * Make an application
 * 
 * @package Clicalmani\Flesco
 * @author clicalmani
 */
class Application extends \Symfony\Component\Console\Application
{
    public function __construct(private $root_path = null)
    {
        parent::__construct();
    }

    public function make()
    {
        // Console Kernel
        $kernel = \Clicalmani\Console\Kernel::$kernel;

        foreach ($kernel as $command) {
            $this->add(new $command($this->root_path));
        }
    }
}
