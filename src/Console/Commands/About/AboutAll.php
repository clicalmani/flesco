<?php

namespace Clicalmani\Flesco\Console\Commands\About;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AboutAll extends Command
{
    protected static $defaultName = 'about:all';

    protected function configure()
    {
        $this
            ->setDescription('Show information about a application')
            ->setHelp('Show information about a application')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Logique de traitement de votre commande
        return Command::SUCCESS;
    }
}
