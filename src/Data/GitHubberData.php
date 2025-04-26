<?php

namespace DeanHowe\Laravel\Moof\Data;

use DateTime;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class GitHubberData extends Data
{
    public function __construct(
        public string $login,
        public int $id,
        public string $node_id,
        public string $avatar_url,
        public string|Optional|null $gravatar_id,
        public string $url,
        public string $html_url,
        public string $followers_url,
        public string $following_url,
        public string $gists_url,
        public string $starred_url,
        public string $subscriptions_url,
        public string $organizations_url,
        public string $repos_url,
        public string $events_url,
        public string $received_events_url,
        public string $type,
        public bool|Optional|null $site_admin,
        public string $name,
        public string|Optional|null $company,
        public string|Optional|null $blog,
        public string|Optional|null $location,
        public string|Optional|null $email,
        public bool|Optional|null $hireable,
        public string|Optional|null $bio,
        public string|Optional|null $twitter_username,
        public int $public_repos,
        public int $public_gists,
        public int $followers,
        public int $following,
        public DateTime $created_at,
        public DateTime $updated_at,
    ) {
    }
}
