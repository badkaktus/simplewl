<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wish extends Model
{
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
