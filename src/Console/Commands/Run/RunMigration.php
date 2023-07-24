<?php

namespace Clicalmani\Flesco\Console\Commands\Run;

use Clicalmani\Flesco\Database\Factory\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'run:migration',
    description: 'Run a database migration',
    hidden: false
)]
class RunMigration extends Command
{
    protected static $defaultName = 'test';

    protected function configure()
    {
        $this
            ->setHelp('Run a database migration')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the migration')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    
        $name = $input->getArgument('name');

        $root_path = getenv('APP_ROOT_PATH') ?? "";

        $migration_path = $root_path . '/database/migrations/';
        // $migration_path = $root_path . '/src/test/'; // For testing

        $search_pattern = $migration_path . '*_' . $name . '.php';

        $result = glob($search_pattern);
        
        if(! $result) {

            $output->writeln("<error>Migration file not found</error>");
            return Command::FAILURE;
        }

        $class = require($result[0]);

        try {

            $class -> create();

            $output->writeln("\n<info>Migration run succefully !</info>");


        }catch (\Error | \Exception $e) {

            $output->writeln("\n<error>Migration failed</error>");
            $output->writeln("\n<error>Message : </error>" . $e->getMessage());
            $output->writeln("\n<error>File : </error>" . $e->getFile() . ":" . $e->getLine());

            return Command::FAILURE;
        }

        
        return Command::SUCCESS;
    }
}
