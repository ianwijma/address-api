<?php

namespace App\Service;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileSystemService
{
    public function getTemporaryDirectory(string $prefix): string
    {
        $targetDir = tempnam(sys_get_temp_dir(), $prefix);
        if (file_exists($targetDir)) {
            unlink($targetDir);
        }
        mkdir($targetDir);

        return $targetDir;
    }

    public function removeDirectoryRecursively(string $directoryPath): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $removeFunction = $fileinfo->isDir() ? 'rmdir' : 'unlink';
            $removeFunction($fileinfo->getRealPath());
        }

        rmdir($directoryPath);
    }
}
