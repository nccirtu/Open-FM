<?php

namespace App\Support\Plugins\Installer;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;

class ComposerRunner
{
    /**
     * @param  list<string>  $arguments
     * @param  array<string, string>  $environment
     */
    public function composer(array $arguments, array $environment = []): ProcessResult
    {
        return Process::path((string) config('plugins.composer.working_dir', base_path()))
            ->timeout((int) config('plugins.composer.timeout', 900))
            ->env([
                'COMPOSER_HOME' => $this->composerHome(),
                'COMPOSER_MEMORY_LIMIT' => '-1',
                'COMPOSER_NO_INTERACTION' => '1',
                ...$environment,
            ])
            ->run([...$this->composerCommand(), ...$arguments, '--no-interaction', '--no-ansi']);
    }

    /**
     * @param  list<string>  $arguments
     */
    public function artisan(array $arguments): ProcessResult
    {
        return Process::path(base_path())
            ->timeout(300)
            ->run([PHP_BINARY, 'artisan', ...$arguments, '--no-interaction', '--no-ansi']);
    }

    /**
     * @return list<string>
     */
    protected function composerCommand(): array
    {
        $configured = config('plugins.composer.binary');

        $binary = filled($configured) ? $configured : (new ExecutableFinder)->find('composer');

        if ($binary === null && File::exists(base_path('composer.phar'))) {
            $binary = base_path('composer.phar');
        }

        if ($binary === null) {
            throw new RuntimeException('Composer wurde nicht gefunden. Pfad über PLUGINS_COMPOSER_BINARY setzen.');
        }

        return str_ends_with($binary, '.phar') ? [PHP_BINARY, $binary] : [$binary];
    }

    protected function composerHome(): string
    {
        $home = getenv('COMPOSER_HOME') ?: storage_path('app/private/plugins/composer-home');

        File::ensureDirectoryExists($home);

        return $home;
    }
}
