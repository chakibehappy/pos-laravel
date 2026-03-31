<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    public $timestamps = false; // we only use created_at

    protected $fillable = [
        'created_by',
        'reference_id',
        'reference_type',
        'action',
        'description',
        'created_at',
        'payload', 
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'payload'    => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}