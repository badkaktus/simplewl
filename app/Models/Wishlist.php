<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    public const DEFAULT_WISHLIST_TITLE = 'My Wishlist';
    public const DEFAULT_WISHLIST_SLUG = 'my-wishlist';

    use HasFactory;

    protected $fillable = [
        'title',
        'user_id',
        'slug',
        'is_private',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
