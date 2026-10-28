<?php

namespace Ernestdefoe\Club;

use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;

/**
 * A reply about section N of a club pick stays closed to anyone who hasn't
 * finished section N, so the text never reaches their browser. Your own
 * replies are always open to you. Only replies that name a section are
 * checked, so ordinary discussions cost nothing.
 */
class SpoilerGate
{
    /** @var array<int, int>|null discussion id => pick id, for every pick */
    private ?array $picks = null;

    public function __construct(private ConnectionInterface $db, private Viewer $viewer)
    {
    }

    public function closed(Post $post, User $actor): bool
    {
        $section = (int) $post->getAttribute('club_section');

        if ($section < 1 || $post->user_id === $actor->id) {
            return false;
        }

        $pickId = $this->pickFor((int) $post->discussion_id);

        return $pickId !== null && $this->viewer->done($actor, $pickId) < $section;
    }

    public function pickFor(int $discussionId): ?int
    {
        // Picks are few (about one a month per club): one small query, once.
        $this->picks ??= array_map('intval', $this->db->table('club_picks')->pluck('id', 'discussion_id')->all());

        return $this->picks[$discussionId] ?? null;
    }
}
