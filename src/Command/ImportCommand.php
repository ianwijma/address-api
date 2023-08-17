<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\Coordinate;
use App\Repository\AddressRepository;
use App\Repository\CoordinateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AsCommand(name: 'address:import',description: 'Imports addresses and coordinates')]
class ImportCommand extends Command
{
    public const INPUT_PATH = __DIR__ . '/../../input';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('country', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $country = $input->getArgument('country');
        $path = Path::canonicalize(self::INPUT_PATH . "/$country.geojson");

        $filesystem = new Filesystem();
        if (!$filesystem->exists($path)) {
            throw new FileNotFoundException("File not found: $path");
        }

        // TODO: Not use a map
        $map = [];

        $contents = \file($path);

        $output->writeln("Counting addresses...");
        $total = count($contents);
        $output->writeln("$total addresses found");

        foreach ($contents as $index => $line) {
            $data = json_decode($line);
            $addressProps = $data->properties;
            [$east, $north] = $data->geometry->coordinates;

            if (!array_key_exists("$north::$east", $map)) {
                $coordinate = $map["$north::$east"] = (new Coordinate())
                    ->setNorth($north)
                    ->setEast($east);

                $this->entityManager->persist($coordinate);
            } else {
                $coordinate = $map["$north::$east"];
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
                ->setCoordinate($coordinate);


            $this->entityManager->persist($address);

            if ($index % 10000 === 0) {
                $output->writeln("[$index/$total] Flushing...");
                $this->entityManager->flush();
            }
        }

        return Command::SUCCESS;
    }
}
