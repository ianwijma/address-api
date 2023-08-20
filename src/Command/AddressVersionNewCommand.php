<?php

namespace App\Command;

use App\Repository\VersionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'address:version:new', description: 'Create a new version')]
class AddressVersionNewCommand extends Command
{
    public function __construct(private readonly VersionRepository $repository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nextVersion = $this->repository->createNewVersion();

        $output->writeln(sprintf(
            'Newly created version: %d',
            $nextVersion->getVersionNumber()
        ));

        return self::SUCCESS;
    }
}
