<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLogger
{
    public static function log(string $action, ?Model $subject = null, array $metadata = []): void
    {
        try {
            AdminActivityLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'metadata' => $metadata ?: null,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
