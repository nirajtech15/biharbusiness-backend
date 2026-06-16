<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
  protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'category',
        'city',
        'address',
        'phone',
        'whatsapp',
        'email',
        'website',
        'description',
        'services',
        'price_from',
        'image',
        'featured',
        'plan',
        'status',
        'rating',
        'review_count',
        'views',
        'total_reviews',
        'verified',
        'payment_status',
        'payment_id',
        'gallery',
        'payment_amount',
        'is_claimed',
        'payment_plan',

        // New fields
        'opening_hours',
        'offers',
        'direction',
        'menu_cards',
        'facebook',
        'instagram',
        'youtube',
        'linkedin',
    ];

    protected $casts = [
        'price_from'    => 'decimal:2',
        'rating'        => 'decimal:1',
        'featured'      => 'boolean',
        'verified'      => 'boolean',
        'is_claimed'    => 'boolean',
        'views'         => 'integer',
        'review_count'  => 'integer',
        'total_reviews' => 'integer',

        // Array casts
        'gallery'       => 'array',
        'opening_hours' => 'array',
        'offers'        => 'array',
        'menu_cards'    => 'array',
    ];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function reviews() { return $this->hasMany(Review::class); }
}
