<?php

declare(strict_types=1);

if (!is_file(__DIR__ . '/vendor/bin/phpstan')) {
    fwrite(STDERR, "Run composer install first.\n");
    exit(1);
}

require __DIR__ . '/generate_fixture.php';

$cache = __DIR__ . '/cache';
if (is_dir($cache)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cache, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($cache);
}

function analyse(string $label): void
{
    $command = [PHP_BINARY, '-d', 'memory_limit=2G', __DIR__ . '/vendor/bin/phpstan', 'analyse', '-c', 'phpstan.neon', '-vvv', '--no-progress'];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start PHPStan');
    }
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit !== 0) {
        fwrite(STDERR, $output);
        throw new RuntimeException("PHPStan failed on $label run (exit $exit)");
    }
    echo "$label run:\n";
    foreach (explode("\n", $output) as $line) {
        if (str_contains($line, 'Result cache') || str_contains($line, 'spawned processes:') || str_contains($line, 'Peak memory:')) {
            echo '  ' . trim($line) . "\n";
        }
    }
}

analyse('Cold');
for ($i = 0; $i < 120; $i++) {
    $name = sprintf('Class%03d', $i);
    file_put_contents(__DIR__ . "/generated/$name.php", "// changed $i\n", FILE_APPEND);
}
analyse('Incremental');
