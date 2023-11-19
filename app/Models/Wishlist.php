<?php

namespace App\Models;

use Database\Factories\WishlistFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Wishlist
 *
 * @property int $id
 * @property string $title
 * @property int $user_id
 * @property int $is_private
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $slug
 * @method static Builder|Wishlist newModelQuery()
 * @method static Builder|Wishlist newQuery()
 * @method static Builder|Wishlist query()
 * @method static Builder|Wishlist whereCreatedAt($value)
 * @method static Builder|Wishlist whereId($value)
 * @method static Builder|Wishlist whereIsPrivate($value)
 * @method static Builder|Wishlist whereSlug($value)
 * @method static Builder|Wishlist whereTitle($value)
 * @method static Builder|Wishlist whereUpdatedAt($value)
 * @method static Builder|Wishlist whereUserId($value)
 * @method static WishlistFactory factory($count = null, $state = [])
 * @property-read User $user
 * @mixin Eloquent
 */
class Wishlist extends Model
{
    public const TABLE_NAME = 'wishlists';
    public const DEFAULT_WISHLIST_TITLE = 'My Wishlist';
    public const DEFAULT_WISHLIST_SLUG = 'my-wishlist';

    use HasFactory;

    protected $fillable = [
        'title',
        'user_id',
        'slug',
        'is_private',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
