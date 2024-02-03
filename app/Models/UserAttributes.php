<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserAttributesFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\UserAttributes
 *
 * @property-read User|null $user
 *
 * @method static Builder|UserAttributes newModelQuery()
 * @method static Builder|UserAttributes newQuery()
 * @method static Builder|UserAttributes query()
 *
 * @property int $id
 * @property int|null $google_id
 * @property int|null $telegram_id
 * @property int|null $vk_id
 * @property int|null $fb_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|UserAttributes whereCreatedAt($value)
 * @method static Builder|UserAttributes whereFbId($value)
 * @method static Builder|UserAttributes whereGoogleId($value)
 * @method static Builder|UserAttributes whereId($value)
 * @method static Builder|UserAttributes whereTelegramId($value)
 * @method static Builder|UserAttributes whereUpdatedAt($value)
 * @method static Builder|UserAttributes whereVkId($value)
 * @method static Builder|UserAttributes whereGithubId($value)
 *
 * @property string|null $github_id
 *
 * @method static UserAttributesFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class UserAttributes extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'user_attributes';

    protected $fillable = [
        'id',
        'google_id',
        'telegram_id',
        'vk_id',
        'fb_id',
        'github_id',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id');
    }
}
