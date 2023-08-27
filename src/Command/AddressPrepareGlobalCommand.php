<?php

namespace App\Command;

use App\Service\FileSystemService;
use Doctrine\Migrations\Tools\Console\Exception\FileTypeNotSupported;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Slugger\AsciiSlugger;
use ZipArchive;
use function Symfony\Component\String\u;

#[AsCommand(name: 'address:prepare:global-collection', description: 'Prepare the OpenAddresses.io, global-collection.zip into importable files')]
class AddressPrepareGlobalCommand extends Command
{
    const AVAILABLE_SPLIT_TARGETS = [
        'country',
        'region',
        'city',
        'postcode',
        'district',
        'street',
        'number',   // Questionable use case, but added for completeness
        'unit',     // Questionable use case, but added for completeness
    ];

    public function __construct(
        private readonly FileSystemService $fileSystemService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('input-file', InputArgument::REQUIRED, 'The global-collection.zip input file from OpenAddresses.io')
            ->addArgument('output-directory', InputArgument::REQUIRED, 'The output directory where we put the combines files into a importable format.')
            ->addArgument('country', InputArgument::REQUIRED, 'The country you want to prepare.')
            ->addArgument(
                'split-target',
                InputArgument::OPTIONAL,
                sprintf(
                    'What we want to split it by, chose one of: %s',
                    implode(', ', self::AVAILABLE_SPLIT_TARGETS)
                )
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $inputFile = $input->getArgument('input-file');
        $splitTarget = $input->getArgument('split-target');
        $country = $input->getArgument('country');

        $filePath = $this->fileSystemService->getAbsoluteFilePath($inputFile);
        $this->fileSystemService->fileExistsOrThrow($filePath);

        if ($splitTarget && !in_array($splitTarget, self::AVAILABLE_SPLIT_TARGETS)) {
            throw new \Exception(sprintf(
                '\'split-target\' is not one of: %s',
                implode(', ', self::AVAILABLE_SPLIT_TARGETS)
            ));
        }

        if (strlen($country) !== 2) {
            throw new \Exception('\'country\' needs to be of a iso2 format');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputFile = $input->getArgument('input-file');
        $directory = $input->getArgument('output-directory');
        $splitTarget = $input->getArgument('split-target');
        $country = u($input->getArgument('country'))->lower();

        $inputFilePath = Path::makeAbsolute(Path::canonicalize($inputFile), getcwd());
        $directoryPath = Path::makeAbsolute(Path::canonicalize($directory), getcwd());

        $prefix = sprintf('address_prepare_%s_', $country);
        $temporaryDirectoryPath = $this->fileSystemService->getTemporaryDirectory($prefix);

        if (!file_exists($inputFilePath)) {
            throw new FileNotFoundException(sprintf(
                '\'input-file\' %s was not found: %s',
                $inputFile,
                $inputFilePath
            ));
        }

        if ('zip' !== Path::getExtension($inputFilePath, forceLowerCase: true)) {
            throw new FileTypeNotSupported(sprintf(
                '\'input-file\' %s was not a .zip file',
                $inputFile
            ));
        }

        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, recursive: true);
        }

        $output->writeln('Getting country files from zip archive, this might take a while');
        $archive = new ZipArchive();
        $archive->open($inputFilePath, ZipArchive::RDONLY);

        $archiveFileNamesToExtract = [];
        for ($index = 0; $index < $archive->count(); $index++) {
            $archiveFileName = $archive->getNameIndex($index);
            if (str_starts_with($archiveFileName, $country)) {
                $archiveFileNamesToExtract[] = $archiveFileName;
            }
        }

        $archive->extractTo($temporaryDirectoryPath, $archiveFileNamesToExtract);

        $output->writeln('Combining multiple geojson files into a single file');
        $finder = (new Finder())
            ->files()
            ->in($temporaryDirectoryPath)
            ->name('*addresses*.geojson');

        $slugger = new AsciiSlugger($country);

        foreach ($finder as $inputFile) {
            if ($splitTarget) {
                $content = explode("\n", $inputFile->getContents());
                foreach ($content as $line) {
                    $data = json_decode($line);
                    if ($data) {
                        $addressProps = $data->properties;
                        $splitValue = $slugger->slug(u($addressProps->$splitTarget)->lower());

                        $outputFile = sprintf("%s-%s.geojson", $country, $splitValue);
                        $outputFilePath = $directoryPath . '/' . $outputFile;

                        file_put_contents(
                            filename: $outputFilePath,
                            data: $line . PHP_EOL,
                            flags: FILE_APPEND
                        );
                    }
                }
            } else {
                $outputFile = sprintf("%s.geojson", $country);
                $outputFilePath = $directoryPath . '/' . $outputFile;
                file_put_contents(
                    filename: $outputFilePath,
                    data: $inputFile->getContents(),
                    flags: FILE_APPEND
                );
            }
        }

        $output->writeln('Removing temporary files');
        $this->fileSystemService->removeDirectoryRecursively($temporaryDirectoryPath);

        return self::SUCCESS;
    }
}
