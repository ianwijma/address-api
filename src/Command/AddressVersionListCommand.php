<?php

namespace App\Command;

use App\Repository\AddressRepository;
use App\Repository\CoordinateRepository;
use App\Repository\VersionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'address:version:list', description: 'List the versions')]
class AddressVersionListCommand extends Command
{
    public function __construct(
        private readonly VersionRepository $versionRepository,
        private readonly AddressRepository $addressRepository,
        private readonly CoordinateRepository $coordinateRepository,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Version', 'Active', 'Address amount', 'Coordinate amount']);

        $versions = $this->versionRepository->findAll();
        foreach ($versions as $version) {
            $table->addRow([
                $version->getVersionNumber(),
                $version->isActive() ? 'true' : 'false',
                $this->addressRepository->count(['version' => $version]),
                $this->coordinateRepository->count(['version' => $version]),
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }
}
