<?php

namespace Ernestdefoe\Club;

use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use WeakMap;

/**
 * What the viewer is in each club and how far they are through each pick.
 * Both are loaded once per actor (one query each) and shared by every field,
 * policy and post that asks, so a page of clubs or posts costs the same as one.
 * Keyed by the actor object, which lives for one request.
 */
class Viewer
{
    /** @var WeakMap<User, array{roles?: array<int, string>, done?: array<int, int>}>|null */
    private static ?WeakMap $memo = null;

    public function __construct(private ConnectionInterface $db)
    {
        self::$memo ??= new WeakMap();
    }

    public function role(User $actor, int $clubId): ?string
    {
        if ($actor->isGuest()) {
            return null;
        }

        $memo = self::$memo[$actor] ?? [];
        $memo['roles'] ??= $this->db->table('club_members')->where('user_id', $actor->id)->pluck('role', 'club_id')->all();
        self::$memo[$actor] = $memo;

        return $memo['roles'][$clubId] ?? null;
    }

    public function hosts(User $actor, int $clubId): bool
    {
        return $this->role($actor, $clubId) === 'host';
    }

    public function done(User $actor, int $pickId): int
    {
        if ($actor->isGuest()) {
            return 0;
        }

        $memo = self::$memo[$actor] ?? [];
        $memo['done'] ??= array_map('intval', $this->db->table('club_progress')->where('user_id', $actor->id)->pluck('done', 'pick_id')->all());
        self::$memo[$actor] = $memo;

        return $memo['done'][$pickId] ?? 0;
    }

    /** After a write, so the response shows it. */
    public function forget(User $actor): void
    {
        unset(self::$memo[$actor]);
    }
}
