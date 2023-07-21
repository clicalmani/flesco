<?php

namespace Clicalmani\Flesco\Console\Commands\Controllers;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Command
{
    protected static $defaultName = 'create:controller';

    protected function configure()
    {
        $this
            ->setDescription('Create a new controller')
            ->setHelp('Create a new controller')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the controller')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $path = dirname(__DIR__, 8) . '/app/http/controllers/';
        // $path = __DIR__ . "/"; // (for testing)

        $file = $path . $name . '.php';

        $content = file_get_contents(__DIR__ . "/Prototype.php");

        if (! file_exists($file)) {

            $content = str_replace('ClassName', $name, $content);
            
            if (file_put_contents($file, $content)) {

                $output->writeln("<info>[$file] : created successfully </info>");

            }

            return Command::SUCCESS;
        }

        $output->writeln('<comment>File already exists !</comment>');
        return Command::FAILURE;
    }
}
