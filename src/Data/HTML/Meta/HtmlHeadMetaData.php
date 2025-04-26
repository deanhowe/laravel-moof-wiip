<?php

namespace DeanHowe\Laravel\Moof\Data\HTML\Meta;

use DeanHowe\Laravel\Moof\HTML\Meta\StripeMetaData;
use Spatie\LaravelData\Data;

class HtmlHeadMetaData extends Data
{
    public string $title;

    public string $viewCss;

    public string $description;

    public array $classes;

    public string $relManifest = 'sitess.webmanifest';

    public array $rel;

    public string $shortlink;

    public array $article;

    public string $backLinkTitle;

    public string $backLinkUrl;

    public string $backLinkIcon;

    public string $forwardLinkTitle;

    public string $forwardLinkUrl;

    public string $forwardLinkIcon;

    public string $robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    public string $mobileWebAppCapable = 'yes';

    public string $AppleMobileWebAppCapable = 'yes';

    public string $AppleMobileWebAppTitle;

    public array $og;

    public array $twitter;

    public string $twitterLink;

    public array $getInTouch;

    public string $msapplicationTileColor = '#ffffff';

    public string $msapplicationTileImage = '/mstile-144x144.png';

    public string $themeColor = 'transparent';

    public string $githubSponsorLink = 'https://github.com/sponsors/deanhowe';

    public StripeMetaData $stripe;

    public array $gallery;

    public array $articles;

    public array $snippets;

    public string $calLink;

    public function __construct(
        //
    ) {
    }
}
