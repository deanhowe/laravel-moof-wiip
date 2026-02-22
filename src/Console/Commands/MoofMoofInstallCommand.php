<?php

declare(strict_types=1);

namespace DeanHowe\Laravel\Moof\Console\Commands;

use Carbon\Carbon;
use DeanHowe\Laravel\Moof\MultiDomain\Console\DomainCommandTrait;
use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
// use Wink\WinkAuthor;

final class MoofMoofInstallCommand extends GeneratorCommand implements PromptsForMissingInput
{
    use DomainCommandTrait;

    /**
     * The name and signature of the console command.
     * This commands would benefit from an easier way to have language support for the prompts.
     *
     * @var string
     */
    protected $signature = 'x-moof:install
                           {beta : join the beta program}
                           {app_name : the initial app name to be used during set-up}
                           {app_domain : the initial domain to be used during set-up (e.g. moof.one)}
                           {app_url : the initial app url to be used during set-up (e.g. https://moof.one)}
                           {app_logo : the initial app logo to be used during set-up (e.g. images/moof-logo.svg)}
                           {full_name : the developer/admin name to use during set-up}
                           {username? : the developer/admin username to be used during set-up}
                           {email? : the developer/admin email address to be used during set-up}
                           {password? : the developer/admin password to be used during set-up}
                           {profile_bio? : the developer/admin profile bio to be used during set-up (Wink)}
                           
                           {openai_api_key? : the openai_api_key to be used during set-up}
                           {openai_organization? : the openai_organization to be used during set-up}
                           
                           {slack_oauth_token? : the Slack Bot User OAuth Token to be used during set-up}
                           {slack_default_channel? : the Slack Bot User Default Channel to be used during set-up}
                           {slack_webhook? : the Slack General Webhook URL to be used during set-up}
                           
                           {--current_username= : The current (previously used) username of the install}
                           {--current_email= : The current (previously used) email address of the install}
                           {--current_password= : The current (previously updated) password of the install}
                           {--force : Force the operation to run when in production.}';

    /**
     * The description of the command.
     *
     * @var string The description of the command
     */
    protected $description = 'Install moof::moof - the Laravel framework for Moofiineers';

    private bool $hasDeanFinishedThis = false;

    /**
     * The composer data from the composer.json file.
     *
     * @var array The composer data from the composer.json file
     */
    private array $composerData;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        $this->error('Could not read composer.json');
        // $this->files->isFile();
        dump(__DIR__);

        return;

        // Publish…
        $this->callSilent('ray:publish-config');

        $this->callSilent('vendor:publish', ['--tag' => 'moof-moof-public-assets', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'moof-moof-views', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'moof-moof-configs', '--force' => true]);

        $this->callSilent('vendor:publish', ['--tag' => 'blade-mooficons', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'blade-heroicons', '--force' => true]);

        $this->callSilent('vendor:publish', ['--tag' => 'moof-moof-env', '--force' => true]);

        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        $app_domain = $this->argument('app_domain');
        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        $full_name = $this->argument('full_name');
        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        $username = $this->argument('username');
        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        $email = $this->argument('email');
        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        $password = $this->argument('email');
        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        $profile_bio = $this->argument('profile_bio');

        // $this->call('migrate:fresh');

        // blade-icons:install

/*
        if ($this->confirm('Would you like to install Wink? (the Moof version)', false)) {

            $this->call('wink:install');

            $this->call('migrate:fresh', [
                '--database' => config('wink.database_connection'),
                '--path' => 'vendor/themsaid/wink/src/Migrations',
                '--force' => true,
            ]);

            $shouldCreateNewAuthor =
                !Schema::connection(config('wink.database_connection'))->hasTable('wink_authors') ||
                !WinkAuthor::count();

            if ($shouldCreateNewAuthor) {

                WinkAuthor::create([
                    'id' => (string) Str::uuid(),
                    'name' => $full_name,
                    'slug' => Str::slug($username),
                    'bio' => $profile_bio,
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);

                $this->line('');
                $this->line('Wink is ready for use. Enjoy!');
                $this->line('You may log in using <info>' . $email . '</info> and password: <info>' . $password . '</info>');
                $this->line('');
            }
        }
        */

        //        $this->call('x-moof:domain-add', [
        //            'domain' => 'moof',
        //            '--domain_values' => json_encode([
        //                'MOOF_USERNAME' => $username,
        //                'MOOF_EMAIL' => $email,
        //                'MOOF_PASSWORD' => $password,
        //            ]),
        //            '--force' => true,
        //            '--dev' => true,
        //        ]);

        if (!$this->hasDeanFinishedThis) {
            $this->error('This feature has not been finished yet!');

            return;
        }
        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        $package_vendor = Str::slug($this->argument('package_vendor'));
        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        $package_name = Str::slug($this->argument('package_name'));
        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        $package_classname = Str::title($this->argument('package_classname'));

        $package_full_name = Str::lower($package_vendor . '/' . $package_name);

        /** @psalm-suppress PossiblyInvalidArgument, PossiblyInvalidCast */
        $package_email = Str::lower($this->argument('package_email'));

        $composerPath = __DIR__ . '/../../../../composer.json';

        if ($composerFile = file_get_contents($composerPath)) {
            $composerData = (array) json_decode($composerFile, true);

            $this->composerData = $composerData;

            $this->warn("  We are about to rename the package… {$package_full_name} by {$package_email}.");
            $this->newLine();
            $this->warn('        from: deanhowe/laravel-package-boilerplate by deanhowe@gmail.com');
            $this->warn("        to: {$package_full_name} by {$package_email}");
            $this->newLine();
            $this->warn('  We will have more questions for you…');

            if (!$this->confirm('Do you wish to continue?', true)) {
                $this->info('Aborting...');
                $this->newLine();

                return;
            }

            $this->composerData['name'] = $package_full_name;

            /* @psalm-suppress MixedArrayAssignment */
            $this->composerData['autoload']['psr-4'] = ["{$package_classname}\\" => 'src/'];

            /* @psalm-suppress MixedArrayAssignment */
            $this->composerData['extra']['laravel']['providers'][0] = "{$package_classname}\\Providers\\ServiceProvider";

            /* @psalm-suppress MixedArrayAssignment */
            $this->composerData['description'] = $this->ask('Please enter a description for the package');

            /* @psalm-suppress MixedArrayAssignment */
            $this->composerData['version'] = $this->ask('Please enter a version number for the package');

            /* @psalm-suppress MixedArrayAssignment */
            $this->composerData['type'] = $this->choice(
                'Please enter a type for the package',
                ['library', 'project', 'metapackage', 'composer-plugin'],
                'library'
            );

            /** @psalm-suppress MixedAssignment */
            $keywords = $this->ask('Please enter keywords for the package (comma seperated)');
            if (filled($keywords) && is_string($keywords)) {
                /* @psalm-suppress MixedArrayAssignment */
                $this->composerData['keywords'] = explode(',', $keywords);
            }

            /** @psalm-suppress MixedAssignment */
            $homepage = $this->ask('Please enter a homepage for the package');
            if (filled($homepage)) {
                $this->composerData['homepage'] = $homepage;
            }

            /** @psalm-suppress MixedAssignment */
            $read_me = $this->ask('Where is the README for the package?', 'README.md');
            if (filled($read_me)) {
                $this->composerData['readme'] = $read_me;
            }

            $this->composerData['time'] = Carbon::now()->rawFormat('Y-m-d H:i:s');

            /* @psalm-suppress MixedArrayAssignment */
            if (is_array($composerData['license-options'])) {
                $this->composerData['license'] = $this->choice(
                    'Which license do you want to use?',
                    $composerData['license-options'],
                    8
                );
            }

            $this->composerData['authors'] = [];
            $this->_askForAuthorDetails();

            while ($this->confirm('Add another author?')) {
                $this->_askForAuthorDetails();
            }

            if ($this->confirm('Add details for support?')) {
                if ($this->confirm('Use github values?')) {

                    /* @psalm-suppress MixedArrayAssignment, MixedArgument */
                    $this->composerData['support'] = collect($this->composerData['support'])->map(
                        fn (string $value) => Str::of($value)->replace('deanhowe/laravel-package-boilerplate', $package_full_name)
                    )->toArray();

                    /** @psalm-suppress MixedAssignment */
                    $support_email = $this->ask('Please enter the support email');
                    if (filled($support_email)) {
                        /* @psalm-suppress MixedArrayAssignment */
                        $this->composerData['support']['email'] = $support_email;
                    }

                    /** @psalm-suppress MixedAssignment */
                    $support_irc = $this->ask('Please enter IRC channel for support, e.g irc://server/channel');
                    if (filled($support_irc)) {
                        /* @psalm-suppress MixedArrayAssignment */
                        $this->composerData['support']['irc'] = $support_irc;
                    }

                } else {
                    if (is_array($composerData['support-suggestions'])) {
                        /* @psalm-suppress MixedAssignment */
                        foreach ($composerData['support-suggestions'] as $type => $support_suggestion) {
                            /** @psalm-suppress MixedArgument */
                            $support = $this->ask($support_suggestion);
                            if (filled($support)) {
                                /* @psalm-suppress MixedArrayAssignment */
                                $this->composerData['support'][$type] = $support;
                            }
                        }
                    }
                }
            }

            $this->composerData['funding'] = [];
            while ($this->confirm('Add funding details?')) {
                /** @psalm-suppress MixedAssignment */
                $funding_source = $this->choice(
                    'Which type of funding source?',
                    ['github', 'patreon', 'tidelift', 'other'],
                    'github'
                );
                /** @psalm-suppress MixedAssignment */
                $funding_url = $this->ask('Please enter the funding url');
                if (filled($funding_url) && filled($funding_source)) {
                    /* @psalm-suppress MixedArrayAssignment */
                    $this->composerData['funding'][] = [
                        'type' => $funding_source,
                        'url' => $funding_url,
                    ];
                }
            }

            unset($this->composerData['license-options'], $this->composerData['provide-suggestions'], $this->composerData['replace-suggestions'], $this->composerData['repositories-suggestions'], $this->composerData['support-suggestions']);

            /*
             * This all took far longer than I expected, mainly because I got a bit side-tracked by psalm - so I'm going to leave it for now…
             * This all started because I couldn't find the package:rename command in the original package I forked.
             * I contemplated not writing the `composer.json` out - but I decided in the end it's better to do that and prompt
             * the developer for the rest to be filled out either manually or via the command line.
             *
             * TODO: ask for require - easier from the command line?
             * TODO: ask for require-dev - easier from the command line?
             * TODO: ask for conflict - might take a while?
             * TODO: ask for suggest - low-hanging fruit?
             * TODO: ask for autoload - easier if the devs update this themselves if needed?
             * TODO: ask for autoload-dev - easier if the devs update this themselves if needed?
             * TODO: ask for minimum-stability - low-hanging fruit?
             * TODO: ask for prefer-stable - low-hanging fruit?
             * TODO: ask for repositories - might take a while?
             * TODO: ask for config - might take a while?
             *
             */

            $json = json_encode($this->composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if (!$this->confirm('Promise you’ll read and update the `composer.json` file we are about to create manually?', true)) {
                $this->info('Aborting...');
                $this->newLine();

                return;
            }

            $this->info('  Renaming package...');

            file_put_contents($composerPath, $json);

            $this->info('   composer.json updated successfully.');

        } else {
            $this->error('Could not read composer.json');
        }
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {

        return [
            'beta' => fn () => select(
                label: 'Are you happy to join the limited beta program?',
                options: [
                    'yes' => 'Yes',
                ]
            ),
            'app_name' => fn () => text(
                label: 'What is the initial app name to be used during set-up?',
                placeholder: 'Your App Name',
                default: env('APP_NAME'),
                hint: env('APP_NAME', 'Moof Test')
            ),
            'app_domain' => fn () => text(
                label: 'What is the initial domain to be used during set-up?',
                placeholder: '',
                default: $this->guessFQDN(),
                validate: fn ($value) => $this->validateDomain($value),
                hint: 'e.g. moof.test'
            ),

            'app_url' => fn () => text(
                label: 'What is the initial app url to be used during set-up?',
                placeholder: '',
                default: env('APP_URL', 'https://' . $this->argument('app_domain')),
                validate: fn ($value) => $this->validateUrl($value),
                hint: 'e.g. https://moof.test'
            ),

            'app_logo' => fn () => text(
                label: 'What is the initial app logo to be used during set-up?',
                placeholder: '',
                default: env('APP_LOGO', $this->guessLogo($this->argument('app_domain'))),
                validate: fn ($value) => $this->validateSVGPath($value),
                hint: 'e.g. images/moof-logo.svg'
            ),

            'full_name' => fn () => text(
                label: 'What is the developer/admin name to use during set-up?',
                placeholder: 'Dean Howe',
                default: env('SENIOR_DEV_NAME', 'Dean Howe'),
                validate: fn ($value) => $this->validateFullname($value),
                hint: 'e.g. Dean Howe'
            ),

            'username' => fn () => text(
                label: 'What is the developer/admin username to be used during set-up?',
                placeholder: '',
                default: env('APP_DOMAIN'),
                validate: fn ($value) => $this->validateDomain($value),
                hint: 'e.g. yourappname.test'
            ),

            'email' => fn () => text(
                label: 'What is the developer/admin email address to be used during set-up?',
                placeholder: '',
                default: env('APP_DOMAIN'),
                validate: fn ($value) => $this->validateDomain($value),
                hint: 'e.g. yourappname.test'
            ),

            'password' => fn () => text(
                label: 'What is the developer/admin password to be used during set-up?',
                placeholder: '',
                default: env('APP_DOMAIN'),
                validate: fn ($value) => $this->validateDomain($value),
                hint: 'e.g. yourappname.test'
            ),

            'profile_bio' => fn () => text(
                label: 'What is the developer/admin profile bio to be used during set-up (Wink)?',
                placeholder: '',
                default: env('APP_DOMAIN'),
                validate: fn ($value) => $this->validateDomain($value),
                hint: 'e.g. yourappname.test'
            ),
        ];

        //        return [
        //            'package_name' => 'What name (slug) would you like to use for your package?',
        //            'package_vendor' => 'What vendor name (slug) would you like to use?',
        //            'package_classname' => 'What classname would you like to replace the word `Package` with (e.g. `MyAwesomePackage`) in the composer file?',
        //            'package_email' => 'What email address would you like to use?',
        //        ];
    }

    private function _askForAuthorDetails(): void
    {
        $author = [];

        /** @psalm-suppress MixedAssignment */
        $author_name = text('Please enter the author name');
        /** @psalm-suppress MixedAssignment */
        $author_email = text('Please enter the author email');
        /** @psalm-suppress MixedAssignment */
        $author_homepage = text('Please enter the author homepage (optional)');
        /** @psalm-suppress MixedAssignment */
        $author_role = text('Please enter the author role (optional)');

        if (filled($author_name)) {
            /* @psalm-suppress MixedAssignment */
            $author['name'] = $author_name;
        }
        if (filled($author_email)) {
            /* @psalm-suppress MixedAssignment */
            $author['email'] = $author_email;
        }
        if (filled($author_homepage)) {
            /* @psalm-suppress MixedAssignment */
            $author['homepage'] = $author_homepage;
        }
        if (filled($author_role)) {
            /* @psalm-suppress MixedAssignment */
            $author['role'] = $author_role;
        }
        if (filled($author)) {
            /* @psalm-suppress MixedArrayAssignment */
            $this->composerData['authors'][] = $author;
        }
    }

    public function guessFQDN(): string
    {
        // take the `app_name` and guess a suitable a FQDN
        $fqdn = $this->argument('app_name');
        $fqdn = Str::of($fqdn)->replace(' ', '')->lower();
        if (env('APP_ENV') === 'local') {
            $fqdn = Str::of($fqdn)->replace('test', '') . '.test';
        }

        return (string) $fqdn;
    }

    public function guessLogo($svg): string
    {
        // take the `app_name` and guess a suitable a FQDN
        $svg = Str::of($svg)->replace('.test', '-logo.svg')->prepend('images/');

        return $svg->toString();
    }

    private function validateDomain($fqdn)
    {
        if (Str::contains($fqdn, ['/', ' ', ':'])) {
            return 'The domain should not contain slashes, dots or colons.';
        }

        if (Str::contains($fqdn, ['http://', 'https://'])) {
            return 'The domain should not contain http:// or https://.';
        }

        if (filter_var($fqdn, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            // Handle invalid FQDN
            return 'The domain needs to be a Full Qualified Domain Name (FQDN).';
        }

        return null;

    }

    private function validateUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL, FILTER_SANITIZE_URL) === false) {
            // Handle invalid FQDN
            return 'The url needs to be a valid url.';
        }

        return null;

    }

    private function validateSVGPath($svgPath)
    {
        if (!Str::of($svgPath)->startsWith('images/') || !Str::of($svgPath)->endsWith('-logo.svg')) {
            return 'This is not valid, the logo needs to be in public `images` folder and end with `-logo.svg`.';
        }

        return null;

    }

    private function validateFullname($fullName)
    {
        $validator = Validator::make(['fullName' => $fullName], [
            'fullName' => 'regex:/^[a-zA-Z ‘’\-]+$/',
        ]);
        if ($validator->fails()) {
            // Handle validation failure
            return 'The full name should only contain letters.';
        }

        return null;

    }

    protected function getStub()
    {
        // TODO: Implement getStub() method.
    }
}
