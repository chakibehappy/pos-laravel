<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ActivityLogger
{
    /**
     * Basic logger
     */
    public static function log(
        string $action,
        string $referenceType,
        ?int $referenceId = null,
        ?string $description = null,
        ?int $createdBy = null
    ): void {
        
        ActivityLog::create([
            'created_by'     => $createdBy,
            'reference_id'   => $referenceId,
            'reference_type' => $referenceType,
            'action'         => $action,
            'description'    => $description,
            'created_at'     => now(),
        ]);
    }

    /**
     * Cleaner model-based logger
     */
    public static function logModel(
        string $action,
        $model,
        ?string $description = null,
        ?int $createdBy = null
    ): void {
        self::log(
            $action,
            $model->getTable(),
            $model->id ?? null,
            $description,
            $createdBy
        );
    }
}
