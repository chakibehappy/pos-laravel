<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    // Using guarded empty allows all fields to be mass-assigned for our sprint
    protected $guarded = [];

    /**
     * Get the type that owns the store.
     */
    public function store_type(): BelongsTo
    {
        return $this->belongsTo(StoreType::class, 'store_type_id');
    }

    /**
     * Get the staff (pos_users) for this store.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(PosUser::class, 'store_id');
    }

    /**
     * Get the products available in this store.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'store_id');
    }

    public function operators()
    {
        return $this->belongsToMany(PosUser::class, 'pos_user_store', 'store_id', 'pos_user_id');
    }
}