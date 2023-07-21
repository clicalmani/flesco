<?php

namespace Clicalmani\Flesco\Console\Commands\Local;

use Clicalmani\Flesco\Console\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class Server extends Command
{
    protected static $defaultName = 'serve';

    public $defaultAddress = "localhost:4000";

    protected function configure()
    {

        $this
            ->setDescription('Start local development server')
            ->setHelp('Start local development server')
            ->addArgument('address', InputArgument::OPTIONAL, "The address on which the development server will start", $this->defaultAddress)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $phpFinder = new PhpExecutableFinder();

        $phpPath = $phpFinder->find();

        if ($phpPath) {

            $serverPath = dirname(__DIR__, 8) . '/server.php';
            
            $cmd = [$phpPath, '-s', '-S', $input->getArgument('address'), $serverPath];
            $process = new Process($cmd);
            $process->setTimeout(null);
            $output->writeln("\n<info>Starting ...</info>\n");
            $process->run(function($type, $buffer) use ($output) {

                // if (Process::ERR == $type) {

                //     $output->writeln("<error>$buffer</error>");
                //     return Command::FAILURE;
                // }

                $output->writeln("<info>$buffer</info>");
            });

            return Command::SUCCESS;

        }
        
        $output->writeln("<error>Php not found in path. Please fix it and retry !</error>");

        return Command::FAILURE;
    }
}
