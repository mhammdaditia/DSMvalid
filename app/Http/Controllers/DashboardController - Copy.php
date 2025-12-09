<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Event;
use App\Models\Validation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ========================================
        // DATE RANGE FILTER - DEFAULT: Yesterday to Today
        // ========================================
        $startDate = null;
        $endDate = null;
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        } else {
            // DEFAULT: From yesterday to today
            $startDate = Carbon::yesterday()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        }

        // ========================================
        // SHIFT TIME FILTER - DEFAULT: 6:00 - 18:00
        // ========================================
        $shiftStartTime = $request->input('shift_start_time', 6);
        $shiftEndTime = $request->input('shift_end_time', 18);

        // ========================================
        // BUILD BASE QUERY FOR EVENTS
        // ========================================
        $baseEventsQuery = Event::query();
        
        // Apply date filter
        if ($startDate && $endDate) {
            $baseEventsQuery->whereRaw('CAST(Tanggal AS DATE) BETWEEN ? AND ?', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
        }
        
        // ✅ PERBAIKAN: Apply shift time filter dengan < bukan <=
        if ($shiftStartTime !== null && $shiftEndTime !== null) {
            $startHour = (int) $shiftStartTime;
            $endHour = (int) $shiftEndTime;
            
            if ($startHour > $endHour) {
                // Night shift crossing midnight (e.g., 18 - 6)
                // Contoh: 18:00 - 05:59 (jam 6 tidak termasuk)
                $baseEventsQuery->where(function($q) use ($startHour, $endHour) {
                    $q->whereRaw("DATEPART(HOUR, Tanggal) >= ?", [$startHour])
                      ->orWhereRaw("DATEPART(HOUR, Tanggal) < ?", [$endHour]); // ✅ UBAH <= jadi <
                });
            } else {
                // Normal shift (e.g., 6 - 18)
                // Contoh: 06:00 - 17:59 (jam 18 tidak termasuk)
                $baseEventsQuery->whereRaw("DATEPART(HOUR, Tanggal) >= ?", [$startHour])
                               ->whereRaw("DATEPART(HOUR, Tanggal) < ?", [$endHour]); // ✅ UBAH <= jadi <
            }
        }

        // ========================================
        // BUILD BASE QUERY FOR VALIDATIONS
        // ========================================
        $baseValidationsQuery = Validation::query();
        
        if ($startDate && $endDate) {
            $baseValidationsQuery->whereRaw('CAST(created_at AS DATE) BETWEEN ? AND ?', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
        }
        
        // ✅ PERBAIKAN: Apply shift time filter dengan < bukan <=
        if ($shiftStartTime !== null && $shiftEndTime !== null) {
            $startHour = (int) $shiftStartTime;
            $endHour = (int) $shiftEndTime;
            
            if ($startHour > $endHour) {
                $baseValidationsQuery->where(function($q) use ($startHour, $endHour) {
                    $q->whereRaw("DATEPART(HOUR, created_at) >= ?", [$startHour])
                      ->orWhereRaw("DATEPART(HOUR, created_at) < ?", [$endHour]); // ✅ UBAH <= jadi <
                });
            } else {
                $baseValidationsQuery->whereRaw("DATEPART(HOUR, created_at) >= ?", [$startHour])
                                    ->whereRaw("DATEPART(HOUR, created_at) < ?", [$endHour]); // ✅ UBAH <= jadi <
            }
        }

        // ========================================
        // 1. ALARM STATISTICS
        // ========================================
        $alarmStats = (clone $baseEventsQuery)
            ->select('Alarm', DB::raw('count(*) as total'))
            ->groupBy('Alarm')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'alarm' => $item->Alarm,
                    'total' => $item->total,
                ];
            });

        // ========================================
        // 2. VALIDATION STATUS DISTRIBUTION (FIXED)
        // ========================================
        $totalEvents = (clone $baseEventsQuery)->count();
        
        $validatedCount = (clone $baseEventsQuery)
            ->whereExists(function ($query) {
                $query->selectRaw(1)
                    ->from('validations')
                    ->whereColumn('validations.alarm_id', 'tbl_t_deviasi_dsm.Alarm_id');
            })
            ->count();

        $pendingCount = $totalEvents - $validatedCount;

        $validationStatusDistribution = [
            'validated' => $validatedCount,
            'pending' => $pendingCount,
        ];

        // ========================================
        // 3. STATUS DISTRIBUTION (True/False)
        // ========================================
        $trueCount = (clone $baseValidationsQuery)->where('status', 1)->count();
        $falseCount = (clone $baseValidationsQuery)->where('status', 0)->count();

        $statusDistribution = [
            'true' => $trueCount,
            'false' => $falseCount,
        ];

        // ========================================
        // 4. ALARM BY HOUR (SESUAI SHIFT FILTER)
        // ========================================
        $alarmByHourRaw = (clone $baseEventsQuery)
            ->select(DB::raw('DATEPART(HOUR, Tanggal) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATEPART(HOUR, Tanggal)'))
            ->get()
            ->keyBy('hour');

        // Determine hour range based on shift
        $startHour = $shiftStartTime !== null ? (int)$shiftStartTime : 0;
        $endHour = $shiftEndTime !== null ? (int)$shiftEndTime : 23;

        // ✅ PERBAIKAN: Fill hours dengan range yang benar
        $alarmByHour = [];

        if ($startHour > $endHour) {
            // Night shift crossing midnight (e.g., 18 → 6)
            // Jam 18, 19, 20, 21, 22, 23, 0, 1, 2, 3, 4, 5
            for ($hour = $startHour; $hour < 24; $hour++) {
                $alarmByHour[] = [
                    'hour' => $hour,
                    'count' => isset($alarmByHourRaw[$hour]) ? (int)$alarmByHourRaw[$hour]->count : 0,
                ];
            }
            for ($hour = 0; $hour < $endHour; $hour++) { // ✅ UBAH <= jadi <
                $alarmByHour[] = [
                    'hour' => $hour,
                    'count' => isset($alarmByHourRaw[$hour]) ? (int)$alarmByHourRaw[$hour]->count : 0,
                ];
            }
        } else {
            // Normal shift (e.g., 6 → 18)
            // Jam 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17
            for ($hour = $startHour; $hour < $endHour; $hour++) { // ✅ UBAH <= jadi <
                $alarmByHour[] = [
                    'hour' => $hour,
                    'count' => isset($alarmByHourRaw[$hour]) ? (int)$alarmByHourRaw[$hour]->count : 0,
                ];
            }
        }

        // ========================================
        // 5. ALARM BY UNIT (TOP 10)
        // ========================================
        $alarmByUnit = (clone $baseEventsQuery)
            ->select('No_Unit', DB::raw('COUNT(*) as count'))
            ->groupBy('No_Unit')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'No_unit' => $item->No_Unit,
                    'count' => $item->count,
                ];
            });

        // ========================================
        // 6. SUMMARY STATS
        // ========================================
        $stats = [
            'total_events' => $totalEvents,
            'total_validations' => $validatedCount,
            'pending_validations' => $pendingCount,
            'validation_rate' => $totalEvents > 0 ? round(($validatedCount / $totalEvents) * 100, 1) : 0,
        ];

        // ========================================
        // RETURN TO FRONTEND
        // ========================================
        return Inertia::render('Dashboard/Index', [
            'alarmStats' => $alarmStats,
            'validationStatusDistribution' => $validationStatusDistribution,
            'statusDistribution' => $statusDistribution,
            'alarmByHour' => $alarmByHour,
            'alarmByUnit' => $alarmByUnit,
            'stats' => $stats,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'shift_start_time' => $shiftStartTime,
                'shift_end_time' => $shiftEndTime,
            ],
        ]);
    }
}