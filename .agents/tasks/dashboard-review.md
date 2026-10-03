# Dashboard unified data pipeline and UX cleanup

Consolidated the dashboard's data fetching into a single backend endpoint that uses production_history as the source of truth for metrics and charts. Removed date range filtering from the dashboard UI and added trend indicators to metric cards. The dashboard now always shows current month data by default, with backend APIs supporting optional date ranges for distribution endpoints.

**Watch for:** Production chart filtering gap (**confirmed**) — stage update filtering for "Ready" seedlings in production chart queries only the `new_stage` column without checking `action_type = 'stage_update'`, which could miscount transferred seedlings that happen to have new_stage set. CardMetrics trend logic asymmetry (**confirmed**) — distributed metric uses requested_date for filtering, total seedlings uses changed_at with no date boundaries, creating inconsistent comparison windows. Inconsistent date column fallback (**confirmed**) — RequestController and TargetController APIs check requested_date first with updated_at fallback, but DashboardController only uses requested_date with no fallback, so requests without requested_date won't be counted in dashboard metrics.

**Verdict**: NEEDS_CHANGES

## High-level view

The DashboardController consolidates six separate API calls into one endpoint that sources all production and inventory metrics from production_history rather than the productions table. This shift is architecturally sound: production_history is append-only and preserves quantities even after records are transferred or deleted, while the productions table only holds current state.

The frontend date range filter was removed from Dashboard.jsx entirely. No dateRange state, no filter UI, no props passed to children. CardMetrics, ProductionReadyChart, and ActualVsTargetChart now call a single getDashboardData service method with no parameters.

ActualVsTargetChart replaced the line chart with a bar chart that mirrors ProductionReadyChart's visual style. Both charts now have consistent empty states and loading indicators.

CardMetrics gained trend indicators comparing current month to previous month. The trend calculation is percentage-based and color-coded: green for up, red for down. Only total seedlings and distributed metrics show trends; available stock and pending requests do not.

The DistributionTargetProgress component in the distribution section was not modified or removed, as required. It remains untouched.

Three date filtering issues remain: the production chart's stage update query doesn't verify action_type when filtering by stage, the metrics use different time boundaries for different aggregations (changed_at for seedlings, requested_date for distributions), and the dashboard controller doesn't fall back to updated_at when requested_date is null while other controllers do.

<details>
<summary>Issues (3)</summary>

1. **Production chart filtering gap** — stage_update aggregation in production chart queries `new_stage = "Ready"` without checking `action_type = 'stage_update'`, which risks double-counting seedlings that were transferred with new_stage inadvertently set. Add `AND action_type = 'stage_update'` to the WHERE clause in DashboardController.php line 151.

2. **CardMetrics trend logic asymmetry** — total_seedlings metric uses changed_at with year-only filtering (all months), while distributed metric uses requested_date with month-specific filtering. Trend percentages compare mismatched windows: current month distributed vs current month previous, but year-to-date seedlings vs previous-month-only seedlings. Align both to use the same time boundary: whereBetween with month start/end dates.

3. **Inconsistent date column fallback** — DashboardController queries only check requested_date for Released requests, but RequestController and TargetController fall back to updated_at when requested_date is null. Requests released before requested_date column was populated won't appear in dashboard metrics but will appear in the distribution card and target progress. Add the same fallback logic to DashboardController lines 60-62 and 163-165.

</details>

<details>
<summary>Details</summary>

## Production_history as metric source

DashboardController queries production_history for all seedling counts. Total seedlings aggregates `new_quantity` where `action_type IN ('created', 'transferred')`. The production chart sums `new_quantity` where `action_type = 'created'` for sown, and `new_stage = 'Ready'` for ready seedlings, grouped by month.

The ready seedling filter has a gap. Line 151 reads:

```sql
SUM(CASE WHEN new_stage = "Ready" THEN new_quantity ELSE 0 END) as ready
```

It's missing the `action_type = "stage_update"` check. If a transferred or edited record happens to have `new_stage = "Ready"`, it will be counted as a ready seedling in the chart, even though the action wasn't a stage update. The production_history schema allows new_stage to be set on any action_type, so this filter is too broad.

## Trend indicators and time boundary inconsistency

The total seedlings trend compares:
- Current: `whereYear('changed_at', $currentYear)` — all months of the current year
- Previous: `whereBetween('changed_at', [$prevMonthStart, $prevMonthEnd])` — only last month

