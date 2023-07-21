<?php

namespace Clicalmani\Flesco\Console\Commands\Create;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateBase extends Command
{

    protected static $defaultName = "";

    protected string $description = "";
    protected string $help = "";
    protected string $_path = "";
    protected string $prototype = "";

    protected function configure()
    {

        $this
            ->setDescription($this->description)
            ->setHelp($this->help)
            ->addArgument('name', InputArgument::REQUIRED, 'The filename')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $path = dirname(__DIR__, 8) . $this->_path;
        // $path = __DIR__ . "/"; // (for testing)

        $file = $path . $name . '.php';

        $content = file_get_contents($this->prototype);

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
