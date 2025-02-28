<?php

declare(strict_types=1);

namespace DeanHowe\Laravel\Moof\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\progress;

use Spatie\Image\Manipulations;

final class MoofImageProcessCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     * This commands would benefit from an easier way to have language support for the prompts.
     *
     * @var string
     */
    protected $signature = 'x-moof:images
                           --force : Force the operation to run!';

    protected $description = 'Setup the developer environment for Moof Moof';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //
        //(new Filesystem)->ensureDirectoryExists(app()->exactPublishedDomainAssetsPath());
        ray(app()->exactRawDomainAssetsPath());
        $rawAssets = (new Filesystem())->allFiles(app()->exactRawDomainAssetsPath());
        $publishedAssets = (new Filesystem())->allFiles(app()->exactPublishedDomainAssetsPath());
        ray($rawAssets, $publishedAssets);
        $users = progress(
            label: 'Updating users',
            steps: $rawAssets,
            callback: function ($rawAsset, $progress) {
                $progress
                    ->label("Updating {$rawAsset->getFilename()}")
                    ->hint("Size {$rawAsset->getSize()}");
                processImages($rawAsset, width: 540, height: 540, fit: Manipulations::FIT_FILL);
                $progress
                    ->label("Updating {$rawAsset->getFilename()} adding thumbs");

                return processImages($rawAsset, destination: '/thumbs', width: 360, height: 360, fit: Manipulations::FIT_CROP);
            },
            hint: 'This may take some time.',
        );
        //        array_walk($rawAssets, function ($rawAsset) {
        //
        //
        //        });
//        ray($rawAssets, $publishedAssets);

    }

    protected function promptForMissingArgumentsUsing(): array
    {

        return [];
    }
}
