<?php

namespace Clicalmani\Flesco\Console\Commands\About;

use Clicalmani\Flesco\Console\Console;
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
        $root_path = getenv('APP_ROOT_PATH') ?? '';

        $envFile = $root_path . '/.env';

        if (file_exists($envFile)) {
            
            $file = fopen($envFile, 'r');

            echo "\n";
            while(!feof($file)) {

                $line = fgets($file);
                $line = str_replace(" ", "", $line);
                $data = explode("=", $line);
                Console::printLine($output, $data);
                
            }

            fclose($file);

            return Command::SUCCESS;

        }

        $output->writeln("\n<comment>Application configuration file not found</comment>");

        return Command::FAILURE;

    
    }
}
