<?php

namespace App\Command;

use App\Repository\AddressRepository;
use App\Repository\VersionRepository;
use Doctrine\DBAL\Connection;
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
use Symfony\Component\Uid\Ulid;

#[AsCommand(name: 'address:import:file', description: 'Imports addresses using a file as input')]
class AddressImportGeojsonCommand extends Command
{
    public const BATCH_SIZE = 100;

    public function __construct(
        private readonly VersionRepository $versionRepository,
        private readonly AddressRepository $addressRepository,
        private readonly Connection $connection
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
     * @throws \Doctrine\DBAL\Exception
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

        $this->connection->getConfiguration()->setMiddlewares([]);
        $this->connection->beginTransaction();
        foreach ($contents as $index => $line) {
            $data = json_decode($line);
            $addressProps = $data->properties;
            [$east, $north] = $data->geometry->coordinates;

            $coordinateStatement = $this->connection->prepare('
INSERT INTO coordinate(id, north, east, version_id)
VALUES (?, ?, ?, ?)
ON CONFLICT DO NOTHING
RETURNING id;
');

            $coordinateResult = $coordinateStatement->executeQuery([
                (new Ulid())->toRfc4122(),
                $north,
                $east,
                $version->getId()->toRfc4122()
            ]);

            if ($coordinateResult->rowCount() > 0) {
                [$coordinateId] = $coordinateResult->fetchFirstColumn();
            } else {
                // Fallback
                $coordinateStatement = $this->connection->prepare('SELECT id FROM coordinate WHERE north=? AND east=? AND version_id=?');
                $coordinateResults = $coordinateStatement->executeQuery([
                    $north,
                    $east,
                    $version->getId()->toRfc4122()
                ]);

                [$coordinateId] = $coordinateResults->fetchFirstColumn();
            }

            $addressStatement = $this->connection->prepare('
INSERT INTO address(id, coordinate_id, number, street, unit, district, region, postcode, hash, external_id, country, city, version_id) 
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
RETURNING id;
');

            $addressStatement->executeStatement([
                (new Ulid())->toRfc4122(),
                $coordinateId,
                $addressProps->number,
                $addressProps->street,
                $addressProps->unit,
                $addressProps->district,
                $addressProps->region,
                $addressProps->postcode,
                $addressProps->hash,
                $addressProps->id,
                $country,
                $addressProps->city,
                $version->getId()->toRfc4122()
            ]);

            if ($index % self::BATCH_SIZE === 0) {
                $output->writeln("[$index/$total] Flushing...");
                $this->connection->commit();
                $this->connection->beginTransaction();
            }

            unset($data, $addressProps, $north, $east, $coordinateResult, $addressStatement);
        }

        // Clean up the remaining items
        $this->connection->commit();

        return self::SUCCESS;
    }
}
