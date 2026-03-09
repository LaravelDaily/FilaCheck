<?php

declare(strict_types=1);

namespace Filacheck\Support;

class GitDirtyFiles
{
    /**
     * Get files with uncommitted changes (staged, unstaged, and untracked).
     *
     * @return string[]|null Absolute file paths, or null if git is unavailable / not a repo.
     */
    public static function get(string $workingDir): ?array
    {
        $gitRoot = self::exec('git rev-parse --show-toplevel', $workingDir);

        if ($gitRoot === null) {
            return null;
        }

        $gitRoot = trim($gitRoot);

        $output = self::exec('git status --porcelain', $workingDir);

        if ($output === null) {
            return null;
        }

        $lines = preg_split('/\R+/', $output, flags: PREG_SPLIT_NO_EMPTY);

        if (empty($lines)) {
            return [];
        }

        $files = [];

        foreach ($lines as $line) {
            if (strlen($line) < 4) {
                continue;
            }

            $status = trim(substr($line, 0, 2));

            // Skip deleted files
            if ($status === 'D') {
                continue;
            }

            $filePath = trim(substr($line, 3));

            // Handle renamed files (take the new name after " -> ")
            if (str_contains($filePath, ' -> ')) {
                $filePath = trim(explode(' -> ', $filePath, 2)[1]);
            }

            $absolutePath = realpath($gitRoot.DIRECTORY_SEPARATOR.$filePath);

            if ($absolutePath !== false) {
                $files[] = $absolutePath;
            }
        }

        return $files;
    }

    private static function exec(string $command, string $workingDir): ?string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $workingDir);

        if (! is_resource($process)) {
            return null;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return $exitCode === 0 ? $stdout : null;
    }
}
