<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GreetCommand extends Command
{
    protected static $defaultName = 'GreetCommand';

    protected function configure()
    {
        $this
            ->setDescription('A greet command.')
            ->addArgument('name', InputArgument::OPTIONAL, 'Argument description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');

        if (in_array($name, ['Foo', 'Bar'])) {
            $io->error('I don\' like Foo and Bar!');

            return 1;
        }

        if (!$name) {
            $name = 'World';
        }

        $io->writeln(sprintf('Hello %s', $name));

        return 0;
    }
}

#         $arg1 = $input->getArgument('arg1');
