<?php

namespace App\Models;

use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property string $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\MeetingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereUserId($value)
 * @mixin \Eloquent
 */
class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
    ];
}
