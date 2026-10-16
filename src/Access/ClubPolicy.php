<?php

namespace Ernestdefoe\Club\Access;

use Ernestdefoe\Club\Club;
use Ernestdefoe\Club\Pick;
use Ernestdefoe\Club\Viewer;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

/** Hosts run their club and its picks; moderators can step in anywhere. */
class ClubPolicy extends AbstractPolicy
{
    public function __construct(private Viewer $viewer)
    {
    }

    public function update(User $actor, Club|Pick $model): ?string
    {
        return $this->runs($actor, $model) ? $this->allow() : $this->deny();
    }

    public function delete(User $actor, Club|Pick $model): ?string
    {
        return $this->runs($actor, $model) ? $this->allow() : $this->deny();
    }

    private function runs(User $actor, Club|Pick $model): bool
    {
        $clubId = $model instanceof Club ? $model->id : $model->club_id;

        return $actor->hasPermission('ernestdefoe-club.moderate') || $this->viewer->hosts($actor, $clubId);
    }
}
