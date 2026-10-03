<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an activity
     */
    public static function log(
        $userId, // Now accepts both int and string (e.g., 1 or "STF-0001")
        string $userType,
        string $action,
        string $module,
        string $description,
        ?array $details = null
    ) {
        try {
            ActivityLog::create([
                'user_id' => (string)$userId, // Cast to string to handle both types
                'user_type' => $userType,
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'details' => $details,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log activity: ' . $e->getMessage());
        }
    }

    /**
     * Log request status change
     */
    public static function logRequestStatusChange(
        $userId, // Mixed: int or string
        string $userType,
        int $requestId,
        string $oldStatus,
        string $newStatus,
        ?string $clientName = null
    ) {
        $description = sprintf(
            'Request #%d status changed from %s to %s%s',
            $requestId,
            ucfirst($oldStatus),
            ucfirst($newStatus),
            $clientName ? " for client {$clientName}" : ''
        );

        self::log(
            $userId,
            $userType,
            'updated',
            'request',
            $description,
            [
                'request_id' => $requestId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'client_name' => $clientName,
            ]
        );
    }

    /**
     * Log production activity
     */
    public static function logProduction(
        $userId, // Mixed: int or string
        string $userType,
        string $action,
        string $batchId,
        ?array $details = null
    ) {
        $description = '';

        switch ($action) {
            case 'created':
                $seedlingType = $details['seedling_type'] ?? 'Unknown';
                $quantity = $details['quantity_sown'] ?? 0;
                $description = sprintf(
                    'Created production batch %s: %s (%s seedlings)',
                    $batchId,
                    $seedlingType,
                    number_format($quantity)
                );
                break;

            case 'stage_updated':
                $oldStage = $details['old_stage'] ?? 'Unknown';
                $newStage = $details['new_stage'] ?? 'Unknown';
                $newQuantity = $details['new_quantity'] ?? 0;
                $description = sprintf(
                    'Updated batch %s: Stage changed from %s to %s (Current: %s seedlings)',
                    $batchId,
                    $oldStage,
                    $newStage,
                    number_format($newQuantity)
                );
                break;

            case 'transferred':
                $seedlingType = $details['seedling_type'] ?? 'Unknown';
                $quantity = $details['quantity'] ?? 0;
                $description = sprintf(
                    'Transferred batch %s to inventory: %s (%s seedlings)',
                    $batchId,
                    $seedlingType,
                    number_format($quantity)
                );
                break;

            default:
                $description = sprintf('Production batch %s %s', $batchId, $action);
        }

        self::log($userId, $userType, $action, 'production', $description, $details);
    }

    /**
     * Log inventory activity
     */
    public static function logInventory(
        $userId, // Mixed: int or string
        string $userType,
        string $action,
        string $seedlingType,
        ?array $details = null
    ) {
        $description = '';

        switch ($action) {
            case 'created':
                $quantity = $details['quantity'] ?? 0;
                $price = $details['price'] ?? 0;
                $description = sprintf(
                    'Added %s to inventory (%s seedlings at ₱%s each)',
                    $seedlingType,
                    number_format($quantity),
                    number_format($price, 2)
                );
                break;

            case 'updated':
                $changedFields = [];
                if (isset($details['old_price']) && isset($details['new_price'])) {
                    $changedFields[] = sprintf(
                        'price (₱%s → ₱%s)',
                        number_format($details['old_price'], 2),
                        number_format($details['new_price'], 2)
                    );
                }
                if (isset($details['old_quantity']) && isset($details['new_quantity'])) {
                    $changedFields[] = sprintf(
                        'quantity (%s → %s)',
                        number_format($details['old_quantity']),
                        number_format($details['new_quantity'])
                    );
                }
                if (isset($details['old_status']) && isset($details['new_status'])) {
                    $changedFields[] = sprintf(
                        'status (%s → %s)',
                        $details['old_status'],
                        $details['new_status']
                    );
                }
                
                $fieldsText = !empty($changedFields) ? implode(', ', $changedFields) : 'details';
                $description = sprintf('Updated %s inventory: %s', $seedlingType, $fieldsText);
                break;

            case 'distributed':
                $quantity = $details['quantity'] ?? 0;
                $clientName = $details['client_name'] ?? 'Unknown';
                $description = sprintf(
                    'Distributed %s seedlings of %s to %s',
                    number_format($quantity),
                    $seedlingType,
                    $clientName
                );
                break;

            default:
                $description = sprintf('Inventory %s for %s', $action, $seedlingType);
        }

        self::log($userId, $userType, $action, 'inventory', $description, $details);
    }

    /**
     * Log client activity
     */
    public static function logClient(
        $userId, // Mixed: int or string
        string $userType,
        string $action,
        string $clientName,
        ?string $clientId = null
    ) {
        $actionText = [
            'created' => 'added',
            'updated' => 'updated',
            'deleted' => 'deleted',
        ][$action] ?? $action;

        $description = sprintf(
            'Client %s (%s) %s',
            $clientName,
            $clientId ?? 'N/A',
            $actionText
        );

        self::log($userId, $userType, $action, 'client', $description, ['client_id' => $clientId]);
    }

    /**
     * Log staff activity
     */
    public static function logStaff(
        $userId, // Mixed: int or string
        string $userType,
        string $action,
        string $staffName,
        ?string $staffId = null
    ) {
        $actionText = [
            'created' => 'added',
            'updated' => 'updated',
            'deleted' => 'deleted',
        ][$action] ?? $action;

        $description = sprintf(
            'Staff %s (%s) %s',
            $staffName,
            $staffId ?? 'N/A',
            $actionText
        );

        self::log($userId, $userType, $action, 'staff', $description, ['staff_id' => $staffId]);
    }

    /**
     * Log target activity
     */
    public static function logTarget(
        $userId, // Mixed: int or string
        string $userType,
        string $action,
        ?array $details = null
    ) {
        $actionText = [
            'created' => 'set',
            'updated' => 'updated',
            'deleted' => 'deleted',
        ][$action] ?? $action;

        $description = sprintf(
            'Production target %s',
            $actionText
        );

        self::log($userId, $userType, $action, 'target', $description, $details);
    }

    /**
     * Log profile activity
     */
    public static function logProfile(
        $userId, // Mixed: int or string
        string $userType,
        string $action,
        ?array $details = null
    ) {
        $description = '';

        switch ($action) {
            case 'updated':
                $changedFields = [];
                if (isset($details['first_name']) || isset($details['last_name'])) {
                    $changedFields[] = 'name';
                }
                if (isset($details['email'])) {
                    $changedFields[] = 'email';
                }
                if (isset($details['contact_number'])) {
                    $changedFields[] = 'contact number';
                }
                if (isset($details['position'])) {
                    $changedFields[] = 'position';
                }
                if (isset($details['department'])) {
                    $changedFields[] = 'department';
                }
                
                $fieldsText = !empty($changedFields) ? implode(', ', $changedFields) : 'profile';
                $description = sprintf('Updated %s', $fieldsText);
                break;

            case 'password_changed':
                $description = 'Changed account password';
                break;

            default:
                $description = sprintf('Profile %s', $action);
        }

        self::log($userId, $userType, $action, 'profile', $description, $details);
    }

    /**
     * Log login activity
     */
    public static function logLogin($userId, string $userType, string $userName) // Mixed: int or string
    {
        self::log(
            $userId,
            $userType,
            'login',
            'auth',
            sprintf('%s (%s) logged in', $userName, ucfirst($userType)),
            ['user_name' => $userName]
        );
    }
}
