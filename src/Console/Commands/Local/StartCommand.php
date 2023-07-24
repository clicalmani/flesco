<?php
namespace Clicalmani\Flesco\Console\Commands\Local;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'dev',
    description: 'Start web server',
    hidden: false
)]
class StartCommand extends Command
{
    private $defaultPort = 8000;

    protected function configure() : void
    {
        $this->setHelp('This command start the web server');
        $this->setDefinition([
            new InputOption('port', 'p', InputOption::VALUE_REQUIRED, 'Host port', $this->defaultPort)
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $port = $input->getOption('port');
        
        $phpFinder = new PhpExecutableFinder();

        $phpPath = $phpFinder->find();

        if ($phpPath) {
            
            $cmd = [$phpPath, '-s', '-S', "localhost:$port", "server.php"];
            $process = new Process($cmd);
            $process->setTimeout(null);
            $output->writeln("\n<info>Starting ...</info>\n");
            $process->run(function($type, $buffer) use ($output) {
                $output->writeln("$buffer");
            });

            return Command::SUCCESS;

        }
        
        $output->writeln("<error>Php not found in path. Please fix it and retry !</error>");

        return Command::FAILURE;
    }
}
