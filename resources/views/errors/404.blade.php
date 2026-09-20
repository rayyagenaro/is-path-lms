<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman tidak ditemukan · IS-Path</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/is-path-v3.css') }}?v={{ filemtime(public_path('css/is-path-v3.css')) }}">
</head>
<body class="error-page">
<main class="error-shell">
    <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('login') }}" aria-label="IS-Path, kembali ke halaman utama">
        <span class="brand-mark" aria-hidden="true">IS</span>
        <span>IS-Path</span>
    </a>

    <section class="error-content" aria-labelledby="error-title">
        <div class="error-visual" aria-hidden="true">
            <svg viewBox="0 0 120 120" focusable="false">
                <circle cx="60" cy="60" r="48"/>
                <path d="M43 77 54 54l23-11-11 23-23 11Z"/>
                <circle cx="60" cy="60" r="4"/>
            </svg>
            <span>404</span>
        </div>
        <span class="eyebrow">JALUR TIDAK DITEMUKAN</span>
        <h1 id="error-title">Alamat ini tidak menuju halaman IS-Path.</h1>
        <p>Tautannya mungkin berubah atau tidak lengkap. Kembali ke ringkasan untuk melanjutkan langkah belajarmu.</p>
        <a class="btn btn-primary" href="{{ auth()->check() ? route('dashboard') : route('login') }}">
            {{ auth()->check() ? 'Kembali ke ringkasan' : 'Kembali ke halaman masuk' }}
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"/></svg>
        </a>
    </section>
</main>
</body>
</html>
