<?php
namespace App\Support;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
class ActivityLogger
{
    public static function log(string $action, ?Model $subject = null, array $properties = [], ?string $label = null): void
    {
        try {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $label ?: ($subject->name ?? $subject->title ?? $subject->order_number ?? null),
                'properties' => $properties,
                'ip_address' => request()?->ip(),
                'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
            ]);
        } catch (\Throwable $e) {
            // ไม่ให้ระบบหลักล่มเพราะ activity log
        }
    }
}
