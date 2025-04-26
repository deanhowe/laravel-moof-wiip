<?php

namespace DeanHowe\Laravel\Moof\MultiDomain\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ListDomainCommand extends Command
{
    use DomainCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x-moof:domain-list
                            {--output=txt : the output type json or txt (txt as default)}';

    protected $description = 'Lists the domains the application is aware of.';

    protected $domain;

    /*
     * Se il file di ambiente esiste già viene semplicemente sovrascirtto con i nuovi valori passati dal comando (update)
     */
    public function handle()
    {

        if (!$this->isInstalled()) {

            $this->line('<info>Please install</info> <comment>Moof🐮Moof</comment> <info>`php artisan x-moof:install`</info>');

            return;
        }

        $this->newLine();
        $this->line('<info>You have the following </info> <comment>Moof🐮Moof</comment> <info> domains installed :</info>');
        $this->newLine();
        /*
         * GET CONFIG FILE
         */
        $filename = base_path('config/' . $this->configFile . '.php');
        // ray('Loaded and looking for : ', $filename)->green();

        $config = include $filename;

        /*
         * GET DOMAINS BASED ON domains KEY IN THE CONFIG FILE
         */
        $domains = Arr::get($config, 'domains', []);

        /*
         * Simply returns the info for each domain found in config.
         */
        $outputType = $this->option('output');

        $domains = $this->buildResult($domains);

        if ($domains->count() > 0) {
            switch (strtolower(trim($outputType ?? 'txt'))) {
                default:
                case 'txt':
                    $this->outputAsText($domains);
                    break;
                case 'table':
                    $this->outputAsTable($domains);
                    break;
                case 'json':
                    $this->outputAsJson($domains);
                    break;
            }
        } else {
            $this->info('No domains found');
        }
    }

    protected function outputAsText(Collection $domains)
    {
        foreach ($domains as $domain) {
            $this->line('<info>Domain: </info><comment>' . Arr::get($domain, 'domain') . '</comment>');

            $this->line('<info> - Storage dir: </info><comment>' . Arr::get($domain, 'storage_dir') . '</comment>');
            $this->line('<info> - Env file: </info><comment>' . Arr::get($domain, 'env_file') . '</comment>');

            $this->line('');

        }
    }

    protected function outputAsJson(Collection $domains)
    {
        $this->output->writeln(json_encode($domains));
    }

    protected function outputAsTable(Collection $domains)
    {
        $this->output->table(array_keys(head($domains)), $domains);
    }

    protected function buildResult(array $domains): Collection
    {
        $result = [];
        foreach ($domains as $domain) {
            $result[] = [
                'domain' => $domain,
                'storage_dir' => $this->getDomainStoragePath($domain),
                'env_file' => $this->getDomainEnvFilePath($domain),
            ];
        }

        return collect($result);
    }
}
