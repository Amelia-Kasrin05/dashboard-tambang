<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Dashboard Produksi Tambang') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --navy-dark: #0a1929;
            --navy-medium: #1a2332;
            --navy-light: #2a3f5f;
            --navy-accent: #3d5a80;
            --white: #ffffff;
            --gray-light: #f8f9fa;
            --gray-medium: #e9ecef;
            --text-dark: #212529;
            --text-light: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--gray-light);
            color: var(--text-dark);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--navy-dark);
            color: var(--white);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-placeholder {
            width: 50px;
            height: 50px;
            background: var(--navy-accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }

        .logo-text h1 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .logo-text p {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.6);
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .menu-item:hover {
            background: var(--navy-medium);
            color: var(--white);
            border-left-color: var(--white);
        }

        .menu-item.active {
            background: var(--navy-accent);
            color: var(--white);
            border-left-color: var(--white);
        }

        .menu-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: var(--navy-dark);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--navy-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .user-details h3 {
            font-size: 14px;
            font-weight: 600;
        }

        .user-details p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: var(--white);
            padding: 16px 32px;
            border-bottom: 1px solid var(--gray-medium);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--navy-dark);
        }

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .content {
            padding: 32px;
            flex: 1;
        }

        /* Button Styles */
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--navy-dark);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--navy-accent);
        }

        .btn-secondary {
            background: var(--gray-medium);
            color: var(--text-dark);
        }

        .btn-secondary:hover {
            background: var(--text-light);
            color: var(--white);
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
        }

        .btn-logout {
            background: var(--danger);
            color: var(--white);
            width: 100%;
            justify-content: center;
        }

        /* Card */
        .card {
            background: var(--white);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .card-header {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--navy-dark);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--navy-dark);
        }

        /* Alert */
        .alert {
            padding: 16px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content {
                padding: 16px;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-placeholder">
                    <!-- Logo akan diletakkan di sini -->
                    <img src="#" alt="Logo" style="display:none;" id="company-logo">
                    <span id="logo-initial">PT</span>
                </div>
                <div class="logo-text">
                    <h1>Dashboard Produksi</h1>
                    <p>Sistem Monitoring Tambang</p>
                </div>
            </div>

            <nav class="sidebar-menu">
                <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard Utama
                </a>

                <div style="padding: 12px 20px; color: rgba(255, 255, 255, 0.5); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                    Data Mining
                </div>

                <a href="{{ route('excel.upload.form') }}" class="menu-item {{ request()->routeIs('excel.upload.*') ? 'active' : '' }}">
                    <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Upload Excel
                </a>

                @if(auth()->user()->isSuperAdmin())
                <a href="#" class="menu-item">
                    <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Kelola User
                </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="user-details">
                        <h3>{{ auth()->user()->name }}</h3>
                        <p>{{ auth()->user()->role_label }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-logout">
                        <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h2>@yield('page-title', 'Dashboard')</h2>
                <div class="header-actions">
                    @yield('header-actions')
                </div>
            </header>

            <div class="content">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-error">
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
