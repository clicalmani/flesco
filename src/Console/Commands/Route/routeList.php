<?php

namespace Clicalmani\Flesco\Console\Commands\Route;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'route:list',
    description: 'Show application routes',
    hidden: false
)]
class RouteList extends Command
{

    protected function configure()
    {
        $this
            ->setHelp('Show application routes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $root_path = getenv('APP_ROOT_PATH') ?? "";
        $routes_path = $root_path . "/routes/";

        $files = scandir($routes_path);

        if (!$files) {

            $output->writeln("\n<error>Routes directory not found !</error>");
            return Command::FAILURE;
        }

        $table = new Table($output);
        $table->setHeaders(['Type', 'Method', 'Path', 'Action']);
        $tab = [];


        foreach ($files as $file) {

            if ($file !== '.' && $file !== "..") {
                
                $content = file_get_contents($routes_path . $file);
                
                $pattern = '/Route::([a-zA-Z]+)\([\'"]([^\'"]+)[\'"], \[([^\]]+)\]\);/';

                $type = explode('.', $file)[0];

                if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {

                    foreach ($matches as $match) {
                        $tab[] = [$type, $match[1], $match[2], $match[3]];
                    }
                    
                    $table->setRows($tab);

                }

            }
        }
        $table->render();

        return Command::SUCCESS;
    }
}
