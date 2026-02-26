<?php

declare(strict_types=1);

namespace Frumle;

/**
 * Recursively scans a directory for PHP project files.
 */
class Scanner
{
    private const DEFAULT_IGNORE_DIRS = [
        'vendor',
        'node_modules',
        '.git',
        'storage',
        'cache',
        'var',
        'tmp',
        'temp',
        'logs',
        'log',
        'dist',
        'build',
        '.idea',
        '.vscode',
        'nbproject',
        'runtime',
        'assets',
    ];

    private const DEFAULT_IGNORE_FILES = [
        'frumle.config.json',
        'devdoc.config.json',
    ];

    private const DEFAULT_EXTENSIONS = [
        'php',
        'json',
        'yaml',
        'yml',
        'xml',
        'md',
        'env',
        'neon',
        'ini',
        'twig',
    ];

    /**
     * Scan directory and return list of file data arrays.
     *
     * @param string      $dirPath        Root directory to scan
     * @param string[]    $ignoreDirs     Directories to skip
     * @param string[]    $fileExtensions File extensions to include (without dots)
     * @return array<int, array{path: string, relativePath: string, content: string}>
     */
    public static function readCodebase(
        string $dirPath,
        array $ignoreDirs = [],
        array $fileExtensions = []
    ): array {
        $base = realpath($dirPath);
        if ($base === false || !is_dir($base)) {
            return [];
        }

        $ignore = empty($ignoreDirs) ? self::DEFAULT_IGNORE_DIRS : $ignoreDirs;
        $exts = empty($fileExtensions) ? self::DEFAULT_EXTENSIONS : $fileExtensions;
        $exts = array_map(fn(string $e) => ltrim(strtolower($e), '.'), $exts);

        $results = [];
        self::walk($base, $base, $ignore, $exts, $results);
        return $results;
    }

    private static function walk(
        string $current,
        string $base,
        array $ignoreDirs,
        array $exts,
        array &$results
    ): void {
        $handle = @opendir($current);
        if ($handle === false) {
            return;
        }

        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            // Skip hidden dirs/files (except .env*)
            if ($entry[0] === '.' && !str_starts_with($entry, '.env')) {
                continue;
            }

            $fullPath = $current . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($fullPath)) {
                if (!in_array($entry, $ignoreDirs, true)) {
                    self::walk($fullPath, $base, $ignoreDirs, $exts, $results);
                }
                continue;
            }

            if (in_array($entry, self::DEFAULT_IGNORE_FILES, true)) {
                continue;
            }

            // Handle compound extensions like .blade.php
            $ext = self::getExtension($entry);
            if (!in_array($ext, $exts, true)) {
                // Also check if it's a blade template
                if (str_ends_with(strtolower($entry), '.blade.php') && in_array('php', $exts, true)) {
                    // allow through
                } else {
                    continue;
                }
            }

            $content = @file_get_contents($fullPath);
            if ($content === false) {
                continue;
            }

            $relativePath = ltrim(str_replace($base, '', $fullPath), DIRECTORY_SEPARATOR);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            $results[] = [
                'path'         => $fullPath,
                'relativePath' => $relativePath,
                'content'      => $content,
            ];
        }

        closedir($handle);
    }

    private static function getExtension(string $filename): string
    {
        $pos = strrpos($filename, '.');
        if ($pos === false) {
            return '';
        }
        return strtolower(substr($filename, $pos + 1));
    }
}
