<?php

namespace DeanHowe\Laravel\Moof\MultiDomain\Console;

use Config;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Process\Pool;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

trait DomainCommandTrait
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * The name of the configuration file of the package.
     */
    protected string $configFile = 'domain';

    /**
     * Has Moof Moof been installed?
     *
     * @param  null  $domain
     */
    protected function isInstalled(): string
    {
        return file_exists(base_path('config/domain.php'));
    }

    /**
     * Returns the path of the .env file for the specified domain.
     *
     * @param  null  $domain
     */
    protected function getDomainEnvFilePath($domain = null): string
    {
        if (is_null($domain)) {
            $domain = $this->domain;
        }

        return rtrim(env_path('.env.' . $domain), '.');
    }

    /**
     * Returns the path of the asset folder for the specified domain.
     *
     * @param  null  $domain
     */
    protected function getDomainAssetPath($domain = null): string
    {
        $path = app()->exactDomainAssetPath($domain);

        return $path;
    }

    /**
     * Returns the path of the markdown folder for the specified domain.
     *
     * @param  null  $domain
     */
    protected function getDomainMarkdownPath($domain = null): string
    {
        $path = app()->exactDomainMarkdownPath($domain);

        return $path;
    }

    /**
     * Returns the path of the markdown folder for the specified domain.
     *
     * @param  null  $domain
     */
    protected function getDomainLanguagePath($domain = null): string
    {
        $path = app()->exactDomainLanguagePath($domain);

        return $path;
    }

    /**
     * Returns the path of the storage folder for the specified domain.
     *
     * @param  null  $domain
     */
    protected function getDomainStoragePath($domain = null): string
    {
        $path = app()->exactDomainStoragePath($domain);

        return $path;
    }

    /**
     * Returns the path of the markdown theme for the specified domain.
     *
     * @param  null  $domain
     */
    protected function getDomainThemePath($domain = null): string
    {
        $path = app()->exactDomainThemePath($domain);

        return $path;
    }

    /**
     * Returns the contents of the stub of the package's configuration file.
     */
    protected function getConfigStub(): mixed
    {
        $filename = base_path('stubs/domain/config.stub');

        if (!$this->files->exists($filename)) {
            $filename = __DIR__ . '/../../../stubs/config.stub';
        }

        return $this->files->get($filename);
    }

    /**
     * This method updates the package's config file by adding or removing the domain handled by the caller command.
     * It calls either the addDomainToConfigFile or the removeDomainToConfigFile method of the caller.
     *
     * @param  string  $opType  (add|remove)
     */
    protected function updateConfigFile(string $opType = 'add'): void
    {
        $filename = base_path('config/' . $this->configFile . '.php');

        $config = include $filename;

        $configStub = $this->getConfigStub();

        $methodName = $opType . 'DomainToConfigFile';

        $finalConfig = call_user_func_array([$this, $methodName], [$config]);

        $modelConfigStub = str_replace(
            '{{$configArray}}',
            var_export($finalConfig, true),
            $configStub
        );

        $modelConfigStub = str_replace(
            'return array (',
            'return [',
            $modelConfigStub
        );
        $modelConfigStub = str_replace(
            ');',
            ' ];',
            $modelConfigStub
        );
        $modelConfigStub = str_replace(
            ["\narray (", "\n  array (", "\n    array (", "\n      array ("],
            '[',
            $modelConfigStub
        );
        $modelConfigStub = str_replace(
            ['),'],
            '],',
            $modelConfigStub
        );

        $this->files->put($filename, $modelConfigStub);
        Config::set($this->configFile, $finalConfig);
    }

    /**
     * This method gets the contents of a file formatted as a standard .env file
     * i.e. with each line in the form of KEY=VALUE
     * and returns the entries as an array.
     */
    protected function getVarsArray($path): array
    {
        $envFileContents = $this->files->get($path);
        $envFileContentsArray = explode("\n", $envFileContents);
        $varsArray = [];
        foreach ($envFileContentsArray as $line) {
            $lineArray = explode('=', $line);

            // Skip the line if there is no '='
            if (count($lineArray) < 2) {
                continue;
            }

            $value = substr($line, strlen($lineArray[0]) + 1);
            $varsArray[$lineArray[0]] = trim($value);

        }

        return $varsArray;
    }

    /**
     * This method prepares the values of an .env file to be stored.
     */
    protected function makeDomainEnvFileContents($domainValues): string
    {
        $contents = '';

        $previousKeyPrefix = '';

        foreach ($domainValues as $key => $value) {
            $keyPrefix = current(explode('_', $key));
            if ($keyPrefix !== $previousKeyPrefix && !empty($contents)) {
                $contents .= "\n";
            }
            $contents .= $key . '=' . $value . "\n";
            $previousKeyPrefix = $keyPrefix;
        }

        return $contents;
    }

    protected function downloadGithubRepo(string $repo): \Illuminate\Process\ProcessPoolResults
    {
        $repo = explode('/', $repo);

        $username = $repo[0];
        $repo = $repo[1] ?? false;

        $results = [];

        if (!$this->files->exists("./resources/sites/{$username}/{$username}")) {

            $gitHubPublicRepo = !$repo ? "git@github.com:{$username}/{$username}.git" : "git@github.com/{$username}/{$repo}";
            $gitHubIoRepo = !$repo ? "git@github.com:{$username}/{$username}.github.io.git" : "git@github.com/{$username}/{$repo}";
            $gitHubPrivateRepo = !$repo ? "git@github.com:{$username}/{$username}-private.git" : "git@github.com/{$username}/{$repo}";
            $gitHubMoofRepo = !$repo ? "git@github.com:{$username}/{$username}-moof.git" : "git@github.com/{$username}/{$repo}";

            $this->files->ensureDirectoryExists("./resources/sites/{$username}");

            $pool = Process::pool(function (Pool $pool) use (
                $username,
                $gitHubPublicRepo,
                $gitHubPrivateRepo,
                $gitHubIoRepo,
                $gitHubMoofRepo,
                &$privetRepoResults,
                &$publicRepoResults,
                &$gitHubMoofRepoResults,
                &$gitHubIORepoResults
            ) {
                $siteDirectory = "./resources/sites/{$username}";

                $publicRepoResults = $pool->as('public')
                    ->path($siteDirectory)
                    ->command("git clone {$gitHubPublicRepo} {$username}-public");

                $gitHubIORepoResults = $pool->as('github.io')
                    ->path($siteDirectory)
                    ->command("git clone {$gitHubIoRepo} {$username}-github");

                $privetRepoResults = $pool->as('private')
                    ->path($siteDirectory)
                    ->command("git clone {$gitHubPrivateRepo} {$username}-private");

                $gitHubMoofRepoResults = $pool->as('moof')
                    ->path($siteDirectory)
                    ->command("git clone {$gitHubMoofRepo} {$username}-local");

            })->start();

            $x = 0;

            while ($pool->running()->isNotEmpty()) {
                $x++;
                $this->line("Downloading.. ({$x}s)");
                sleep(1);
            }

            /** @var ProcessResult[] $results */
            $results = $pool->wait();

            if ($results['public']->failed()) {
                $this->error(trim($results['private']->errorOutput(), "\n"));
            }
            if ($results['github.io']->failed()) {
                $this->error(trim($results['private']->errorOutput(), "\n"));
            }
            if ($results['private']->failed()) {
                $this->error(trim($results['private']->errorOutput(), "\n"));
            }
            if ($results['moof']->failed()) {
                $this->error(trim($results['private']->errorOutput(), "\n"));
            }
        }

        return $results;
    }
}
