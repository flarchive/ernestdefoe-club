<?php

namespace Ernestdefoe\Club;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $user_id
 * @property int $member_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Club extends AbstractModel
{
    public const MAX_SECTIONS = 31;

    protected $table = 'clubs';

    public $timestamps = true;

    protected $casts = [
        'user_id' => 'integer',
        'member_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_members')->withPivot('role', 'joined_at');
    }

    /** @return HasMany<Pick, $this> */
    public function picks(): HasMany
    {
        return $this->hasMany(Pick::class)->orderByDesc('month')->orderByDesc('id');
    }
}
