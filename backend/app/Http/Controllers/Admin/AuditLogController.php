<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a paginated listing of system audit logs.
     */
    public function index(Request $request)
    {
        $query = AdminAuditLog::with('admin')->latest('id');

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($adminId = $request->input('admin_id')) {
            if ($adminId === 'system') {
                $query->whereNull('admin_id');
            } else {
                $query->where('admin_id', (int) $adminId);
            }
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        if ($targetTable = $request->input('target_table')) {
            $query->where('target_table', $targetTable);
        }

        if ($targetId = $request->input('target_id')) {
            $query->where('target_id', (int) $targetId);
        }

        $logs = $query->paginate(30)->withQueryString();

        $auditStats = AdminAuditLog::selectRaw("
            COUNT(*) as total_entries,
            COUNT(CASE WHEN created_at >= ? THEN 1 END) as today_entries,
            COUNT(DISTINCT admin_id) as distinct_admins,
            COUNT(DISTINCT target_table) as distinct_entities
        ", [now()->startOfDay()])->first();

        $modules = AdminAuditLog::distinct()->pluck('module')->filter()->values();
        $actions = AdminAuditLog::distinct()->pluck('action')->filter()->values();
        $entityTypes = AdminAuditLog::whereNotNull('target_table')->distinct()->pluck('target_table')->filter()->values();
        $admins = User::where('role', 'admin')->get(['id', 'name', 'email']);

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'modules' => $modules,
            'actions' => $actions,
            'entityTypes' => $entityTypes,
            'admins' => $admins,
            'auditStats' => $auditStats,
        ]);
    }

    /**
     * Display a single audit log with side-by-side readable diff.
     */
    public function show(AdminAuditLog $log)
    {
        $oldValues = $this->maskSensitiveData($log->old_values) ?? [];
        $newValues = $this->maskSensitiveData($log->new_values) ?? [];

        $allKeys = array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
        sort($allKeys);

        $diff = [];
        foreach ($allKeys as $key) {
            $hasOld = array_key_exists($key, $oldValues);
            $hasNew = array_key_exists($key, $newValues);
            $oldVal = $hasOld ? $oldValues[$key] : null;
            $newVal = $hasNew ? $newValues[$key] : null;

            $isChanged = (!$hasOld && $hasNew)
                || ($hasOld && !$hasNew)
                || ($oldVal !== $newVal);

            $diff[] = [
                'key' => $key,
                'has_old' => $hasOld,
                'has_new' => $hasNew,
                'old_value' => $oldVal,
                'new_value' => $newVal,
                'is_changed' => $isChanged,
            ];
        }

        return view('admin.audit-logs.show', [
            'log' => $log,
            'diff' => $diff,
            'oldValues' => $oldValues,
            'newValues' => $newValues,
        ]);
    }

    /**
     * Recursively mask sensitive credentials and tokens in audit values.
     */
    public function maskSensitiveData(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sensitivePatterns = ['password', 'remember_token', 'token', 'md5_hash', 'qr_string'];

        $masked = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;
            foreach ($sensitivePatterns as $pattern) {
                if (str_contains($lowerKey, $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $masked[$key] = '********';
            } elseif (is_array($value)) {
                $masked[$key] = $this->maskSensitiveData($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }
}
