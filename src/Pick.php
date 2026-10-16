<?php

namespace Ernestdefoe\Club;

use Carbon\Carbon;
use Ernestdefoe\Marquee\Media;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A club's title for one month: a discussion Marquee links to that title, and
 * the schedule the club follows through it.
 *
 * @property int $id
 * @property int $club_id
 * @property int $discussion_id
 * @property int $media_id
 * @property Carbon $month
 * @property list<array{label: string, due: string|null}> $sections
 * @property Carbon $created_at
 * @property Club $club
 * @property Discussion $discussion
 * @property Media $media
 */
class Pick extends AbstractModel
{
    protected $table = 'club_picks';

    public $timestamps = false;

    protected $casts = [
        'club_id' => 'integer',
        'discussion_id' => 'integer',
        'media_id' => 'integer',
        'month' => 'date',
        'sections' => 'array',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Club, $this> */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /** @return BelongsTo<Discussion, $this> */
    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }

    /** @return BelongsTo<Media, $this> */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function sectionCount(): int
    {
        return count($this->sections ?? []);
    }
}
