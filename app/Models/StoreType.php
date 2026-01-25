<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreType extends Model
{
    protected $table = 'store_types';
    
    protected $fillable = ['name'];

    /**
     * Get all stores assigned to this type.
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'store_type_id');
    }
}