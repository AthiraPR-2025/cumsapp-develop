<?php
namespace Cums;

class Common
{
    /**
     * Pull the latest changes from the Git repo.
     */
    public static function gitPull(string $repoPath): void
    {
        if (!is_dir($repoPath)) {
            \Log::error("Repo path not found: $repoPath");
            return;
        }

        $output = shell_exec("cd " . escapeshellarg($repoPath) . " && git pull 2>&1");
        \Log::info("Git pull executed at $repoPath: $output");
    }

    /**
     * Find index.html files up to a given max depth.
     */
    public static function findLocalIndexHtmlFiles(string $baseDir, int $maxDepth = 3): array
    {
        $files = [];

        $baseDir = realpath($baseDir);
        if (!$baseDir || !is_dir($baseDir)) {
            \Log::warning("Invalid base directory: {$baseDir}");
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            if ($file->getFilename() !== 'index.html') continue;

            $depth = substr_count(str_replace($baseDir, '', $file->getPath()), DIRECTORY_SEPARATOR);
            if ($depth <= $maxDepth) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    // Optional: comment out SSH function
    /*
    public static function ssh2_exec($session, string $command)
    {
        $stream = ssh2_exec($session, $command);
        stream_set_blocking($stream, true);
        $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $result = stream_get_contents($stream_out);
        fclose($stream_out);
        fclose($stream);
        return $result;
    }
    */
}
