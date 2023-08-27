<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Symfony\Component\String\u;

#[AsCommand(name: 'development', description: 'Just a command to quickly test stuff out', hidden: true)]
class DevelopmentCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        echo u('SOMETHING STRING 213')->lower()->title(true);

        return self::SUCCESS;
    }
}
