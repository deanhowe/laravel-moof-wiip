<?php namespace DeanHowe\Laravel\Moof\MultiDomain\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;


class AddDomainCommand extends GeneratorCommand
{

    use DomainCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x-moof:domain-add
                            {domain : The name of the domain to add to the framework}
                            {--domain_values= : The optional values for the domain variables to be stored in the env file (json object)}
                            {--force : Force the creation of domain storage dirs also if they already exist}
                            {--dev : Setting up to use a domain for local development }';

    protected $description = "Adds a domain to the framework by creating the specific .env file and storage dirs.";
    protected $domain;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        if(! $this->isInstalled()) {

            $this->line("<info>Please install</info> <comment>Moof WiiP</comment> <info>`php artisan x-moof:install`</info>");
            return;
        }
        $this->domain = $this->argument('domain');

//        /*
//         * CREATE ENV FILE FOR DOMAIN
//         */
//        $this->createDomainEnvFile();
//
//        /*
//         * Create domain markdown directories
//         */
//        $this->createDomainMarkdownDirs();
//
//        /*
//        * Create domain markdown directories
//        */
//        $this->createAssetsDirs();
//
//        (new Filesystem)->ensureDirectoryExists(base_path('public/images/'. domain_sanitized($this->domain)));
//
//        /*
//         * Create domain lang directories
//         */
//        $this->createDomainLanguageDirs();
//
//        /*
//         * Create domain storage directories
//         */
//        $this->createDomainStorageDirs();
//
//        /*
//         * Create domain theme directories
//         */
//        $this->createDomainThemeDirs();
//
//        /*
//         * Update config file
//         */
//        $this->updateConfigFile();
//
//        /**
//         * Configuring .gitignore file according local requirements
//         */
//        if ($this->option('dev')) {
//            $this->setupGitIgnore();
//        }
//
//        $this->line("<info>Added</info> <comment>" . $this->domain . "</comment> <info>to the application.</info>" . ($this->option('dev') ? ' (In dev mode)' : ''));
    }


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->files->exists($this->getDomainEnvFilePath())) {
            return $this->getDomainEnvFilePath();
        }
        return env_path(Config::get('domain.env_stub', '.env'));
    }

    protected function createDomainEnvFile()
    {
        $envFilePath = $this->getStub();

        $domainValues = json_decode($this->option("domain_values"), true);


        if (!is_array($domainValues)) {
            $domainValues = array();
        }


        $envArray = $this->getVarsArray($envFilePath);

        $domainEnvFilePath = $this->getDomainEnvFilePath();

        $domainEnvArray = array_merge($envArray, $domainValues);
        $domainEnvFileContents = $this->makeDomainEnvFileContents($domainEnvArray);

        $this->files->put($domainEnvFilePath, $domainEnvFileContents);
    }

    public function createDomainStorageDirs(): void
    {
        $storageDirs = Config::get('domain.storage_dirs', array());
        $path = $this->getDomainStoragePath($this->domain);
        $rootPath = storage_path();
        if ($this->files->exists($path) && !$this->option('force')) {
            return;
        }

        if ($this->files->exists($path)) {
            $this->files->deleteDirectory($path);
        }


        $this->createRecursiveDomainDirs($rootPath, $path, $storageDirs);

    }

    public function createDomainLanguageDirs(): void
    {
        $storageDirs = Config::get('domain.lang_dirs', array());
        $path = $this->getDomainLanguagePath($this->domain);
        $rootPath = lang_path();
        if ($this->files->exists($path) && !$this->option('force')) {
            return;
        }

        if ($this->files->exists($path)) {
            $this->files->deleteDirectory($path);
        }


        $this->createRecursiveDomainDirs($rootPath, $path, $storageDirs);

    }

    public function createAssetsDirs(): void
    {
        $storageDirs = Config::get('domain.asset_dirs', array());
        $path = $this->getDomainAssetPath($this->domain);
        $rootPath = resource_path('assets/' . MOOF_DOMAINS_FOLDER);
        if ($this->files->exists($path) && !$this->option('force')) {
            return;
        }

        if ($this->files->exists($path)) {
            $this->files->deleteDirectory($path);
        }

        $this->createRecursiveDomainDirs($rootPath, $path, $storageDirs);

    }

    public function createDomainMarkdownDirs(): void
    {
        $storageDirs = Config::get('domain.markdown_dirs', array());
        $path = $this->getDomainMarkdownPath($this->domain);
        $rootPath = resource_path('markdown/' . MOOF_DOMAINS_FOLDER);
        if ($this->files->exists($path) && !$this->option('force')) {
            return;
        }

        if ($this->files->exists($path)) {
            $this->files->deleteDirectory($path);
        }


        $this->createRecursiveDomainDirs($rootPath, $path, $storageDirs);

    }

    public function createDomainThemeDirs(): void
    {
        $storageDirs = Config::get('domain.theme_dirs', array());
        $path = $this->getDomainThemePath($this->domain);
        $rootPath = resource_path('views/x_moof_themes');
        if ($this->files->exists($path) && !$this->option('force')) {
            return;
        }

        if ($this->files->exists($path)) {
            $this->files->deleteDirectory($path);
        }


        $this->createRecursiveDomainDirs($rootPath, $path, $storageDirs);

    }

    protected function createRecursiveDomainDirs($rootPath, $path, $dirs): void
    {
        $this->files->makeDirectory($path, 0755, true);
        foreach (['.gitignore', '.gitkeep'] as $gitFile) {
            $rootGitPath = $rootPath . DIRECTORY_SEPARATOR . $gitFile;
            if ($this->files->exists($rootGitPath)) {
                $gitPath = $path . DIRECTORY_SEPARATOR . $gitFile;
                $this->files->copy($rootGitPath, $gitPath);
            }
        }
        foreach ($dirs as $subdir => $subsubdirs) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $subdir;
            $fullRootPath = $rootPath . DIRECTORY_SEPARATOR . $subdir;
            $this->createRecursiveDomainDirs($fullRootPath, $fullPath, $subsubdirs);

        }

    }

    protected function addDomainToConfigFile($config)
    {
        $domains = Arr::get($config, 'domains', []);
        if (!array_key_exists($this->domain, $domains)) {
            $domains[$this->domain] = $this->domain;
        }

        ksort($domains);
        $config['domains'] = $domains;

        return $config;
    }

    protected function setupGitIgnore()
    {
        $toplevelDomain = array_reverse(explode('.', $this->domain))[0];

        $finds = [
            'domains' => Config::get('domain.env_stub', '.env') . ".*.{$toplevelDomain}",
            'storage' => DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "*_{$toplevelDomain}",
        ];

        foreach (file(base_path('.gitignore'), FILE_SKIP_EMPTY_LINES) as $line) {
            foreach ($finds as $key => $value) {
                if (strpos($line, $value) === 0) {
                    $finds[$key] = null;
                }
            }
        }

        $contents = implode("\n", $finds);

        if (empty(trim($contents))) {
            return;
        }

        $fh = fopen(base_path('.gitignore'), 'a');

        fwrite($fh, "\n# Files and directories for local development\n");
        fwrite($fh, $contents);

        fclose($fh);
    }
}
