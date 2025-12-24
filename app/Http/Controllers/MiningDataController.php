<?php

namespace App\Http\Controllers;

use App\Models\ExcelUpload;
use App\Models\MiningData;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MiningDataImport;

class MiningDataController extends Controller
{
    /**
     * Upload Excel dengan Anti-Duplikasi
     * Logika: Jika filename sama dari user yang sama, HAPUS data lama dan INSERT data baru
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $userId = auth()->id();

        DB::beginTransaction();
        try {
            // ANTI-DUPLIKASI: Cek apakah file dengan nama sama sudah pernah diupload oleh user ini
            $existingUpload = ExcelUpload::where('user_id', $userId)
                ->where('original_filename', $originalFilename)
                ->first();

            if ($existingUpload) {
                // HAPUS data mining lama yang terkait dengan upload ini
                MiningData::where('upload_id', $existingUpload->id)->delete();

                // Hapus file fisik lama
                if (Storage::disk('local')->exists($existingUpload->stored_filename)) {
                    Storage::disk('local')->delete($existingUpload->stored_filename);
                }

                // Log aktivitas penghapusan
                ActivityLog::create([
                    'user_id' => $userId,
                    'action' => 'delete_duplicate',
                    'description' => "Menghapus data lama dari file: {$originalFilename}",
                    'ip_address' => $request->ip(),
                ]);

                // Hapus record upload lama
                $existingUpload->delete();
            }

            // Store file baru
            $storedFilename = 'uploads/' . $userId . '_' . time() . '_' . $originalFilename;
            $file->storeAs('', $storedFilename, 'local');

            // Buat record upload baru
            $upload = ExcelUpload::create([
                'user_id' => $userId,
                'original_filename' => $originalFilename,
                'stored_filename' => $storedFilename,
                'status' => 'processing',
            ]);

            // Import data Excel
            Excel::import(new MiningDataImport($userId, $upload->id), $file);

            // Hitung jumlah row yang berhasil diimport
            $rowCount = MiningData::where('upload_id', $upload->id)->count();

            // Update status upload
            $upload->update([
                'row_count' => $rowCount,
                'status' => 'completed',
            ]);

            // Log aktivitas upload
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'upload',
                'description' => "Upload file: {$originalFilename} ({$rowCount} baris data)",
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', "Upload berhasil! {$rowCount} baris data telah diimport.");

        } catch (\Exception $e) {
            DB::rollBack();

            // Update status upload ke failed jika ada
            if (isset($upload)) {
                $upload->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            // Log error
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'upload_failed',
                'description' => "Upload gagal: {$originalFilename}. Error: " . $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            return redirect()->back()->with('error', 'Upload gagal: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan data milik user (USER ISOLATION)
     */
    public function index(Request $request)
    {
        $userId = auth()->id();

        // Filter
        $query = MiningData::where('user_id', $userId); // USER ISOLATION - HANYA DATA MILIK USER INI

        // Filter tanggal
        if ($request->filled('date_from')) {
            $query->where('tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('tanggal', '<=', $request->date_to);
        }

        // Filter shift
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }

        // Filter lokasi
        if ($request->filled('lokasi')) {
            $query->where('lokasi', $request->lokasi);
        }

        // Ambil data dengan pagination
        $data = $query->latest('tanggal')
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        // Stats KPI (hanya untuk user ini)
        $stats = [
            'total_tonnase' => MiningData::where('user_id', $userId)->sum('tonnase'),
            'total_rit' => MiningData::where('user_id', $userId)->sum('rit'),
            'total_records' => MiningData::where('user_id', $userId)->count(),
            'last_upload' => ExcelUpload::where('user_id', $userId)->latest()->first(),
        ];

        // Filter options
        $shifts = MiningData::where('user_id', $userId)->distinct()->pluck('shift')->filter();
        $lokasi = MiningData::where('user_id', $userId)->distinct()->pluck('lokasi')->filter();

        return view('mining.index', compact('data', 'stats', 'shifts', 'lokasi'));
    }

    /**
     * Delete data by upload
     */
    public function deleteUpload($uploadId)
    {
        $userId = auth()->id();

        // USER ISOLATION: Pastikan upload ini milik user yang sedang login
        $upload = ExcelUpload::where('id', $uploadId)
            ->where('user_id', $userId)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Hapus semua mining data terkait
            MiningData::where('upload_id', $uploadId)->delete();

            // Hapus file fisik
            if (Storage::disk('local')->exists($upload->stored_filename)) {
                Storage::disk('local')->delete($upload->stored_filename);
            }

            // Log aktivitas
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'delete',
                'description' => "Menghapus upload: {$upload->original_filename}",
                'ip_address' => request()->ip(),
            ]);

            // Hapus record upload
            $upload->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Data berhasil dihapus!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
