<?php namespace DeanHowe\Laravel\Moof\MultiDomain\Console;

use Config;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Arr;

class RemoveDomainCommand extends GeneratorCommand {

    use DomainCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x-moof:domain-remove 
                            {domain : The name of the domain to remove from the framework} 
                            {--force : Force the deletion of domain storage dirs also if they exist and they are possibly full}';

    protected $description = "Removes a domain from the framework.";
    protected string $domain;

    /*
     * Se il file di ambiente esiste già viene semplicemente sovrascirtto con i nuovi valori passati dal comando (update)
     */
    public function handle() {

        if(! $this->isInstalled()) {

            $this->line("<info>Please install</info> <comment>Moof Moof</comment> <info>`php artisan x-moof:install`</info>");
            return;
        }

        $this->domain = $this->argument('domain');

        /*
         * CREATE ENV FILE FOR DOMAIN
         */
        $this->deleteDomainEnvFile();

        /*
         * Setting domain storage directories
         */

        if ($this->option('force')) {
            $this->deleteDomainLanguageDirs();
            $this->deleteDomainMarkdownDirs();
            $this->deleteDomainStorageDirs();
            $this->deleteDomainThemeDirs();
        }

        /*
         * Update config file
         */

        $this->updateConfigFile('remove');

        $this->line("<info>Removed</info> <comment>" . $this->domain . "</comment> <info>from the application.</info>");
    }

    protected function deleteDomainEnvFile()
    {
        $domainEnvFilePath = $this->getDomainEnvFilePath();
        if ($this->files->exists($domainEnvFilePath)) {
            $this->files->delete($domainEnvFilePath);
        }
    }

    public function deleteDomainLanguageDirs() {
        $path = $this->getDomainLanguagePath($this->domain);
        if ($this->files->exists($path)) {
            $this->files->deleteDirectory($path);
        }
    }

    public function deleteDomainMarkdownDirs() {
        $path = $this->getDomainMarkdownPath($this->domain);
        if ($this->files->exists($path)) {
            $this->files->deleteDirectory($path);
        }
    }

    public function deleteDomainStorageDirs() {
        $path = $this->getDomainStoragePath($this->domain);
        if ($this->files->exists($path)) {
            $this->files->deleteDirectory($path);
        }
    }

    public function deleteDomainThemeDirs() {
        $path = $this->getDomainThemePath($this->domain);
        if ($this->files->exists($path)) {
            $this->files->deleteDirectory($path);
        }
    }

    protected function removeDomainToConfigFile($config) {
        $domains = Arr::get($config, 'domains', []);
        if (array_key_exists($this->domain, $domains)) {
            unset($domains[$this->domain]);
        }
        $config['domains'] = $domains;
        return $config;
    }

    protected function getStub()
    {
        // TODO: Implement getStub() method.
    }
}
