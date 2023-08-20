<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use ZipArchive;

class ExtractCommand
{
    public const INPUT_PATH = __DIR__ . '/../../input';

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'target this to the collection-global.zip file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');

        $filesystem = new Filesystem();
        if (!$filesystem->exists($file)) {
            throw new FileNotFoundException("File $file was not found.");
        }

        $output->writeln('Extracting archive, this can take a while...');
        $targetDir = $this->getTempDir();
        $archive = new ZipArchive();
        $archive->open($file, ZipArchive::RDONLY);
        $archive->extractTo($targetDir);

        $output->writeln("Extracting done, creating per-country geojson files.");
        $finder = new Finder();
        $finder
            ->files()
            ->in($targetDir)
            ->name('*addresses*.geojson');

        foreach ($finder as $file) {
            $path = Path::makeRelative($file->getRealPath(), $targetDir);
            [$country] = explode(DIRECTORY_SEPARATOR, $path);

            $countryPath = Path::canonicalize(self::INPUT_PATH . "/$country.geojson");
            file_put_contents(
                filename: $countryPath,
                data: $file->getContents(),
                flags: FILE_APPEND
            );
        }

        return Command::SUCCESS;
    }

    private function getTempDir(): string
    {
        $targetDir = tempnam(sys_get_temp_dir(), 'address_');
        if (file_exists($targetDir)) {
            unlink($targetDir);
        }
        mkdir($targetDir);

        return $targetDir;
    }
}
