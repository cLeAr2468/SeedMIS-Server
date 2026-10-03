<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Get paginated activity logs
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $module = $request->input('module');
            $action = $request->input('action');
            $userType = $request->input('user_type');
            
            // Get logged-in user info
            $loggedUserId = $request->input('logged_user_id');
            $loggedUserType = $request->input('logged_user_type');

            $query = ActivityLog::query()->orderBy('created_at', 'desc');

            // If staff is logged in, only show their own logs
            if ($loggedUserType === 'staff' && $loggedUserId) {
                $query->where('user_id', $loggedUserId)
                      ->where('user_type', 'staff');
            }

            // Apply filters (only if admin or no logged user restriction)
            if ($module) {
                $query->where('module', $module);
            }

            if ($action) {
                $query->where('action', $action);
            }

            if ($userType && $loggedUserType !== 'staff') {
                // Only allow user_type filter for admin
                $query->where('user_type', $userType);
            }

            $logs = $query->paginate($perPage);

            // Enhance logs with user names
            $logs->getCollection()->transform(function ($log) {
                if ($log->user_type === 'admin') {
                    // Admin uses numeric ID
                    $admin = Admin::find($log->user_id);
                    $log->user_name = $admin ? $admin->name : 'Unknown Admin';
                } else {
                    // Staff uses staff_id (e.g., "STF-0001")
                    $staff = Staff::where('staff_id', $log->user_id)->first();
                    if (!$staff) {
                        // Fallback: try as numeric ID
                        $staff = Staff::find($log->user_id);
                    }
                    $log->user_name = $staff ? trim($staff->first_name . ' ' . $staff->last_name) : 'Unknown Staff';
                }
                return $log;
            });

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get activity log statistics
     */
    public function statistics(Request $request)
    {
        try {
            // Get logged-in user info
            $loggedUserId = $request->input('logged_user_id');
            $loggedUserType = $request->input('logged_user_type');

            $query = ActivityLog::query();

            // If staff is logged in, only count their own logs
            if ($loggedUserType === 'staff' && $loggedUserId) {
                $query->where('user_id', $loggedUserId)
                      ->where('user_type', 'staff');
            }

            $totalLogs = (clone $query)->count();
            $todayLogs = (clone $query)->whereDate('created_at', today())->count();
            $weekLogs = (clone $query)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
            
            $byModule = (clone $query)->selectRaw('module, COUNT(*) as count')
                ->groupBy('module')
                ->pluck('count', 'module');

            $byUserType = (clone $query)->selectRaw('user_type, COUNT(*) as count')
                ->groupBy('user_type')
                ->pluck('count', 'user_type');

            $recentActions = (clone $query)->selectRaw('action, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_logs' => $totalLogs,
                    'today_logs' => $todayLogs,
                    'week_logs' => $weekLogs,
                    'by_module' => $byModule,
                    'by_user_type' => $byUserType,
                    'recent_actions' => $recentActions,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
