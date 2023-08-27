<?php

namespace App\Command;

use App\Service\FileSystemService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'address:prepare:split', description: 'Allows splitting to have smaller files for more parallel processing')]
class AddressSplitCommand extends Command
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
        private readonly FileSystemService $fileSystemService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('input-file', InputArgument::REQUIRED, 'The file your want to split')
            ->addArgument(
                'split-target',
                InputArgument::REQUIRED,
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

        $filePath = $this->fileSystemService->getAbsoluteFilePath($inputFile);
        $this->fileSystemService->fileExistsOrThrow($filePath);

        if (!in_array($splitTarget, self::AVAILABLE_SPLIT_TARGETS)) {
            throw new \Exception(sprintf(
                '\'split-target\' is not one of: %s',
                implode(', ', self::AVAILABLE_SPLIT_TARGETS)
            ));
        }
    }
}
