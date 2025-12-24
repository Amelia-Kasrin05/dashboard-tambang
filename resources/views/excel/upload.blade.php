@extends('layouts.dashboard')

@section('page-title', 'Upload Excel')

@section('content')

<style>
    .upload-card {
        max-width: 600px;
        margin: 0 auto;
    }

    .upload-area {
        border: 2px dashed var(--navy-accent);
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        background: var(--gray-light);
        transition: all 0.3s;
    }

    .upload-area:hover {
        border-color: var(--navy-dark);
        background: var(--white);
    }

    .upload-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        color: var(--navy-accent);
    }

    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
    }

    .file-input-wrapper input[type=file] {
        position: absolute;
        left: -9999px;
    }

    .file-label {
        padding: 12px 24px;
        background: var(--navy-dark);
        color: var(--white);
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
        display: inline-block;
    }

    .file-label:hover {
        background: var(--navy-accent);
    }

    .file-name {
        margin-top: 16px;
        font-size: 14px;
        color: var(--text-light);
    }

    .upload-info {
        margin-top: 24px;
        padding: 16px;
        background: #e3f2fd;
        border-radius: 6px;
        border-left: 4px solid var(--navy-dark);
    }

    .upload-info h4 {
        margin-bottom: 8px;
        color: var(--navy-dark);
        font-size: 14px;
    }

    .upload-info ul {
        margin: 0;
        padding-left: 20px;
        font-size: 13px;
        color: var(--text-dark);
    }

    .upload-info li {
        margin-bottom: 4px;
    }
</style>

<div class="upload-card">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Upload File Excel</h3>
        </div>

        <form method="POST" action="{{ route('excel.upload.process') }}" enctype="multipart/form-data" id="upload-form">
            @csrf

            <div class="upload-area">
                <svg class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>

                <h3 style="margin-bottom: 12px; color: var(--navy-dark);">Pilih File Excel</h3>
                <p style="margin-bottom: 20px; color: var(--text-light); font-size: 14px;">
                    Format: .xlsx, .xls, .csv
                </p>

                <div class="file-input-wrapper">
                    <label for="file" class="file-label">
                        <svg style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Browse File
                    </label>
                    <input type="file" id="file" name="file" required accept=".xlsx,.xls,.csv">
                </div>

                <div id="file-name" class="file-name"></div>
            </div>

            <div style="margin-top: 24px; text-align: center;">
                <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Upload Excel
                </button>
            </div>
        </form>

        <div class="upload-info">
            <h4>Informasi Upload:</h4>
            <ul>
                <li>File akan masuk ke tabel <strong>productions_raw</strong></li>
                <li>Semua baris akan diproses tanpa di-skip</li>
                <li>Kolom kosong akan diisi dengan NULL</li>
                <li>Setelah upload, klik <strong>Normalisasi Data</strong> di Dashboard</li>
                <li>User: <strong>{{ auth()->user()->name }}</strong></li>
            </ul>
        </div>
    </div>
</div>

<script>
document.getElementById('file').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || '';
    const fileNameDisplay = document.getElementById('file-name');
    const submitBtn = document.getElementById('submit-btn');

    if (fileName) {
        fileNameDisplay.textContent = 'File dipilih: ' + fileName;
        fileNameDisplay.style.color = 'var(--success)';
        fileNameDisplay.style.fontWeight = '500';
        submitBtn.disabled = false;
    } else {
        fileNameDisplay.textContent = '';
        submitBtn.disabled = true;
    }
});
</script>

@endsection
