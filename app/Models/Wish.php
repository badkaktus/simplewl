<?php

namespace App\Models;

use Database\Factories\WishFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Wish
 *
 * @property int $id
 * @property int $wishlist_id
 * @property string $title
 * @property string|null $description
 * @property string|null $url
 * @property string|null $image_url
 * @property string|null $amount
 * @property string|null $currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $is_completed
 * @property string|null $slug
 * @property-read Wishlist $wishlist
 * @method static Builder|Wish newModelQuery()
 * @method static Builder|Wish newQuery()
 * @method static Builder|Wish query()
 * @method static Builder|Wish whereAmount($value)
 * @method static Builder|Wish whereCreatedAt($value)
 * @method static Builder|Wish whereCurrency($value)
 * @method static Builder|Wish whereDescription($value)
 * @method static Builder|Wish whereId($value)
 * @method static Builder|Wish whereImageUrl($value)
 * @method static Builder|Wish whereIsCompleted($value)
 * @method static Builder|Wish whereSlug($value)
 * @method static Builder|Wish whereTitle($value)
 * @method static Builder|Wish whereUpdatedAt($value)
 * @method static Builder|Wish whereUrl($value)
 * @method static Builder|Wish whereWishlistId($value)
 * @method static WishFactory factory($count = null, $state = [])
 * @mixin \Eloquent
 */
class Wish extends Model
{
    public const TABLE_NAME = 'wishes';

    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'wishlist_id',
        'url',
        'image_url',
        'amount',
        'currency',
        'is_completed',
        'slug',
    ];

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
