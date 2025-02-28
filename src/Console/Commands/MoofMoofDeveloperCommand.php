<?php

namespace DeanHowe\Laravel\Moof\Console\Commands;

use DeanHowe\Laravel\Moof\Data\GitHubberData;
use DeanHowe\Laravel\Moof\MultiDomain\Console\DomainCommandTrait;
use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Yaml\Yaml;
use function Laravel\Prompts\text;

/**
 * MoofMoofDeveloperCommand
 *
 * It's really a GitHub command.
 *
 * Takes a github `username` or `username/rpo-name` and download github info for the user and clones four branches if they are there.
 *      - https://github.com/<username>/<username>
 *      - https://github.com/<username>/<username>.github.io
 *      - https://github.com/<username>/<username>-private
 *      - https://github.com/<username>/<username>-moof
 *
 */
class MoofMoofDeveloperCommand extends GeneratorCommand implements PromptsForMissingInput
{
    use DomainCommandTrait;

    /**
     * The name and signature of the console command.
     * This commands would benefit from an easier way to have language support for the prompts.
     *
     * sudo /usr/local/bin/valet secure flexagon.digital
     *
     * @var string
     */
    protected $signature = 'x-moof:developer
                           {github_username : the github username to create accounts for}}';


    protected $description = "Setup the developer environment for Moof Moof";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {

        $username = $this->argument('github_username');

        $gitHubRawDetails = Cache::rememberForever('github.users.' . $username, function () use ($username) {
            return Http::get("https://api.github.com/users/" . $username)->json();
        });

        $gitHubRawDetails = collect($gitHubRawDetails);


        if($gitHubRawDetails->has('status') && $gitHubRawDetails->doesntContain('node_id') && $gitHubRawDetails['status'] === "404") {

            $this->error("{$username} does not appear to a valid GitHUb user.");
            $this->newLine();

            return;
        }

        $gitHubDetails = GitHubberData::from($gitHubRawDetails);

        $yaml = Yaml::dump($gitHubDetails->toArray(), 2 ,4, YAML::DUMP_MULTI_LINE_LITERAL_BLOCK);

        #dump(__DIR__);
        #dump(app_path());
        //$this->files->exists();

        #ray($gitHubRawDetails, $gitHubDetails)->red();


//        $response = Http::withHeaders([
////            'Authorization' => 'token your-github-token',  // Optional
////            'User-Agent' => 'Your-App',  // GitHub requires a user agent
//        ])->post("https://moof.one.test/webhooks/beta-program", ['githubber'=>$gitHubDetails]);


        ray($gitHubRawDetails)->orange();
        ray($yaml)->orange();

        $repos = $this->downloadGithubRepo($username);

        // Assume we have the repos


        /**
         * Generate new keys for moof
         *      ssh-keygen -t ed25519 -C "your_email@example.com"
         *      ssh-keygen -t ed25519 -C "grayarea-uk@moof.uk"
         * Enter you details…
         * Is the macOS agent running?
         *      eval "$(ssh-agent -s)"
         * Sort out the ssh config
         *      open ~/.ssh/config
         *      touch ~/.ssh/config
         *      > Host moof.github
         *      >   Hostname github.com
         *      >   AddKeysToAgent yes
         *      >   UseKeychain yes
         *      >   IdentityFile ~/.ssh/id_ed25519
         *
         *      ssh-add --apple-use-keychain ~/.ssh/id_ed25519
         *      pbcopy < ~/.ssh/id_ed25519.pub
         *
         */

    }

    protected function promptForMissingArgumentsUsing(): array
    {

        return [
            'github_username' => fn() => text(
                label: 'Are you happy to join the limited beta program?',
                default: 'deanhowe',
            ),
        ];
    }

    protected function getStub()
    {
        // TODO: Implement getStub() method.
    }
}