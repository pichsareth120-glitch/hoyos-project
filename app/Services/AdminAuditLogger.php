<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AdminAuditLogger
{
    /**
     * Write an admin audit log entry.
     *
     * @param string $module Module name (orders, payments, shipments, products, etc.)
     * @param string $action Action verb (create, update, cancel, refund, etc.)
     * @param string $targetTable Database table affected
     * @param int|string|null $targetId Primary key of targeted record
     * @param array|null $oldValues State snapshot before mutation
     * @param array|null $newValues State snapshot after mutation
     * @param int|null $adminId Staff user ID or null for Auth::id()
     * @return AdminAuditLog
     */
    public static function log(
        string $module,
        string $action,
        string $targetTable,
        int|string|null $targetId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $adminId = null,
        bool $forceNullActor = false
    ): AdminAuditLog {
        $userId = $forceNullActor ? null : ($adminId ?? (Auth::check() ? Auth::id() : null));

        return AdminAuditLog::create([
            'admin_id' => $userId,
            'module' => $module,
            'action' => $action,
            'target_table' => $targetTable,
            'target_id' => $targetId ? (int) $targetId : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'created_at' => now(),
        ]);
    }
}
