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
use ZipArchive;

#[AsCommand(name: 'address:prepare:global-collection', description: 'Prepare the OpenAddresses.io, global-collection.zip into importable files')]
class AddressPrepareGlobalCommand extends Command
{
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
            ->addArgument('country', InputArgument::REQUIRED, 'The country you want to prepare.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputFile = $input->getArgument('input-file');
        $directory = $input->getArgument('output-directory');
        $country = $input->getArgument('country');
        $outputFile = sprintf("%s.geojson", $country);

        $inputFilePath = Path::makeAbsolute(Path::canonicalize($inputFile), getcwd());
        $directoryPath = Path::makeAbsolute(Path::canonicalize($directory), getcwd());
        $outputFilePath = $directoryPath . '/' . $outputFile;

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

        if (file_exists($outputFilePath)) {
            throw new FileNotFoundException(sprintf(
                'The output file %s for this country already exists: %s',
                $outputFile,
                $outputFilePath
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

        foreach ($finder as $inputFile) {
            file_put_contents(
                filename: $outputFilePath,
                data: $inputFile->getContents(),
                flags: FILE_APPEND
            );
        }

        $output->writeln('Removing temporary files');
        $this->fileSystemService->removeDirectoryRecursively($temporaryDirectoryPath);

        return self::SUCCESS;
    }
}
