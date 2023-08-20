<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\Coordinate;
use App\Repository\AddressRepository;
use App\Repository\CoordinateRepository;
use App\Repository\VersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Path;

#[AsCommand(name: 'address:import:file', description: 'Imports addresses using a file as input')]
class AddressImportGeojsonCommand extends Command
{
    public const BATCH_SIZE = 100;

    public function __construct(
        private readonly VersionRepository $versionRepository,
        private readonly AddressRepository $addressRepository,
        private readonly CoordinateRepository $coordinateRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('version-number', InputArgument::REQUIRED, 'The version we want to import under')
            ->addArgument('input-file', InputArgument::REQUIRED, 'The input file')
            ->addOption('country', 'c', InputOption::VALUE_OPTIONAL, 'Force a country instead of taking it from the \'input-file\'', null);
    }

    /**
     * @throws EntityNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $versionNumber = (int) $input->getArgument('version-number');
        $inputFile = $input->getArgument('input-file');
        $country = $input->getOption('country');
        if (null === $country) {
            $country = Path::getFilenameWithoutExtension($inputFile);
        }

        $version = $this->versionRepository->findByVersionNumberOrThrow($versionNumber);
        $inputFilePath = Path::makeAbsolute(Path::canonicalize($inputFile), getcwd());
        if (!file_exists($inputFilePath)) {
            throw new FileNotFoundException(sprintf(
                '\'input-file\' %s was not found: %s',
                $inputFile,
                $inputFilePath
            ));
        }

        $address = $this->addressRepository->findOneBy(['country' => $country, 'version' => $version]);
        if ($address) {
            throw new Exception(sprintf(
                'Addresses for this country \'%s\' & version \'%s\' are already imported',
                $country,
                $version->getVersionNumber()
            ));
        }

        $contents = file($inputFilePath);
        $total = count($contents);
        $output->writeln("$total addresses to be imported");

        $this->entityManager->getConfiguration()->setMiddlewares([]);

        $coordinateIdMap = [];
        foreach ($contents as $index => $line) {
            $data = json_decode($line);
            $addressProps = $data->properties;
            [$east, $north] = $data->geometry->coordinates;

            $coordinateIdMapKey = "$north::$east";
            if (!array_key_exists($coordinateIdMapKey, $coordinateIdMap)) {
                $coordinate = (new Coordinate())
                    ->setNorth($north)
                    ->setEast($east)
                    ->setVersion($version);

                $this->entityManager->persist($coordinate);

                $coordinateIdMap[$coordinateIdMapKey] = $coordinate->getId();
            } else {
                $coordinateId = $coordinateIdMap[$coordinateIdMapKey];
                $coordinate = $this->coordinateRepository->find($coordinateId);
            }

            $address = (new Address())
                ->setCountry($country)
                ->setCity($addressProps->city)
                ->setRegion($addressProps->region)
                ->setDistrict($addressProps->district)
                ->setPostcode($addressProps->postcode)
                ->setStreet($addressProps->street)
                ->setNumber($addressProps->number)
                ->setUnit($addressProps->unit)
                ->setHash($addressProps->hash)
                ->setExternalId($addressProps->id)
                ->setVersion($version)
                ->setCoordinate($coordinate);


            $this->entityManager->persist($address);

            if ($index % self::BATCH_SIZE === 0) {
                $output->writeln("[$index/$total] Flushing...");
                $this->entityManager->flush();
                $this->entityManager->clear();
                $version = $this->versionRepository->findByVersionNumberOrThrow($versionNumber);
            }
        }

        // Clean up the remaining items
        $this->entityManager->flush();
        $this->entityManager->clear();

        return self::SUCCESS;
    }
}
