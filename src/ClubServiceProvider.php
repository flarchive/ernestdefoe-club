<?php

namespace Ernestdefoe\Club;

use Flarum\Foundation\AbstractServiceProvider;

class ClubServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // One per request: it remembers which discussions are picks.
        $this->container->singleton(SpoilerGate::class);
    }
}
