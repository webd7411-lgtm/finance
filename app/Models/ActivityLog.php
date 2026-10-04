<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'details',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Static helper to record an activity log entry
     */
    public static function log(string $action, string $module, ?string $details = null, ?int $userId = null): self
    {
        return self::create([
            'user_id'    => $userId ?? auth()->id(),
            'action'     => $action,
            'module'     => $module,
            'details'    => $details,
            'ip_address' => request()->ip(),
        ]);
    }

    public function getActionBadgeAttribute(): string
    {
        return match(strtolower($action = $this->action)) {
            'created'   => '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-plus-circle me-1"></i>Created</span>',
            'updated'   => '<span class="badge bg-primary-subtle text-primary border border-primary"><i class="bi bi-pencil me-1"></i>Updated</span>',
            'deleted'   => '<span class="badge bg-danger-subtle text-danger border border-danger"><i class="bi bi-trash me-1"></i>Deleted</span>',
            'closed', 'finalized' => '<span class="badge bg-indigo-subtle border" style="background:#ede9fe; color:#4338ca;"><i class="bi bi-lock-fill me-1"></i>Finalized</span>',
            'unlocked', 'reopened' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning"><i class="bi bi-unlock-fill me-1"></i>Reopened</span>',
            'login'     => '<span class="badge bg-info-subtle text-info-emphasis border border-info"><i class="bi bi-box-arrow-in-right me-1"></i>Login</span>',
            default     => '<span class="badge bg-secondary">' . ucfirst($action) . '</span>',
        };
    }
}
