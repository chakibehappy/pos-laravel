<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogger
{
    public static function log(
        string $action,
        string $referenceType,
        ?int $referenceId = null,
        ?string $description = null,
        ?int $createdBy = null,
        ?array $payload = null,
        ?int $storeId = null // <--- 1. Tambahkan parameter baru di sini
    ): void {
        
        // --- LOGIKA OTOMATIS PAYLOAD ---
        if ($action === 'update' && $referenceId && is_null($payload)) {
            try {
                $modelName = Str::studly(Str::singular($referenceType));
                $modelClass = "App\\Models\\{$modelName}";

                if (class_exists($modelClass)) {
                    $model = $modelClass::find($referenceId);
                    if ($model) {
                        $payload = [
                            'old' => $model->getOriginal(),
                            'new' => $model->getAttributes()
                        ];

                        // --- LOGIKA OTOMATIS STORE_ID ---
                        // Jika storeId tidak dikirim manual, coba ambil dari model jika ada
                        if (is_null($storeId) && isset($model->store_id)) {
                            $storeId = $model->store_id;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error("ActivityLogger Error: " . $e->getMessage());
            }
        }

        // Simpan ke Database
        ActivityLog::create([
            'created_by'     => $createdBy ?? Auth::id(),
            'reference_id'   => $referenceId,
            'reference_type' => $referenceType,
            'action'         => $action,
            'description'    => $description,
            'payload'        => $payload,
            'store_id'       => $storeId, // <--- 2. Masukkan ke kolom database
            'created_at'     => now(),
        ]);
    }

    /**
     * Helper untuk log berbasis model
     */
    public static function logModel(
        string $action,
        $model,
        ?string $description = null,
        ?int $createdBy = null,
        ?array $payload = null
    ): void {
        self::log(
            $action,
            $model->getTable(),
            $model->id ?? null,
            $description,
            $createdBy,
            $payload ?? ['old' => $model->getOriginal(), 'new' => $model->getAttributes()],
            $model->store_id ?? null // <--- 3. Tambahkan ini agar otomatis ambil dari model
        );
    }
}