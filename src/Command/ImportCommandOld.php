<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\Coordinate;
use App\Exceptions\VersionMissMatchException;
use App\Service\AddressVersionService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ImportCommandOld
{
    public const INPUT_PATH = __DIR__ . '/../../input';
    public const BATCH_SIZE = 100;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressVersionService $addressVersionService,
    ) {
    }

    /**
     * @return int
     * @throws Exception
     * @throws VersionMissMatchException
     */
    public function getNextVersion(): int
    {
        $currentVersion = $this->addressVersionService->getVersion();

        return $currentVersion + 1;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('country', InputArgument::REQUIRED);
    }

    /**
     * @throws VersionMissMatchException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $country = $input->getArgument('country');

        $version = $this->getNextVersion();
        $validatedFilePath = $this->getValidatedFilePath(country: $country);
        $this->importFile(version: $version, country: $country, filePath: $validatedFilePath, output: $output);

        return Command::SUCCESS;
    }

    private function getValidatedFilePath(string $country): string
    {
        $path = Path::canonicalize(self::INPUT_PATH . "/$country.geojson");

        $filesystem = new Filesystem();
        if (!$filesystem->exists($path)) {
            throw new FileNotFoundException("File not found: $path");
        }

        return $path;
    }

    private function importFile(int $version, string $country, string $filePath, OutputInterface $output): void
    {
        $contents = \file($filePath);
        $total = count($contents);
        $output->writeln("$total addresses to be imported");

        $this->entityManager->getConfiguration()->setMiddlewares([]);

        $coordinateRepository = $this->entityManager->getRepository(Coordinate::class);

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
                $coordinate = $coordinateRepository->find($coordinateId);
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
            }
        }

        // Clean up the remaining items
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
