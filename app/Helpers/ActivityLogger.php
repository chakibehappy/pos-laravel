<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogger
{
    /**
     * Logger Utama - Sekarang Otomatis Mencari Payload jika tidak dikirim manual
     */
    public static function log(
        string $action,
        string $referenceType,
        ?int $referenceId = null,
        ?string $description = null,
        ?int $createdBy = null,
        ?array $payload = null
    ): void {
        
        // --- LOGIKA OTOMATIS PAYLOAD ---
        // Jika ini adalah 'update' dan payload masih kosong (NULL)
        if ($action === 'update' && $referenceId && is_null($payload)) {
            try {
                // Mencari Model berdasarkan nama tabel (contoh: payment_methods -> PaymentMethod)
                $modelName = Str::studly(Str::singular($referenceType));
                $modelClass = "App\\Models\\{$modelName}";

                if (class_exists($modelClass)) {
                    $model = $modelClass::find($referenceId);
                    if ($model) {
                        // Mengambil data perbandingan
                        $payload = [
                            'old' => $model->getOriginal(), // Data asli dari database
                            'new' => $model->getAttributes() // Data yang baru saja diupdate
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Jika gagal cari model, biarkan payload tetap null agar tidak error
                \Log::error("ActivityLogger Error: " . $e->getMessage());
            }
        }

        // Simpan ke Database
        ActivityLog::create([
            'created_by'     => $createdBy ?? Auth::id(),
            'reference_id'   => $referenceId,
            'reference_type' => $referenceType,
            'action'         => $action,
            'description'    => $description, // Kalimat manual kamu tetap tampil di sini
            'payload'        => $payload,     // JSON Payload otomatis masuk ke sini
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
            $payload ?? ['old' => $model->getOriginal(), 'new' => $model->getAttributes()]
        );
    }
}