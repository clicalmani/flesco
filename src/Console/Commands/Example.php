<?php

namespace Clicalmani\Flesco\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'about:all',
    description: 'Show information about a application',
    hidden: false
)]
class Example extends Command
{
    protected static $defaultName = 'test';

    protected function configure()
    {
        $this
            ->setDescription('Description de ma commande')
            ->setHelp('Aide détaillée pour ma commande')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Logique de traitement de votre commande
        $output->writeln('Exécutée avec succès!');
        return Command::SUCCESS;
    }
}
