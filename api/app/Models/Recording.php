<?php

namespace App\Models;

use Database\Factories\RecordingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property int $user_id
 * @property string $egress_id
 * @property int $active
 * @property string $file_name
 * @property \Illuminate\Support\Carbon $last_ping
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static Builder<static>|Recording active()
 * @method static \Database\Factories\RecordingFactory factory($count = null, $state = [])
 * @method static Builder<static>|Recording newModelQuery()
 * @method static Builder<static>|Recording newQuery()
 * @method static Builder<static>|Recording ownedByMe()
 * @method static Builder<static>|Recording query()
 * @method static Builder<static>|Recording whereActive($value)
 * @method static Builder<static>|Recording whereCreatedAt($value)
 * @method static Builder<static>|Recording whereEgressId($value)
 * @method static Builder<static>|Recording whereFileName($value)
 * @method static Builder<static>|Recording whereId($value)
 * @method static Builder<static>|Recording whereLastPing($value)
 * @method static Builder<static>|Recording whereUpdatedAt($value)
 * @method static Builder<static>|Recording whereUserId($value)
 * @mixin \Eloquent
 */
class Recording extends Model
{
    /** @use HasFactory<RecordingFactory> */
    use HasFactory;

    protected $casts = [
        'last_ping' => 'datetime',
    ];

    protected $fillable = [
        'active',
        'egress_id',
        'user_id',
        'file_name',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeOwnedByMe(Builder $query): void
    {
        $query->where('user_id', auth()->id());
    }
}
