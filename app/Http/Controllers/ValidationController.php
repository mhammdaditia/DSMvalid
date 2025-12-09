<?php

namespace App\Http\Controllers;

use App\Models\Validation;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class ValidationController extends Controller
{
    public function index(Request $request)
{
    // ✅ EAGER LOAD validator relationship
    $query = Validation::with('validator');

    // Search
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('no_unit', 'like', "%{$search}%")
              ->orWhere('alarm', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%")
              ->orWhere('keterangan', 'like', "%{$search}%")
              ->orWhere('follow_up', 'like', "%{$search}%");
        });
    }

    // Filter by Status
    if ($request->has('status') && $request->status !== '' && $request->status !== null) {
        $query->where('status', (int)$request->status);
    }

    $validations = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

    // ✅ Enrich validation data with video info
    $validations->getCollection()->transform(function ($validation) {
        $event = Event::where('Alarm_id', $validation->alarm_id)
            ->where('No_Unit', $validation->no_unit)
            ->where('Tanggal', $validation->tanggal)
            ->first();

        if ($event) {
            $validation->IsVideoSent = $event->IsVideoSent;
            $validation->VideoFileName = $event->VideoFileName;
            $validation->No_Unit = $event->No_Unit;
            $validation->Tanggal = $validation->tanggal;
        } else {
            $validation->IsVideoSent = false;
            $validation->VideoFileName = null;
            $validation->No_Unit = $validation->no_unit;
            $validation->Tanggal = $validation->tanggal;
        }

        return $validation;
    });

    // Statistics
    $stats = [
        'total' => Validation::count(),
        'valid' => Validation::where('status', 1)->count(),
        'invalid' => Validation::where('status', 0)->count(),
    ];

    // ✅ FIX: Check role properly (admin OR super_admin)
    $isAdmin = auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin']);

    return Inertia::render('Validations/Index', [
        'validations' => $validations,
        'filters' => $request->only(['search', 'status']),
        'stats' => $stats,
        'isAdmin' => $isAdmin, // ✅ Pass isAdmin ke frontend
    ]);
}

    public function update(Request $request, $id)
{
    if (!auth()->user()->is_admin) {
        return back()->with('error', 'Anda tidak memiliki akses untuk mengubah data validasi');
    }

    $validation = Validation::findOrFail($id);

    // ✅ FILE WAJIB saat EDIT jika status TRUE
    $validated = $request->validate([
        'status' => 'required|boolean',
        'follow_up' => 'nullable|required_if:status,true|string|max:1000',
        'follow_up_file' => 'required_if:status,true|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480', // ✅ WAJIB
        'keterangan' => 'required|string|max:1000',
    ], [
        'status.required' => 'Status validasi harus dipilih',
        'follow_up.required_if' => 'Follow up wajib diisi jika status True',
        'follow_up_file.required_if' => 'File bukti wajib diupload jika status True', // ✅ MESSAGE
        'follow_up_file.mimes' => 'File harus berformat: PDF, JPG, JPEG, PNG, DOC, atau DOCX',
        'follow_up_file.max' => 'Ukuran file maksimal 20MB',
        'keterangan.required' => 'Keterangan wajib diisi',
    ]);

    // ✅ TAPI cek dulu: kalau sudah ada file lama dan tidak upload file baru, skip validasi
    // Ini untuk backward compatibility dengan data lama
    if ($validated['status'] && !$request->hasFile('follow_up_file') && !$validation->follow_up_file) {
        return back()->withErrors([
            'follow_up_file' => 'File bukti wajib diupload jika status True'
        ])->withInput();
    }

    // Handle file upload
    if ($request->hasFile('follow_up_file')) {
        // Hapus file lama
        if ($validation->follow_up_file) {
            $oldFilePath = 'E:\\dsm_file\\' . str_replace('/', '\\', $validation->follow_up_file);
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }

        // Upload file baru
        $file = $request->file('follow_up_file');
        $fileContent = file_get_contents($file->getRealPath());
        
        $tanggalObj = Carbon::parse($validation->tanggal);
        $dateFolder = $tanggalObj->format('Ymd');
        $unitFolder = $validation->no_unit;
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
        
        $validated['follow_up_file'] = $dateFolder . '/' . $unitFolder . '/' . $customFileName;
    }

    $validation->update([
        'status' => $validated['status'] ? 1 : 0,
        'follow_up' => $validated['status'] ? $validated['follow_up'] : null,
        'follow_up_file' => $validated['follow_up_file'] ?? $validation->follow_up_file,
        'keterangan' => $validated['keterangan'],
    ]);

    return back()->with('success', 'Data validasi berhasil diupdate!');
}

    public function destroy($id)
    {
        if (!auth()->user()->is_admin) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menghapus data validasi');
        }

        $validation = Validation::findOrFail($id);
        
        // ✅ Hapus file jika ada
        if ($validation->follow_up_file) {
            $filePath = 'E:\\dsm_file\\' . str_replace('/', '\\', $validation->follow_up_file);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        $validation->delete();

        return back()->with('success', 'Data validasi berhasil dihapus!');
    }

    public function bulkDelete(Request $request)
    {
        if (!auth()->user()->is_admin) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menghapus data validasi');
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:validations,id'
        ], [
            'ids.required' => 'Pilih minimal 1 validasi untuk dihapus',
            'ids.*.exists' => 'Validasi yang dipilih tidak valid',
        ]);

        // ✅ Hapus files
        $validations = Validation::whereIn('id', $validated['ids'])->get();
        foreach ($validations as $validation) {
            if ($validation->follow_up_file) {
                $filePath = 'E:\\dsm_file\\' . str_replace('/', '\\', $validation->follow_up_file);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        $count = Validation::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', $count . ' validasi berhasil dihapus!');
    }

    /**
     * ✅ VIEW FILE dengan URL Obfuscated (Base64 encoded)
     * URL akan jadi: /DSMvalid/file/view/{encoded_string}
     */
    public function viewFile($token)
    {
        try {
            // Decode token
            $filePath = base64_decode($token);
            
            // Security: Pastikan path tidak keluar dari E:\dsm_file\
            if (strpos($filePath, '..') !== false) {
                abort(403, 'Invalid file path');
            }
            
            // Full path
            $fullPath = 'E:\\dsm_file\\' . str_replace('/', '\\', $filePath);
            
            // Check if file exists
            if (!file_exists($fullPath)) {
                abort(404, 'File tidak ditemukan');
            }
            
            // Get mime type
            $mimeType = mime_content_type($fullPath);
            $fileName = basename($fullPath);
            
            // Return file dengan inline disposition (view, bukan download)
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error viewing file: ' . $e->getMessage());
            abort(404, 'File tidak dapat diakses');
        }
    }
}