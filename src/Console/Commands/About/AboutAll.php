<?php

namespace Clicalmani\Flesco\Console\Commands\About;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'about:all',
    description: 'Show information about a application',
    hidden: false
)]
class AboutAll extends Command
{

    protected function configure()
    {
        $this
            ->setHelp('Show information about a application')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Logique de traitement de votre commande
        return Command::SUCCESS;
    }
}
