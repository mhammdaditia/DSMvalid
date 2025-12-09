<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ========================================
        // ✅ VALIDASI MAX DATE RANGE (30 HARI)
        // ========================================
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'shift_start_time' => 'nullable|integer|min:0|max:23',
            'shift_end_time' => 'nullable|integer|min:0|max:23',
        ]);

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->input('start_date'))
            : Carbon::yesterday();
            
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->input('end_date'))
            : Carbon::today();

        // ✅ Limit maksimal 30 hari
        if ($startDate->diffInDays($endDate) > 30) {
            return back()->withErrors([
                'date_range' => 'Rentang tanggal maksimal 30 hari. Silakan pilih periode yang lebih pendek.'
            ]);
        }

        // ========================================
        // ✅ CACHING LOGIC - CACHE DATA, BUKAN RESPONSE
        // ========================================
        $hasCustomFilter = $request->hasAny(['start_date', 'end_date', 'shift_start_time', 'shift_end_time']);
        
        $dashboardData = null;
        
        if (!$hasCustomFilter) {
            // ✅ SOLUSI: Cache DATA saja (array), bukan Inertia response
            $cacheKey = 'dashboard_data_v3';
            $cacheDuration = 300; // 5 menit
            
            $dashboardData = Cache::remember($cacheKey, $cacheDuration, function() use ($request) {
                return $this->getDashboardData($request);
            });
        } else {
            // Jika ada custom filter, langsung generate tanpa cache
            $dashboardData = $this->getDashboardData($request);
        }
        
        // ✅ Return Inertia response (tidak di-cache)
        return Inertia::render('Dashboard/Index', $dashboardData);
    }

    /**
     * ✅ PRIVATE METHOD: Get Dashboard Data (Pure Array - Bisa di-cache)
     */
    private function getDashboardData(Request $request): array
    {
        // ========================================
        // DATE & SHIFT SETUP
        // ========================================
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->input('start_date'))
            : Carbon::yesterday();
            
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->input('end_date'))
            : Carbon::today();

        $shiftStartHour = (int) $request->input('shift_start_time', 6);
        $shiftEndHour   = (int) $request->input('shift_end_time', 18);
        
        $shiftStart = $startDate->copy()->setTime($shiftStartHour, 0, 0);
        $shiftEnd = ($shiftStartHour > $shiftEndHour) 
            ? $endDate->copy()->addDay()->setTime($shiftEndHour, 0, 0)
            : $endDate->copy()->setTime($shiftEndHour, 0, 0);

        $shiftStartSql = $shiftStart->format('Y-m-d H:i:s');
        $shiftEndSql = $shiftEnd->format('Y-m-d H:i:s');
        
        $isNightShift = $shiftStartHour > $shiftEndHour;

        // ========================================
        // ✅ MAIN STATISTICS - Single Source of Truth
        // ========================================
        if ($isNightShift) {
            // Night shift dengan hour filter
            $mainStats = DB::selectOne("
                WITH EventsWithLatestValidation AS (
                    SELECT 
                        e.Alarm_id,
                        e.Alarm,
                        e.No_Unit,
                        e.Tanggal,
                        v.status,
                        v.created_at as validation_date,
                        ROW_NUMBER() OVER (PARTITION BY e.Alarm_id ORDER BY COALESCE(v.created_at, '1900-01-01') DESC) as rn
                    FROM tbl_t_deviasi_dsm e
                    LEFT JOIN validations v ON e.Alarm_id = v.alarm_id
                    WHERE e.Tanggal >= ? 
                      AND e.Tanggal < ?
                      AND (DATEPART(HOUR, e.Tanggal) >= ? OR DATEPART(HOUR, e.Tanggal) < ?)
                )
                SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN status IS NOT NULL THEN 1 ELSE 0 END) as events_with_validation,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as true_count,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as false_count
                FROM EventsWithLatestValidation
                WHERE rn = 1
            ", [$shiftStartSql, $shiftEndSql, $shiftStartHour, $shiftEndHour]);
        } else {
            // Day shift dengan hour filter
            $mainStats = DB::selectOne("
                WITH EventsWithLatestValidation AS (
                    SELECT 
                        e.Alarm_id,
                        e.Alarm,
                        e.No_Unit,
                        e.Tanggal,
                        v.status,
                        v.created_at as validation_date,
                        ROW_NUMBER() OVER (PARTITION BY e.Alarm_id ORDER BY COALESCE(v.created_at, '1900-01-01') DESC) as rn
                    FROM tbl_t_deviasi_dsm e
                    LEFT JOIN validations v ON e.Alarm_id = v.alarm_id
                    WHERE e.Tanggal >= ? 
                      AND e.Tanggal < ?
                      AND DATEPART(HOUR, e.Tanggal) >= ?
                      AND DATEPART(HOUR, e.Tanggal) < ?
                )
                SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN status IS NOT NULL THEN 1 ELSE 0 END) as events_with_validation,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as true_count,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as false_count
                FROM EventsWithLatestValidation
                WHERE rn = 1
            ", [$shiftStartSql, $shiftEndSql, $shiftStartHour, $shiftEndHour]);
        }

        // ✅ Parse hasil query
        $totalEvents = (int) ($mainStats->total_events ?? 0);
        $eventsWithValidation = (int) ($mainStats->events_with_validation ?? 0);
        $trueCount = (int) ($mainStats->true_count ?? 0);
        $falseCount = (int) ($mainStats->false_count ?? 0);
        
        // ✅ Hitung pending dari selisih
        $pendingCount = $totalEvents - $eventsWithValidation;
        
        // ✅ Total validations = events yang sudah divalidasi
        $totalValidations = $eventsWithValidation;
        
        // ✅ Validation rate
        $validationRate = $totalEvents > 0 
            ? round(($eventsWithValidation / $totalEvents) * 100, 1) 
            : 0;

        // ========================================
        // ✅ ALARM STATISTICS (TOP 7 LIMIT)
        // ========================================
        if ($isNightShift) {
            $alarmStats = DB::select("
                SELECT Alarm, COUNT(*) as total
                FROM tbl_t_deviasi_dsm
                WHERE Tanggal >= ? 
                  AND Tanggal < ?
                  AND (DATEPART(HOUR, Tanggal) >= ? OR DATEPART(HOUR, Tanggal) < ?)
                GROUP BY Alarm
                ORDER BY total DESC
                OFFSET 0 ROWS FETCH NEXT 7 ROWS ONLY
            ", [$shiftStartSql, $shiftEndSql, $shiftStartHour, $shiftEndHour]);
        } else {
            $alarmStats = DB::select("
                SELECT Alarm, COUNT(*) as total
                FROM tbl_t_deviasi_dsm
                WHERE Tanggal >= ? 
                  AND Tanggal < ?
                  AND DATEPART(HOUR, Tanggal) >= ?
                  AND DATEPART(HOUR, Tanggal) < ?
                GROUP BY Alarm
                ORDER BY total DESC
                OFFSET 0 ROWS FETCH NEXT 7 ROWS ONLY
            ", [$shiftStartSql, $shiftEndSql, $shiftStartHour, $shiftEndHour]);
        }

        // ========================================
        // ✅ ALARM BY HOUR (dengan hour filter)
        // ========================================
        if ($isNightShift) {
            $alarmByHourRaw = DB::select("
                SELECT 
                    DATEPART(HOUR, Tanggal) as hour,
                    COUNT(*) as count
                FROM tbl_t_deviasi_dsm
                WHERE Tanggal >= ? 
                  AND Tanggal < ?
                  AND (DATEPART(HOUR, Tanggal) >= ? OR DATEPART(HOUR, Tanggal) < ?)
                GROUP BY DATEPART(HOUR, Tanggal)
            ", [$shiftStartSql, $shiftEndSql, $shiftStartHour, $shiftEndHour]);
        } else {
            $alarmByHourRaw = DB::select("
                SELECT 
                    DATEPART(HOUR, Tanggal) as hour,
                    COUNT(*) as count
                FROM tbl_t_deviasi_dsm
                WHERE Tanggal >= ? 
                  AND Tanggal < ?
                  AND DATEPART(HOUR, Tanggal) >= ?
                  AND DATEPART(HOUR, Tanggal) < ?
                GROUP BY DATEPART(HOUR, Tanggal)
            ", [$shiftStartSql, $shiftEndSql, $shiftStartHour, $shiftEndHour]);
        }

        $alarmByHourMap = collect($alarmByHourRaw)->keyBy('hour');
        $alarmByHour = $this->buildHourlyData($alarmByHourMap, $shiftStartHour, $shiftEndHour);

        // ========================================
        // ✅ ALARM BY UNIT (TOP 10, dengan hour filter)
        // ========================================
        if ($isNightShift) {
            $alarmByUnit = DB::select("
                SELECT No_Unit, COUNT(*) as count
                FROM tbl_t_deviasi_dsm
                WHERE Tanggal >= ? 
                  AND Tanggal < ?
                  AND (DATEPART(HOUR, Tanggal) >= ? OR DATEPART(HOUR, Tanggal) < ?)
                GROUP BY No_Unit
                ORDER BY count DESC
                OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY
            ", [$shiftStartSql, $shiftEndSql, $shiftStartHour, $shiftEndHour]);
        } else {
            $alarmByUnit = DB::select("
                SELECT No_Unit, COUNT(*) as count
                FROM tbl_t_deviasi_dsm
                WHERE Tanggal >= ? 
                  AND Tanggal < ?
                  AND DATEPART(HOUR, Tanggal) >= ?
                  AND DATEPART(HOUR, Tanggal) < ?
                GROUP BY No_Unit
                ORDER BY count DESC
                OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY
            ", [$shiftStartSql, $shiftEndSql, $shiftStartHour, $shiftEndHour]);
        }

        // ========================================
        // ✅ RETURN PURE ARRAY (BISA DI-SERIALIZE)
        // ========================================
        return [
            'stats' => [
                'total_events'        => $totalEvents,
                'total_validations'   => $totalValidations,
                'pending_validations' => $pendingCount,
                'validation_rate'     => $validationRate,
            ],
            'validationStatusDistribution' => [
                'validated' => $eventsWithValidation,
                'pending'   => $pendingCount,
            ],
            'statusDistribution' => [
                'true'  => $trueCount,
                'false' => $falseCount,
            ],
            'alarmStats' => collect($alarmStats)->map(fn($item) => [
                'alarm' => $item->Alarm,
                'total' => (int) $item->total,
            ])->toArray(), // ✅ toArray() untuk serialization
            'alarmByHour' => $alarmByHour,
            'alarmByUnit' => collect($alarmByUnit)->map(fn($item) => [
                'No_unit' => $item->No_Unit,
                'count'   => (int) $item->count,
            ])->toArray(), // ✅ toArray() untuk serialization
            'filters' => [
                'start_date'       => $startDate->format('Y-m-d'),
                'end_date'         => $endDate->format('Y-m-d'),
                'shift_start_time' => $shiftStartHour,
                'shift_end_time'   => $shiftEndHour,
                'shift_start'      => $shiftStart->format('Y-m-d H:i'),
                'shift_end'        => $shiftEnd->format('Y-m-d H:i'),
                'is_night_shift'   => $isNightShift,
            ],
        ];
    }

    private function buildHourlyData($alarmByHourMap, $shiftStartHour, $shiftEndHour)
    {
        $alarmByHour = [];

        if ($shiftStartHour > $shiftEndHour) {
            // Night shift: 18-23, 0-5
            for ($h = $shiftStartHour; $h < 24; $h++) {
                $alarmByHour[] = [
                    'hour' => $h, 
                    'count' => isset($alarmByHourMap[$h]) ? (int)$alarmByHourMap[$h]->count : 0
                ];
            }
            for ($h = 0; $h < $shiftEndHour; $h++) {
                $alarmByHour[] = [
                    'hour' => $h, 
                    'count' => isset($alarmByHourMap[$h]) ? (int)$alarmByHourMap[$h]->count : 0
                ];
            }
        } else {
            // Day shift: 6-17 (exclude jam 18)
            for ($h = $shiftStartHour; $h < $shiftEndHour; $h++) {
                $alarmByHour[] = [
                    'hour' => $h, 
                    'count' => isset($alarmByHourMap[$h]) ? (int)$alarmByHourMap[$h]->count : 0
                ];
            }
        }

        return $alarmByHour;
    }
    
    /**
     * ✅ Clear Cache Manual (untuk admin)
     */
    public function clearCache()
    {
        Cache::forget('dashboard_data_v3');
        
        return back()->with('success', 'Dashboard cache berhasil dibersihkan');
    }
}