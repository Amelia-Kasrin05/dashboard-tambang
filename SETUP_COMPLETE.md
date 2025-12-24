# 🚀 PANDUAN SETUP LENGKAP - Dashboard Tambang PT Semen Padang

## 📋 Sistem yang Sudah Dibuat

### ✅ Fitur yang Sudah Siap:
1. **Anti-Duplikasi Upload** ✅
   - Upload file Excel dengan nama sama = HAPUS data lama → INSERT data baru
   - File disimpan dengan format: `{user_id}_{timestamp}_{filename}`

2. **User Isolation** ✅
   - Setiap user HANYA bisa lihat datanya sendiri
   - Filter otomatis by `user_id` di semua query

3. **5 User Akun** ✅
   - Admin, Supervisor, 3 Operator
   - Role-based: admin, supervisor, user

4. **Activity Logging** ✅
   - Semua aktivitas upload/delete tercatat
   - Tracking IP address

5. **Excel Import Multi-Format** ✅
   - Support berbagai format tanggal
   - Parse Excel serial number
   - Batch insert untuk performa

---

## 🔧 LANGKAH SETUP

### 1️⃣ Setup Database MySQL (XAMPP)

1. **Jalankan XAMPP:**
   - Start Apache
   - Start MySQL

2. **Buat Database:**
   ```bash
   # Buka http://localhost/phpmyadmin
   # Klik "New" → Database name: dashboard_tambang → Create
   ```

   **ATAU via Command Line:**
   ```bash
   mysql -u root -p
   CREATE DATABASE dashboard_tambang;
   EXIT;
   ```

3. **Update `.env`:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=dashboard_tambang
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### 2️⃣ Jalankan Migrations

```bash
# Jalankan semua migrations
php artisan migrate

# Jalankan seeder untuk 5 users
php artisan db:seed --class=PTSemenPadangSeeder
```

**Output yang diharapkan:**
```
✅ 5 Users PT Semen Padang berhasil dibuat!

Login Credentials:
==================
Admin     : admin@semenpadang.com / password
Supervisor: supervisor@semenpadang.com / password
User 1    : user1@semenpadang.com / password
User 2    : user2@semenpadang.com / password
User 3    : user3@semenpadang.com / password
```

### 3️⃣ Testing Upload Excel

1. **Login** sebagai salah satu user
2. **Upload Excel** dengan kolom (minimal):
   ```
   tanggal | shift | lokasi | material | tonnase | equipment_code | rit
   ```

3. **Format Excel yang Didukung:**
   - Header row (baris pertama) = nama kolom
   - Tanggal: bisa format apapun (d/m/Y, Y-m-d, Excel serial, dll)
   - Angka: bisa pakai koma/titik

**Contoh Excel Template:**
```csv
tanggal,shift,lokasi,material,tonnase,equipment_code,rit
01/12/2024,1,Pit A,Limestone,1500.50,PC-200,25
02/12/2024,2,Pit B,Overburden,2000,EX-300,30
```

### 4️⃣ Update Routes

Tambahkan routes baru di `routes/web.php`:

```php
use App\Http\Controllers\MiningDataController;
use App\Http\Controllers\Api\ChartDataController;

Route::middleware('auth')->group(function () {

    // Mining Data Routes
    Route::get('/mining/dashboard', [MiningDataController::class, 'index'])
        ->name('mining.dashboard');

    Route::post('/mining/upload', [MiningDataController::class, 'upload'])
        ->name('mining.upload');

    Route::delete('/mining/upload/{uploadId}', [MiningDataController::class, 'deleteUpload'])
        ->name('mining.upload.delete');

    // API untuk Chart Data (AJAX)
    Route::prefix('api')->group(function () {
        Route::get('/chart/daily', [ChartDataController::class, 'daily']);
        Route::get('/chart/weekly', [ChartDataController::class, 'weekly']);
        Route::get('/chart/monthly', [ChartDataController::class, 'monthly']);
    });
});
```

---

## 📊 STRUKTUR DATABASE

### Tabel `users`
```sql
- id
- name
- email
- password
- role (admin / supervisor / user)
- department
- timestamps
```

