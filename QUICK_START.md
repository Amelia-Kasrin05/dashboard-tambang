# ⚡ QUICK START - Dashboard Tambang PT Semen Padang

## 🚀 Start dalam 5 Menit!

### 1️⃣ Setup Database (1 menit)
```bash
# Jalankan XAMPP → Start MySQL
# Buka http://localhost/phpmyadmin
# Buat database: dashboard_tambang
```

### 2️⃣ Run Migrations (1 menit)
```bash
cd c:\Projek\dashboard-tambang

# Jalankan migrations
php artisan migrate

# Buat 5 user akun
php artisan db:seed --class=PTSemenPadangSeeder
```

### 3️⃣ Start Server (30 detik)
```bash
php artisan serve
```

### 4️⃣ Login & Test (2 menit)
```
http://localhost:8000

Login dengan:
Email   : admin@semenpadang.com
Password: password
```

---

## 📊 Upload Excel Format

**Minimal kolom yang diperlukan:**
```
tanggal | shift | lokasi | tonnase
```

**Full kolom (opsional):**
```
tanggal, shift, lokasi, material, volume_bcm, tonnase,
equipment_type, equipment_code, rit, fuel_usage, keterangan
```

**Contoh Excel:**
| tanggal    | shift | lokasi | material   | tonnase | equipment_code | rit |
|------------|-------|--------|------------|---------|----------------|-----|
| 01/12/2024 | 1     | Pit A  | Limestone  | 1500.50 | PC-200         | 25  |
| 02/12/2024 | 2     | Pit B  | Overburden | 2000    | EX-300         | 30  |

---

## 👥 Login Credentials

| Role       | Email                       | Password |
|------------|-----------------------------|----------|
| Admin      | admin@semenpadang.com       | password |
| Supervisor | supervisor@semenpadang.com  | password |
| User 1     | user1@semenpadang.com       | password |
| User 2     | user2@semenpadang.com       | password |
| User 3     | user3@semenpadang.com       | password |

---

## ✨ Fitur Utama

### 1. Anti-Duplikasi ✅
Upload file dengan nama sama → Data lama **OTOMATIS TERHAPUS** → Data baru masuk

### 2. User Isolation ✅
Setiap user **HANYA LIHAT DATANYA SENDIRI**

### 3. Activity Logging ✅
Semua aktivitas tercatat (upload, delete, login)

### 4. Multi-Format Excel ✅
Support semua format tanggal (d/m/Y, Excel serial, dll)

---

## 🔗 Routes yang Tersedia

Routes sudah ditambahkan di `routes/web.php`:

```php
Route::middleware('auth')->prefix('mining')->name('mining.')->group(function () {
    // Dashboard Mining
    Route::get('/dashboard', [MiningDataController::class, 'index'])
        ->name('dashboard');

    // Upload Excel
    Route::post('/upload', [MiningDataController::class, 'upload'])
        ->name('upload');

    // Delete Upload
    Route::delete('/upload/{uploadId}', [MiningDataController::class, 'deleteUpload'])
        ->name('upload.delete');

    // API untuk Chart Data
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/dashboard-summary', [ChartDataController::class, 'dashboardSummary']);
        Route::get('/daily-production', [ChartDataController::class, 'dailyProduction']);
        Route::get('/weekly-production', [ChartDataController::class, 'weeklyProduction']);
        Route::get('/monthly-production', [ChartDataController::class, 'monthlyProduction']);
        Route::get('/equipment-stats', [ChartDataController::class, 'equipmentStats']);
        Route::get('/material-breakdown', [ChartDataController::class, 'materialBreakdown']);
    });
});
```

**API Endpoints:**
- `GET /mining/api/dashboard-summary` - KPI untuk gauge charts (bulan ini)
- `GET /mining/api/daily-production?days=30` - Produksi harian (default 30 hari)
- `GET /mining/api/weekly-production?weeks=12` - Produksi mingguan (default 12 minggu)
- `GET /mining/api/monthly-production?months=12` - Produksi bulanan (default 12 bulan)
- `GET /mining/api/equipment-stats?date_from=2024-01-01&date_to=2024-12-31` - Statistik per equipment
- `GET /mining/api/material-breakdown?date_from=2024-01-01&date_to=2024-12-31` - Breakdown material/lokasi/shift

---

## 🎨 Next: Buat View

Buat file `resources/views/mining/index.blade.php` untuk dashboard.

Template sudah tersedia di **SETUP_COMPLETE.md**

---

## 🐛 Error? Cek Ini:

1. **MySQL not running?**
   ```
   Buka XAMPP → Start MySQL
   ```

2. **Migration error?**
   ```bash
   # Cek .env
   DB_DATABASE=dashboard_tambang
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. **Upload error?**
   ```bash
   # Cek folder permissions
   chmod -R 775 storage
   chmod -R 775 bootstrap/cache
   ```

---

## 📁 File Structure

```
dashboard-tambang/
├── app/
│   ├── Models/
│   │   ├── ExcelUpload.php ✅
│   │   ├── MiningData.php ✅
│   │   └── ActivityLog.php ✅
│   ├── Http/Controllers/
│   │   └── MiningDataController.php ✅
│   └── Imports/
│       └── MiningDataImport.php ✅
├── database/
│   ├── migrations/
│   │   ├── *_create_excel_uploads_table.php ✅
│   │   ├── *_create_mining_data_table.php ✅
│   │   └── *_create_activity_logs_table.php ✅
│   └── seeders/
│       └── PTSemenPadangSeeder.php ✅
└── routes/
    └── web.php (update dengan routes baru)
```

---

## 📖 Dokumentasi Lengkap

Lihat **SETUP_COMPLETE.md** untuk:
- Cloudflare Tunnel setup
- API Endpoints
- Chart implementation
- Troubleshooting lengkap

---

🎉 **SELAMAT! Sistem siap digunakan untuk magang!**
