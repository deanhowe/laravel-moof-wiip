<?php

namespace DeanHowe\Laravel\Moof\Diagnostics;

final class RayIntegration
{
    public function disableLegacyClient(bool $enabled): void
    {
        if ($enabled && function_exists('ray')) {
            ray()->disable();
        }
    }
}