### Tabel `excel_uploads`
```sql
- id
- user_id (FK → users)
- original_filename
- stored_filename
- row_count
- status (pending/processing/completed/failed)
- error_message
- timestamps
```

### Tabel `mining_data`
```sql
- id
- user_id (FK → users) [USER ISOLATION]
- upload_id (FK → excel_uploads)
- tanggal
- shift
- lokasi
- material
- volume_bcm
- volume_lcm
- tonnase
- equipment_type
- equipment_code
- rit
- fuel_usage
- jam_operasi
- jam_breakdown
- latitude
- longitude
- keterangan
- timestamps
```

### Tabel `activity_logs`
```sql
- id
- user_id (FK → users)
- action (upload/delete/export/login)
- description
- ip_address
- user_agent
- timestamps
```

---

## 🔐 FITUR USER ISOLATION

**Cara Kerja:**
```php
// ❌ SALAH - Tanpa isolation
$data = MiningData::all();

// ✅ BENAR - Dengan isolation
$data = MiningData::where('user_id', auth()->id())->get();

// ✅ Atau menggunakan scope
$data = MiningData::byUser(auth()->id())->get();
```

**Setiap user HANYA bisa:**
- Lihat data upload-annya sendiri
- Hapus data upload-annya sendiri
- Export data miliknya sendiri

---

## 🚫 FITUR ANTI-DUPLIKASI

**Logika:**
1. User upload `data_produksi_december.xlsx`
2. Sistem cek: "Apakah user ini sudah pernah upload file dengan nama yang sama?"
3. **Jika YA:**
   - HAPUS semua `mining_data` yang terkait upload lama
   - HAPUS file fisik lama
   - INSERT data baru dari file baru
4. **Jika TIDAK:**
   - Langsung INSERT data baru

**Kode Implementation:**
```php
// Di MiningDataController.php
$existingUpload = ExcelUpload::where('user_id', $userId)
    ->where('original_filename', $originalFilename)
    ->first();

if ($existingUpload) {
    // Hapus data lama
    MiningData::where('upload_id', $existingUpload->id)->delete();
    Storage::delete($existingUpload->stored_filename);
    $existingUpload->delete();

    // Log activity
    ActivityLog::create([...]);
}

// Insert data baru
Excel::import(new MiningDataImport($userId, $upload->id), $file);
```

---

## 📱 NEXT STEPS

### 1. Buat View Dashboard (Blade Template)

Buat file `resources/views/mining/index.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    {{-- KPI Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Total Tonnase</h5>
                    <h2>{{ number_format($stats['total_tonnase'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Rit</h5>
                    <h2>{{ number_format($stats['total_rit']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Total Records</h5>
                    <h2>{{ number_format($stats['total_records']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Last Upload</h5>
                    <small>{{ $stats['last_upload']?->created_at?->diffForHumans() ?? 'Belum ada' }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Upload Form --}}
    <div class="card mb-4">
        <div class="card-header">Upload Excel Data Produksi</div>
        <div class="card-body">
            <form action="{{ route('mining.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
                <button type="submit" class="btn btn-success">Upload</button>
            </form>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card">
        <div class="card-header">Data Produksi Tambang</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Shift</th>
                        <th>Lokasi</th>
                        <th>Material</th>
                        <th>Tonnase</th>
                        <th>Equipment</th>
                        <th>Rit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                    <tr>
                        <td>{{ $row->tanggal->format('d/m/Y') }}</td>
                        <td>{{ $row->shift }}</td>
                        <td>{{ $row->lokasi }}</td>
                        <td>{{ $row->material }}</td>
                        <td>{{ number_format($row->tonnase, 2) }}</td>
                        <td>{{ $row->equipment_code }}</td>
                        <td>{{ $row->rit }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $data->links() }}
        </div>
    </div>
</div>
@endsection
```

### 2. Buat API Controller untuk Chart Data

