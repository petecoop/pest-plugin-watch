<?php

declare(strict_types=1);

namespace Petecoop\PestWatch;

use Pest\Contracts\Plugins\HandlesOriginalArguments;
use Pest\Support\Str;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
final class Plugin implements HandlesOriginalArguments
{
    protected static $directories = ['app', 'src', 'tests'];

    private const WATCH_OPTION = 'watch';

    private string $command = 'vendor/bin/pest';

    public Process $pestProcess;

    /** @var array<int, string> */
    private array|string $watchedDirectories;

    public function __construct(
        private OutputInterface $output,
    ) {
        // remove non-existing directories from watched directories
        $this->watchedDirectories = array_filter(self::$directories, fn($directory) => is_dir($directory));
    }

    public static function directories(array $directories): void
    {
        self::$directories = $directories;
    }

    public function handleOriginalArguments(array $originals): void
    {
        if (!$this->userWantsToWatch($originals)) {
            return;
        }

        if (empty($this->watchedDirectories)) {
            $this->info('No directories to watch. Exiting.');

            exit(0);
        }

        $this->info('Starting Pest, and watching for file changes...');

        $processStarted = $this->startPest();

        // if the process failed to start, exit
        if (!$processStarted) {
            exit(1);
        }

        $this->listenForChanges();

        exit(0);
    }

    private function userWantsToWatch(array $originals): bool
    {
        $arguments = array_merge(
            [''],
            array_values(array_filter($originals, function ($original): bool {
                return (
                    $original === sprintf('--%s', self::WATCH_OPTION)
                    || Str::startsWith($original, sprintf('--%s=', self::WATCH_OPTION))
                );
            })),
        );

        $originals = array_flip($originals);
        foreach ($arguments as $argument) {
            unset($originals[$argument]);
        }

        $inputs = [];
        $inputs[] = new InputOption(self::WATCH_OPTION, null, InputOption::VALUE_OPTIONAL, '', true);

        $input = new ArgvInput($arguments, new InputDefinition($inputs));

        if (!$input->hasParameterOption(sprintf('--%s', self::WATCH_OPTION))) {
            return false;
        }

        // set the watched directories
        if ($input->getOption(self::WATCH_OPTION) !== null) {
            /* @phpstan-ignore-next-line */
            $this->watchedDirectories = explode(',', $input->getOption(self::WATCH_OPTION));
        }

        // set command to run
        $this->setCommand(implode(' ', array_flip($originals)));

        return true;
    }

    private function listenForChanges(): self
    {
        Watch::paths(...$this->watchedDirectories)->onAnyChange(function (string $event, string $path) {
            if ($this->changedPathShouldRestartPest($path)) {
                $this->restartPest();
            }
        })->start();

        return $this;
    }

    private function startPest(): bool
    {
        $this->pestProcess = Process::fromShellCommandline($this->getCommand());

        $this->pestProcess->setTty(true)->setTimeout(null);

        $this->pestProcess->start(fn($type, $output) => $this->output->write($output));

        return $this->pestProcess->isStarted();
    }

    private function restartPest(): self
    {
        $this->info('Change detected! Restarting Pest...');

        $this->pestProcess->stop(0);

        $this->startPest();

        return $this;
    }

    private function changedPathShouldRestartPest(string $path): bool
    {
        if ($this->isPhpFile($path)) {
            return true;
        }

        foreach ($this->watchedDirectories as $configuredPath) {
            if ($path === $configuredPath) {
                return true;
            }
        }

        return false;
    }

    private function isPhpFile(string $path): bool
    {
        return str_ends_with(strtolower($path), '.php');
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    private function info(string $message): void
    {
        $this->output->writeln("<info>info</info> {$message}");
    }
}
