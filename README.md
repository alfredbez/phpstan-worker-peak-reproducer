# PHPStan omits worker memory on incremental runs

A standalone reproducer for the `-vvv` memory report after partial result-cache restore. Both analyses spawn workers. The cold run prints the largest worker's memory peak; the incremental run omits it.

## Reproduce

Requirements: PHP 8.2 or newer, Composer and a platform where PHPStan can start parallel workers.

```sh
composer install
php run.php
```

The script generates 200 independent PHP files, one with 12000 methods, clears its local cache, analyses them, then changes 120 files and analyses again. It prints the result-cache decision, number of spawned processes and peak memory line for each run.

On macOS with PHP 8.5.10 and PHPStan `2.3.x-dev@9b5c7d6`, the cold run printed **68 MB for the main process and 216.02 MB for the largest of four workers**. The incremental run said four processes were spawned but printed only **150 MB**, the main-process peak. These are PHP allocator peaks from `memory_get_peak_usage(true)`, not RSS (the resident set size that tools like `top` show). RSS also counts the PHP binary, loaded extensions and memory that PHP has not given back to the OS, so it is usually higher.

On GitHub Actions, PHPStan spawned two workers: the cold run printed **214 MB** for the largest worker, while the incremental run printed only the **152 MB** main-process peak. The missing worker value reproduces on Linux too.

The partial-cache path in `ResultCacheManager` reconstructs `AnalyserResult` without the worker count, so `InceptionResult` takes the main-process-only reporting path. GitHub Actions runs the two analyses on every push.
