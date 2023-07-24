<?php

namespace Clicalmani\Flesco\Console\Commands\Make;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeBase extends Command
{
    protected string $help = "";
    protected string $_path = "";
    protected string $prototype = "";

    protected function configure()
    {

        $this
            ->setHelp($this->help)
            ->addArgument('name', InputArgument::REQUIRED, 'The filename')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('name') . '.php';

        $root_path = getenv('APP_ROOT_PATH') ?? "";

        $file = $root_path . DIRECTORY_SEPARATOR . $this->_path . $filename;

        $content = file_get_contents($this->prototype);

        if (! file_exists($file)) {

            $content = str_replace('ClassName', $filename, $content);
            
            if (file_put_contents($file, $content)) {

                $output->writeln("<info>[$file] : created successfully </info>");

            }

            return Command::SUCCESS;
        }

        $output->writeln('<comment>File already exists !</comment>');
        return Command::FAILURE;
    }
}
