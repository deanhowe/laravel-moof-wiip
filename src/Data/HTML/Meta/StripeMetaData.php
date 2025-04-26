<?php

namespace DeanHowe\Laravel\Moof\Data\HTML\Meta;

use App\Data\Mappers\KebabCaseMapper;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

class StripeMetaData extends Data
{
    /** @var StripeSubscriptionPackageMetaData[] */
    public array $packages;

    #[MapOutputName(KebabCaseMapper::class)]
    #[MapInputName(KebabCaseMapper::class)]
    public string $packagesStrapLine;

    #[MapOutputName(KebabCaseMapper::class)]
    #[MapInputName(KebabCaseMapper::class)]
    public string $packagesCallToAction;

    public function __construct(
        //
    ) {
    }
}
