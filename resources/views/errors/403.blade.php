<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses belum tersedia | IS-Path</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/is-path-v3.css') }}?v={{ filemtime(public_path('css/is-path-v3.css')) }}">
</head>
<body class="error-page">
<main class="error-shell">
    <a class="brand" href="{{ route('login') }}">IS-Path</a>
    <section class="error-content">
        <span class="eyebrow">AKSES BELUM TERSEDIA</span>
        <h1>Halaman ini belum dapat dibuka.</h1>
        <p>Pastikan kamu memakai akun yang sesuai dan sudah menyelesaikan tahap sebelumnya. Modul yang terkunci perlu dibuka secara berurutan.</p>
        <a class="btn btn-primary" href="{{ auth()->check() ? route('dashboard') : route('login') }}">{{ auth()->check() ? 'Kembali ke ringkasan' : 'Masuk ke akun' }}</a>
    </section>
</main>
</body>
</html>
