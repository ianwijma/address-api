<?php

namespace App\Command;

use App\Repository\VersionRepository;
use App\Service\FileSystemService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Ulid;
use function Symfony\Component\String\u;

#[AsCommand(name: 'address:import:file', description: 'Imports addresses using a file as input')]
class AddressImportGeojsonCommand extends Command
{
    public const BATCH_SIZE = 100;

    public function __construct(
        private readonly VersionRepository $versionRepository,
        private readonly Connection $connection,
        private readonly FileSystemService $fileSystemService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('version-number', InputArgument::REQUIRED, 'The version we want to import under')
            ->addArgument('input-file', InputArgument::REQUIRED, 'The input file')
            ->addArgument('country', InputArgument::REQUIRED, 'The country the file belongs to');
    }

    /**
     * @throws EntityNotFoundException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $versionNumber = (int) $input->getArgument('version-number');
        $inputFile = $input->getArgument('input-file');
        $country = $input->getArgument('country');

        $version = $this->versionRepository->findByVersionNumberOrThrow($versionNumber);

        $filePath = $this->fileSystemService->getAbsoluteFilePath($inputFile);
        $this->fileSystemService->fileExistsOrThrow($filePath);
        $filePath = Path::makeAbsolute(Path::canonicalize($inputFile), getcwd());

        $contents = file($filePath);
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
ON CONFLICT DO NOTHING
RETURNING id;
');

            $addressStatement->executeStatement([
                (new Ulid())->toRfc4122(),
                $coordinateId,
                $this->format($addressProps->number),
                $this->format($addressProps->street),
                $this->format($addressProps->unit),
                $this->format($addressProps->district),
                $this->format($addressProps->region),
                $this->format($addressProps->postcode),
                $this->format($addressProps->hash),
                $addressProps->id,
                $country,
                $this->format($addressProps->city),
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

    private function format(string $input): string
    {
        return u($input)->lower()->title(true);
    }
}
