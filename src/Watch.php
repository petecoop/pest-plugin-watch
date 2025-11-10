<?php

declare(strict_types=1);

namespace Petecoop\PestWatch;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Watch extends \Spatie\Watcher\Watch
{
    public static function path(string $path): self
    {
        return (new self())->setPaths($path);
    }

    public static function paths(...$paths): self
    {
        return (new self())->setPaths($paths);
    }

    protected function getWatchProcess(): Process
    {
        $command = [
            (new ExecutableFinder())->find($this->isBunProject() ? 'bun' : 'node'),
            realpath(__DIR__ . '/../bin/file-watcher.js'),
            json_encode($this->paths),
        ];

        $process = new Process(
            command: $command,
            timeout: null,
        );

        $process->start();

        return $process;
    }

    protected function isBunProject(): bool
    {
        return file_exists(getcwd() . '/bun.lock') || file_exists(getcwd() . '/bun.lockb');
    }
}
