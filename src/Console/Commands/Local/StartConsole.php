<?php
namespace Clicalmani\Flesco\Console\Commands\Local;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'console',
    description: 'Start and manage your application in the console',
    hidden: false
)]
class StartConsole extends Command
{
    private $defaultPort = 8000;

    protected function configure() : void
    {
        $this->setHelp('Start and manage your application in the console');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {

        
        $phpFinder = new PhpExecutableFinder();

        $phpPath = $phpFinder->find();

        if ($phpPath) {
            
            $cmd = ['php', 'vendor/bin/psysh'];
            
            if(strpos(PHP_OS, 'WIN') == 0) {

                $descriptorspec = [
                    0 => STDIN,
                    1 => STDOUT,
                    2 => STDERR
                ];
                
                $process = proc_open(implode(' ', $cmd), $descriptorspec, $pipes);

                if (is_resource($process)) {

                    proc_close($process);
                }

            } else {


                $process = new Process($cmd);
                $process->setTty(true);
                $process->run();

                if (!$process->isSuccessful()) {

                    $output->writeln('<error>Une erreur s\'est produite lors de l\'exécution de PsySH :</error>');
                    $output->writeln($process->getErrorOutput());

                    return Command::FAILURE;

                }
            }

            return Command::SUCCESS;

        }
        
        $output->writeln("<error>Php not found in path. Please fix it and retry !</error>");

        return Command::FAILURE;
    }
}
