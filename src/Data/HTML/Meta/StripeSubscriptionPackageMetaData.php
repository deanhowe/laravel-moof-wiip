<?php

namespace DeanHowe\Laravel\Moof\Data\HTML\Meta;

use App\Data\Mappers\KebabCaseMapper;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

class StripeSubscriptionPackageMetaData extends Data
{
    //    public $subscription_id;
    //    public $subscription_item_id;
    //    public $subscription_item_price_id;
    //    public $subscription_item_price_product_id;
    //    public $subscription_item_price_plan_id;
    //    public $subscription_item_price_subscription_id;
    //    public $subscription_item_price_subscription_item_id;
    //    public $subscription_item_price_subscription_item_price_id;
    //    public $subscription_item_price_subscription_item_price_product_id;
    //    public $subscription_item_price_subscription_item_price_plan_id;
    //    public $subscription_item_price_subscription_item_price_subscription_id;
    //    public $subscription_item_price_subscription_item_price_subscription_item_id;
    //    public $subscription_item_price_subscription_item_price_subscription_item_price_id;
    public $name;

    public $description;

    public $price;

    #[MapOutputName(KebabCaseMapper::class)]
    #[MapInputName(KebabCaseMapper::class)]
    public array $paymentLinks;

    public $currency;

    public $interval;

    public $interval_count;

    public $trial_period_days;

    public $product_id;

    public $plan_id;

    public $subscription_id;

    public $subscription_item_id;

    public $features;

    public $metadata;

    //
    public function __construct(
    ) {
    }
}
