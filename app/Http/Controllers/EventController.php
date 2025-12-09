<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Validation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index(Request $request)
    {
        // ? STEP 1: Filter by date range FIRST (untuk optimasi)
        $dateRange = $request->get('date_range', 'last_2_days');
        
        // ✅ FIX: Handle 'all' option
        $startDate = match($dateRange) {
            'last_7_days' => Carbon::now()->subDays(7)->startOfDay(),
            'last_30_days' => Carbon::now()->subDays(30)->startOfDay(),
            'all' => null, // ✅ NULL untuk semua data
            default => Carbon::now()->subDays(2)->startOfDay(), // last_2_days
        };

        // ✅ SOLUSI: Gunakan LEFT JOIN + IS NULL (Menghindari 2100 Parameter Limit)
        $query = DB::table('tbl_t_deviasi_dsm as e')
            ->leftJoin('validations as v', 'e.Alarm_id', '=', 'v.alarm_id')
            ->whereNull('v.alarm_id'); // Event yang BELUM divalidasi
        
        // ✅ Apply date filter hanya jika bukan 'all'
        if ($startDate !== null) {
            $query->where('e.Tanggal', '>=', $startDate);
        }

        // ? STEP 2: Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('e.No_Unit', 'like', "%{$search}%")
                  ->orWhere('e.Alarm', 'like', "%{$search}%")
                  ->orWhere('e.Location', 'like', "%{$search}%")
                  ->orWhere('e.Alarm_id', 'like', "%{$search}%");
            });
        }

        // ? STEP 3: Filter by alarm type
        if ($request->filled('alarm_type')) {
            $query->where('e.Alarm', $request->alarm_type);
        }

        // Select semua kolom dari tbl_t_deviasi_dsm
        $events = $query->select('e.*')
            ->orderByDesc('e.Tanggal')
            ->paginate(20)
            ->withQueryString();

        // ? STEP 4: Get alarm types (dengan LEFT JOIN yang sama)
        $alarmTypesQuery = DB::table('tbl_t_deviasi_dsm as e')
            ->leftJoin('validations as v', 'e.Alarm_id', '=', 'v.alarm_id')
            ->whereNull('v.alarm_id');
        
        // ✅ Apply date filter untuk alarm types juga
        if ($startDate !== null) {
            $alarmTypesQuery->where('e.Tanggal', '>=', $startDate);
        }
        
        $alarmTypes = $alarmTypesQuery->distinct()
            ->pluck('e.Alarm')
            ->toArray();

        // ✅ OPTIMASI: Hanya ambil validated IDs untuk date range yang sedang difilter
        $validatedIdsQuery = Validation::query();
        
        if ($startDate !== null) {
            $validatedIdsQuery->where('tanggal', '>=', $startDate);
        }
        
        $validatedIds = $validatedIdsQuery->pluck('alarm_id')->toArray();

        // Validate event modal
        $validateEvent = null;
        if ($request->filled('validate')) {
            $validateEvent = Event::find($request->validate);
        }

        return Inertia::render('Events/Index', [
            'events' => $events,
            'alarmTypes' => $alarmTypes,
            'filters' => [
                'search' => $request->search,
                'alarm_type' => $request->alarm_type,
                'date_range' => $dateRange,
            ],
            'validatedIds' => $validatedIds,
            'validateEvent' => $validateEvent,
        ]);
    }

    public function storeValidation(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        // ✅ Check existing validation
        $existingValidation = Validation::where('alarm_id', $event->Alarm_id)
            ->where('no_unit', $event->No_Unit)
            ->where('tanggal', $event->Tanggal)
            ->first();
            
        if ($existingValidation) {
            return redirect()->route('events.index')
                ->with('error', 'Event ini sudah divalidasi sebelumnya');
        }

        // ✅ FILE WAJIB HANYA KALAU STATUS TRUE
        $validated = $request->validate([
            'status' => 'required|in:true,false',
            'follow_up' => 'nullable|required_if:status,true|string|max:500',
            'follow_up_file' => 'required_if:status,true|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
            'keterangan' => 'required|string|max:1000',
        ], [
            'status.required' => 'Status validasi harus dipilih',
            'follow_up.required_if' => 'Follow up wajib diisi jika status True',
            'follow_up_file.required_if' => 'File bukti wajib diupload jika status True',
            'follow_up_file.mimes' => 'File harus berformat: PDF, JPG, JPEG, PNG, DOC, atau DOCX',
            'follow_up_file.max' => 'Ukuran file maksimal 20MB',
            'keterangan.required' => 'Keterangan wajib diisi',
        ]);

        $status = $validated['status'] === 'true' ? 1 : 0;

        // Handle file upload (HANYA KALAU ADA)
        $filePath = null;
        if ($request->hasFile('follow_up_file')) {
            try {
                $file = $request->file('follow_up_file');
                $fileContent = file_get_contents($file->getRealPath());
                
                $tanggalObj = Carbon::parse($event->Tanggal);
                $dateFolder = $tanggalObj->format('Ymd');
                $unitFolder = $event->No_Unit;
                $folderPath = 'E:\\dsm_file\\' . $dateFolder . '\\' . $unitFolder;
                
                if (!file_exists($folderPath)) {
                    mkdir($folderPath, 0777, true);
                }
                
                $timestamp = now()->format('His');
                $extension = $file->getClientOriginalExtension();
                
                $customFileName = sprintf(
                    '%s_%s_%s.%s',
                    $dateFolder,
                    $unitFolder,
                    $timestamp,
                    $extension
                );
                
                $fullPath = $folderPath . '\\' . $customFileName;
                file_put_contents($fullPath, $fileContent);
                
                $filePath = $dateFolder . '/' . $unitFolder . '/' . $customFileName;
                
                \Log::info('✅ File saved successfully', [
                    'path' => $fullPath,
                    'size' => strlen($fileContent)
                ]);
                
            } catch (\Exception $e) {
                \Log::error('❌ File upload error: ' . $e->getMessage());
                
                return back()->withErrors([
                    'follow_up_file' => 'Gagal upload file: ' . $e->getMessage()
                ])->withInput();
            }
        }

        // Simpan validasi
        Validation::create([
            'alarm_id' => $event->Alarm_id,
            'no_unit' => $event->No_Unit,
            'alarm' => $event->Alarm,
            'tanggal' => $event->Tanggal,
            'location' => $event->Location,
            'speed' => $event->Speed,
            'status' => $status,
            'follow_up' => $status ? $validated['follow_up'] : null,
            'follow_up_file' => $filePath,
            'keterangan' => $validated['keterangan'],
            'validated_by' => auth()->id(),
        ]);

        try {
            broadcast(new \App\Events\NewEventCreated($event))->toOthers();
        } catch (\Exception $e) {
            \Log::error('❌ Error broadcasting: ' . $e->getMessage());
        }

        return redirect()->route('events.index')
            ->with('success', '✅ Validasi berhasil disimpan!');
    }
}