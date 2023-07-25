<?php

namespace Clicalmani\Flesco\Console;
use Symfony\Component\Console\Output\OutputInterface;

class Console {


    public static function printLine(OutputInterface $output, array $data, int $sleepTime = 500) : void {

        if(count($data) > 1) {

            $key = $data[0] ?? "";
            $value = $data[1] ?? "";

            $output->write("<info>$key</info>");

            for($i = 1; $i < 25; $i++) {
                $output->write(". ");
                usleep($sleepTime);
            }
            
            $output->writeln("<comment>$value</comment>");
        }

    }
}