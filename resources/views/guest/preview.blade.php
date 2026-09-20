<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pratinjau · IS-Path</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/is-path-v3.css') }}?v={{ filemtime(public_path('css/is-path-v3.css')) }}">
</head>
<body class="guest-body">
<header class="guest-header">
    <a class="brand" href="{{ route('login') }}" aria-label="IS-Path, kembali ke halaman masuk">
        <span class="brand-mark" aria-hidden="true">IS</span>
        <span>IS-Path</span>
    </a>
    <nav aria-label="Akun">
        <a class="btn btn-outline" href="{{ route('login') }}">Masuk</a>
        <a class="btn btn-primary" href="{{ route('register') }}">Buat akun</a>
    </nav>
</header>

<main class="guest-shell">
    <aside class="guest-nav" aria-label="Navigasi pratinjau">
        <span class="guest-nav-label">PRATINJAU</span>
        <span class="guest-nav-item active">Ringkasan</span>
        <span class="guest-nav-item">Eksplorasi karier</span>
        <span class="guest-nav-item">Pembelajaran</span>
        <span class="guest-nav-item">Kompetensi</span>
        <span class="guest-nav-item">Assessment</span>
    </aside>

    <section class="guest-content" aria-labelledby="guest-title">
        <div class="guest-notice" role="status">
            <div>
                <strong>Mode guest</strong>
                <span>Kamu sedang melihat contoh tampilan. Data tidak dapat dibuka atau diubah.</span>
            </div>
            <a href="{{ route('register') }}">Daftar untuk mulai</a>
        </div>

        <header class="guest-intro">
            <span class="eyebrow">CONTOH RUANG BELAJAR</span>
            <h1 id="guest-title">Arah belajar terlihat lebih jelas.</h1>
            <p>Setelah profil dan assessment dilengkapi, halaman ini merangkum target karier, hasil pengukuran, dan kompetensi yang perlu diprioritaskan.</p>
        </header>

        <div class="guest-dashboard" aria-label="Contoh ringkasan mahasiswa">
            <section class="guest-target">
                <div>
                    <span class="eyebrow">TARGET KARIER</span>
                    <h2>Data Analyst</h2>
                    <p>Kekuatan awal pada analisis dan pemahaman bisnis sudah mendukung arah ini.</p>
                </div>
                <div class="guest-score" aria-label="Contoh kesiapan 68 persen">
                    <strong>68%</strong>
                    <span>Kesiapan awal</span>
                </div>
            </section>

            <section class="guest-priorities" aria-labelledby="priority-title">
                <div class="guest-section-head">
                    <div>
                        <span class="eyebrow">PRIORITAS BELAJAR</span>
                        <h2 id="priority-title">Tiga kompetensi berikutnya</h2>
                    </div>
                    <span>Contoh data</span>
                </div>
                <ol>
                    <li><span>01</span><div><strong>SQL</strong><small>SQL & Relational Database</small></div><b>Gap 18</b></li>
                    <li><span>02</span><div><strong>Power BI</strong><small>Power BI</small></div><b>Gap 14</b></li>
                    <li><span>03</span><div><strong>Statistics</strong><small>Statistics for Analytics</small></div><b>Gap 11</b></li>
                </ol>
            </section>
        </div>

        <footer class="guest-footer">
            <p>Buat akun mahasiswa untuk mengisi asesmen awal dan mendapatkan hasil milikmu sendiri.</p>
            <a class="btn btn-primary" href="{{ route('register') }}">Buat akun mahasiswa</a>
        </footer>
    </section>
</main>
</body>
</html>
