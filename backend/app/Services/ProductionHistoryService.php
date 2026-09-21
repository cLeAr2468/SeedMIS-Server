<?php

namespace App\Services;

use App\Models\ProductionHistory;
use App\Models\Production;

class ProductionHistoryService
{
    /**
     * Log production history
     */
    public static function log(Production $production, string $actionType, array $changes = [], ?string $notes = null, ?string $changedBy = null)
    {
        $data = [
            'production_id' => $production->id,
            'batch_id' => $production->batch_id,
            'seedling_type' => $production->seedling_type,
            'classification' => $production->classification,
            'action_type' => $actionType,
            'previous_stage' => $changes['previous_stage'] ?? null,
            'new_stage' => $changes['new_stage'] ?? null,
            'previous_quantity' => $changes['previous_quantity'] ?? null,
            'new_quantity' => $changes['new_quantity'] ?? null,
            'changed_by' => $changedBy ?? auth()->user()->name ?? 'System',
            'notes' => $notes,
            'changed_at' => now(),
        ];

        // Store additional metadata if provided
        if (!empty($changes['metadata'])) {
            $data['metadata'] = $changes['metadata'];
        }

        return ProductionHistory::create($data);
    }

    /**
     * Get all production history ordered by most recent
     */
    public static function getAllHistory()
    {
        return ProductionHistory::orderBy('changed_at', 'desc')->get();
    }

    /**
     * Get history for a specific production batch
     */
    public static function getProductionHistory($productionId)
    {
        return ProductionHistory::where('production_id', $productionId)
            ->orderBy('changed_at', 'desc')
            ->get();
    }
}