Line 54 queries the full year, line 58 queries one month. The percentage change compares year-to-date production to last-month production, which produces meaningless trend numbers. A 5% increase here could mean production is flat (because 12 months vs 1 month) or wildly up (if current month alone is 5% higher than the entire previous month).

This should use `whereBetween('changed_at', [$currentMonthStart, $currentMonthEnd])` for the current metric to match the previous month window.

## Date column fallback asymmetry

DashboardController queries Released requests at lines 60-62 and 163-165 using only `requested_date`:

```php
$distributed = Request::where('status', 'Released')
    ->whereBetween('requested_date', [$currentMonthStart, $currentMonthEnd])
    ->sum('quantity');
```

RequestController's getMonthlySales method at line 481 and TargetController's getMonthlyTargetVsActual at line 320 both use a fallback:

```php
$q->where(function($subQ) use ($startDate, $endDate) {
    $subQ->whereBetween('requested_date', [$startDate, $endDate]);
})->orWhere(function($subQ) use ($startDate, $endDate) {
    $subQ->whereNull('requested_date')
         ->whereBetween('updated_at', [$startDate, $endDate]);
});
```

If a request was released before the requested_date column was added or backfilled, it will have `requested_date = NULL` and will be excluded from dashboard metrics but included in the distribution card and target progress card. The data will be inconsistent across the UI.

The DashboardController should use the same fallback pattern for both distributed calculations.

## Date range removal from frontend

Dashboard.jsx no longer imports or uses any date picker components. No dateRange state variable, no filter UI, no props passed to children. CardMetrics, ProductionReadyChart, and ActualVsTargetChart call a single getDashboardData service method with no parameters.

## ActualVsTargetChart consistency with ProductionReadyChart

ActualVsTargetChart replaced the line chart with a bar chart matching ProductionReadyChart's structure: CartesianGrid, XAxis, YAxis, Bar components, ChartTooltip, ChartLegend. Both use the same loading and empty state patterns and chartConfig color convention.

The XAxis in ActualVsTargetChart rotates labels -45 degrees with textAnchor="end" and height={80} to prevent overlap. ProductionReadyChart doesn't need this because month abbreviations are short.

## DistributionTargetProgress preservation

The file `c:\Users\User\Desktop\seedMIS\src\pages\distribution\layout\DistributionTargetProgress.jsx` was not modified. Git status shows no changes to this file.

## Backend route and inventory status changes

The diff shows a new route added to routes/api.php at line 66:

```php
Route::get('dashboard', [DashboardController::class, 'getDashboardData']);
```

The diff also adds a `status` column to the inventories table validation and model fillable array. InventoryController now validates `status` as `nullable|in:Available,Not Available` on store and update. ProductionController sets `status => 'Available'` when transferring production to inventory. This change is unrelated to the dashboard review but doesn't conflict with it.

</details>

<details>
<summary>File map</summary>

**Backend**

- `backend/app/Http/Controllers/Api/DashboardController.php` — new controller consolidating all dashboard data queries; sources metrics from production_history and inventories
- `backend/app/Http/Controllers/Api/RequestController.php` — added getMonthlySales date range filtering with requested_date/updated_at fallback logic
- `backend/app/Http/Controllers/Api/TargetController.php` — added getMonthlyTargetVsActual date range filtering with requested_date/updated_at fallback logic
- `backend/app/Http/Controllers/Api/InventoryController.php` — added status field validation
- `backend/app/Http/Controllers/Api/ProductionController.php` — set status to 'Available' on production-to-inventory transfer
- `backend/app/Models/Inventory.php` — added status to fillable array
- `backend/routes/api.php` — added GET /dashboard route

**Frontend**

- `src/services/dashboardService.js` — single method calling /dashboard endpoint with no parameters
- `src/pages/dashboard/Dashoard.jsx` — removed date range state and filter UI, added TargetProgress and SeedlingTypeProgress components
- `src/pages/dashboard/layout/CardMetrics.jsx` — refactored to call dashboardService.getDashboardData; added trend indicator rendering
- `src/pages/dashboard/layout/ActualVsTargetChart.jsx` — replaced line chart with bar chart matching ProductionReadyChart style
- `src/pages/dashboard/layout/ProductionReadyChart.jsx` — no changes (context reference for chart consistency)
- `src/pages/dashboard/layout/TargetProgress.jsx` — new component showing annual production, monthly distribution, and revenue target progress
- `src/pages/dashboard/layout/SeedlingTypeProgress.jsx` — new component showing per-seedling-type target progress

Full diff: `git diff main` in c:\xampp\htdocs\SeedMIS-Server

</details>
