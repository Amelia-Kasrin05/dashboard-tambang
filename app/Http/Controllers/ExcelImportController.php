<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\MiningDataImport;
use App\Models\ExcelUpload;
use App\Models\MiningData;
use App\Models\ActivityLog;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Legacy Excel Import Controller
 * Redirect ke MiningDataController untuk fitur mining
 */
class ExcelImportController extends Controller
{
    /**
     * Menampilkan halaman upload Excel
     */
    public function index()
    {
        // Get recent uploads for current user
        $recentUploads = ExcelUpload::where('user_id', auth()->id())
            ->where('status', 'completed')
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('excel.upload', compact('recentUploads'));
    }

    /**
     * Proses upload Excel → mining_data
     * (Menggunakan sistem baru dengan anti-duplikasi)
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240' // Max 10MB
        ]);

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $userId = auth()->id();

        DB::beginTransaction();
        try {
            // ANTI-DUPLIKASI: Check if same filename exists
            $existingUpload = ExcelUpload::where('user_id', $userId)
                ->where('original_filename', $originalFilename)
                ->first();

            if ($existingUpload) {
                // Delete old mining data
                MiningData::where('upload_id', $existingUpload->id)->delete();

                // Delete old file
                if (Storage::disk('local')->exists($existingUpload->stored_filename)) {
                    Storage::disk('local')->delete($existingUpload->stored_filename);
                }

                // Log deletion
                ActivityLog::log('delete_duplicate', "Menghapus data lama dari file: {$originalFilename}", $userId);

                $existingUpload->delete();
            }

            // Store new file
            $storedFilename = 'uploads/' . $userId . '_' . time() . '_' . $originalFilename;
            $file->storeAs('', $storedFilename, 'local');

            // Create upload record
            $upload = ExcelUpload::create([
                'user_id' => $userId,
                'original_filename' => $originalFilename,
                'stored_filename' => $storedFilename,
                'status' => 'processing',
            ]);

            // Import Excel data
            Excel::import(new MiningDataImport($userId, $upload->id), $file);

            // Count imported rows
            $rowCount = MiningData::where('upload_id', $upload->id)->count();

            // Update upload status
            $upload->update([
                'row_count' => $rowCount,
                'status' => 'completed',
            ]);

            // Log success
            ActivityLog::log('upload_success', "Upload file: {$originalFilename} ({$rowCount} rows)", $userId);

            DB::commit();

            return back()->with('success', "Upload berhasil! {$rowCount} baris data mining telah disimpan.");

        } catch (\Exception $e) {
            DB::rollBack();

            // Log error
            ActivityLog::log('upload_error', "Upload gagal: {$originalFilename} - Error: {$e->getMessage()}", $userId ?? null);

            return back()->with('error', 'Upload gagal: ' . $e->getMessage());
        }
    }
}
