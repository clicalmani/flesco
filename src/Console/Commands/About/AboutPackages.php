<?php

namespace Clicalmani\Flesco\Console\Commands\About;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'about:packages',
    description: 'Show information about a packages and their dependencies',
    hidden: false
)]
class AboutPackages extends Command
{

    protected function configure()
    {
        $this
            ->setHelp('Show information about a packages and their dependencies')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $process = new Process(["composer", "show"]);

        $process -> setTimeout(null);
        $process->run(function($type, $buffer) use ($output) {

            $output->writeln("\n<info>$buffer</info>");
        });

        return Command::SUCCESS;

    
    }
}
