<?php

namespace App\Command;

use App\Repository\VersionRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'address:version:activate', description: 'Switches the active version')]
class AddressVersionActivateCommand extends Command
{
    public function __construct(
        private readonly VersionRepository $repository
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('version', InputArgument::REQUIRED, 'The version we want to activate');
    }

    /**
     * @throws EntityNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = $input->getArgument('version');

        $activeVersion = $this->repository->activateVersion($version);

        $output->writeln(sprintf(
            'Newly activates version: %d',
            $activeVersion->getVersionNumber()
        ));

        return self::SUCCESS;
    }
}