Buat `app/Http/Controllers/Api/ChartDataController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MiningData;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ChartDataController extends Controller
{
    /**
     * Data chart harian (7 hari terakhir)
     */
    public function daily()
    {
        $userId = auth()->id();

        $data = MiningData::where('user_id', $userId)
            ->where('tanggal', '>=', Carbon::now()->subDays(7))
            ->selectRaw('DATE(tanggal) as date, SUM(tonnase) as total_tonnase, SUM(rit) as total_rit')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'labels' => $data->pluck('date'),
            'tonnase' => $data->pluck('total_tonnase'),
            'rit' => $data->pluck('total_rit'),
        ]);
    }

    // ... weekly & monthly methods
}
```

---

## 🌐 SETUP CLOUDFLARE TUNNEL

### Instalasi Cloudflare Tunnel (cloudflared)

1. **Download cloudflared:**
   ```bash
   # Windows: Download dari
   https://github.com/cloudflare/cloudflared/releases

   # Atau via winget
   winget install --id Cloudflare.cloudflared
   ```

2. **Login ke Cloudflare:**
   ```bash
   cloudflared tunnel login
   ```
   - Browser akan terbuka
   - Login dengan akun Cloudflare Anda
   - Pilih domain (misal: namadomain.my.id)

3. **Buat Tunnel:**
   ```bash
   cloudflared tunnel create dashboard-tambang-sp
   ```
   - Catat Tunnel ID yang muncul

4. **Configure Tunnel:**

   Buat file `config.yml`:
   ```yaml
   tunnel: <TUNNEL_ID>
   credentials-file: C:\Users\<USER>\.cloudflared\<TUNNEL_ID>.json

   ingress:
     - hostname: dashboard.namadomain.my.id
       service: http://localhost:8000
     - service: http_status:404
   ```

5. **Route DNS:**
   ```bash
   cloudflared tunnel route dns dashboard-tambang-sp dashboard.namadomain.my.id
   ```

6. **Jalankan Tunnel:**
   ```bash
   # Testing
   cloudflared tunnel run dashboard-tambang-sp

   # Atau install as service
   cloudflared service install
   ```

7. **Jalankan Laravel:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

8. **Akses dari Internet:**
   ```
   https://dashboard.namadomain.my.id
   ```

---

## ✅ CHECKLIST DEPLOYMENT

- [ ] XAMPP MySQL running
- [ ] Database `dashboard_tambang` created
- [ ] `.env` configured
- [ ] Run `php artisan migrate`
- [ ] Run `php artisan db:seed --class=PTSemenPadangSeeder`
- [ ] Test login dengan 5 users
- [ ] Test upload Excel
- [ ] Test anti-duplikasi (upload file sama 2x)
- [ ] Test user isolation (login beda user, cek data berbeda)
- [ ] Cloudflare Tunnel installed
- [ ] Tunnel configured & running
- [ ] Domain pointing to tunnel
- [ ] Laravel serve running
- [ ] Akses dari internet works

---

## 🐛 TROUBLESHOOTING

### Error: "No connection could be made"
```bash
# Cek MySQL running
# Buka XAMPP → Start MySQL
# Cek di .env: DB_HOST=127.0.0.1
```

### Error: "Class 'Maatwebsite\Excel\...' not found"
```bash
composer require maatwebsite/excel
```

### Upload gagal: "File too large"
```php
// php.ini
upload_max_filesize = 20M
post_max_size = 20M
```

### Tunnel tidak connect
```bash
# Restart tunnel
cloudflared tunnel run dashboard-tambang-sp

# Cek status
cloudflared tunnel list
```

---

## 📞 SUPPORT

Jika ada error, cek:
1. `storage/logs/laravel.log`
2. Browser Console (F12)
3. MySQL error log di XAMPP

**File penting yang sudah dibuat:**
- ✅ `app/Models/ExcelUpload.php`
- ✅ `app/Models/MiningData.php`
- ✅ `app/Models/ActivityLog.php`
- ✅ `app/Http/Controllers/MiningDataController.php`
- ✅ `app/Imports/MiningDataImport.php`
- ✅ `database/migrations/*_create_excel_uploads_table.php`
- ✅ `database/migrations/*_create_mining_data_table.php`
- ✅ `database/migrations/*_create_activity_logs_table.php`
- ✅ `database/seeders/PTSemenPadangSeeder.php`

**Password semua user: `password`**

---

🎉 **SISTEM SIAP DIGUNAKAN!**
