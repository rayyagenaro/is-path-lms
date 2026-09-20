<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'IS-Path') · Temukan Arah Kariermu</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/is-path-v3.css') }}?v={{ filemtime(public_path('css/is-path-v3.css')) }}">
</head>
<body>
<a class="skip-link" href="#main-content">Lewati navigasi</a>

<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Navigasi aplikasi">
        <a href="{{ route('dashboard') }}" class="brand" aria-label="IS-Path, kembali ke ringkasan">
            <span class="brand-mark" aria-hidden="true">IS</span>
            <span>IS-Path</span>
        </a>

        <nav class="nav-list" aria-label="Navigasi utama">
            <p class="nav-label">UTAMA</p>

            <a
                class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                href="{{ route('dashboard') }}"
                @if(request()->routeIs('dashboard')) aria-current="page" @endif
            >
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M3 11.5 12 4l9 7.5M5.5 10v10h13V10M9.5 20v-6h5v6"/>
                </svg>
                <span>Ringkasan</span>
            </a>

            <a
                    class="nav-item {{ request()->routeIs('careers*') ? 'active' : '' }}"
                    href="{{ route('careers') }}"
                    @if(request()->routeIs('careers*')) aria-current="page" @endif
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M9 6V4h6v2M4 8h16v11H4zM4 12c5 2 11 2 16 0M10 13h4"/>
                    </svg>
                    <span>Eksplorasi Karier</span>
            </a>
            <a
                    class="nav-item {{ request()->routeIs('courses*') ? 'active' : '' }}"
                    href="{{ route('courses') }}"
                    @if(request()->routeIs('courses*')) aria-current="page" @endif
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5zM4 5.5v16M8 7h8M8 11h7"/>
                    </svg>
                    <span>Pembelajaran</span>
            </a>
            <a
                    class="nav-item {{ request()->routeIs('assessments.*') ? 'active' : '' }}"
                    href="{{ route('assessments.index') }}"
                    @if(request()->routeIs('assessments.*')) aria-current="page" @endif
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M7 3h10v4H7zM6 5H4v16h16V5h-2M8 12l2.5 2.5L16 9M8 18h8"/>
                    </svg>
                    <span>Assessment</span>
            </a>
            <a
                    class="nav-item {{ request()->routeIs('competencies*') ? 'active' : '' }}"
                    href="{{ route('competencies') }}"
                    @if(request()->routeIs('competencies*')) aria-current="page" @endif
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M4 18V9M10 18V5M16 18v-7M22 18V3M2 21h22"/>
                    </svg>
                    <span>Kompetensi</span>
            </a>
            <a
                    class="nav-item {{ request()->routeIs('career-profile') ? 'active' : '' }}"
                    href="{{ route('career-profile') }}"
                    @if(request()->routeIs('career-profile')) aria-current="page" @endif
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <circle cx="12" cy="8" r="3.5"/>
                        <path d="M5 20c.7-4 3-6 7-6s6.3 2 7 6"/>
                    </svg>
                    <span>Profil Karier</span>
            </a>

        </nav>

        <div class="sidebar-foot">
            <div class="mini-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="sidebar-user">
                <strong>{{ auth()->user()->name }}</strong>
                <small>Mahasiswa</small>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="icon-button icon-button-sm" type="submit" title="Keluar" aria-label="Keluar dari IS-Path">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M14 8l4 4-4 4M18 12H7M10 4H4v16h6"/>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <button
        class="sidebar-overlay"
        type="button"
        data-sidebar-overlay
        aria-label="Tutup navigasi"
        tabindex="-1"
    ></button>

    <main class="main" id="main-content" tabindex="-1">
        <header class="topbar compact-topbar">
            <div class="topbar-inner">
                <button
                    class="menu-toggle icon-button"
                    type="button"
                    data-sidebar-toggle
                    aria-label="Buka navigasi"
                    aria-expanded="false"
                    aria-controls="sidebar"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M4 7h16M4 12h16M4 17h16"/>
                    </svg>
                </button>
                <span class="sr-only">IS-Path</span>
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>
    </main>
</div>

<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
