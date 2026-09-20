<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | IS-Path</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/is-path-v3.css') }}?v={{ filemtime(public_path('css/is-path-v3.css')) }}">
</head>
<body class="login-body">
<main class="login-main">
    <section class="login-story" aria-labelledby="login-story-title">
        <pre class="ascii-field" data-ascii-field aria-hidden="true"></pre>

        <a class="brand brand-light" href="{{ route('login') }}" aria-label="IS-Path, halaman masuk">
            <span class="brand-mark light" aria-hidden="true">IS</span>
            <span>IS-Path</span>
        </a>

        <div class="story-copy">
            <h1 id="login-story-title">Bangun kompetensi.<br>Tentukan arah kariermu.</h1>
            <p>Jelajahi pilihan karier, ukur kompetensimu, dan dapatkan jalur belajar yang sesuai dalam satu tempat.</p>
        </div>
    </section>

    <section class="login-panel" aria-labelledby="login-form-title">
        <div class="login-box">
            <a class="mobile-brand" href="{{ route('login') }}" aria-label="IS-Path, halaman masuk">
                <span class="brand-mark" aria-hidden="true">IS</span>
                <span>IS-Path</span>
            </a>

            <h2 id="login-form-title">Masuk ke IS-Path</h2>
            <p class="muted">Gunakan akun yang sudah terdaftar.</p>
            @if(session('session_notice'))
                <p class="form-errors" role="status">{{ session('session_notice') }}</p>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="login-form">
                @csrf

                <div class="form-field">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="nama@kampus.ac.id"
                        autocomplete="email"
                        inputmode="email"
                        required
                        autofocus
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    >
                    @error('email')
                        <p class="form-error error" id="email-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
                        <button
                            class="password-toggle"
                            type="button"
                            data-password-toggle
                            aria-label="Tampilkan kata sandi"
                            aria-controls="password"
                            aria-pressed="false"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M2.8 12s3.2-5 9.2-5 9.2 5 9.2 5-3.2 5-9.2 5-9.2-5-9.2-5Z"/>
                                <circle cx="12" cy="12" r="2.5"/>
                            </svg>
                            <span class="sr-only" data-password-toggle-label>Tampilkan kata sandi</span>
                        </button>
                    </div>
                    @error('password')
                        <p class="form-error error" id="password-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <p class="login-note">Sesi berakhir setelah 3 menit tanpa permintaan ke server. Jika diminta, masuk kembali untuk melanjutkan.</p>

                <button class="btn btn-primary btn-full" type="submit">
                    <span>Masuk</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="m9 6 6 6-6 6"/>
                    </svg>
                </button>
            </form>

            <p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a></p>

            <div class="divider" aria-hidden="true"><span>atau</span></div>
            <nav class="guest-entry" aria-label="Pratinjau aplikasi">
                <a href="{{ route('guest.preview') }}">Masuk sebagai guest</a>
            </nav>
            <p class="login-note">Guest hanya dapat melihat preview aplikasi, tanpa mengakses fitur.</p>
        </div>
    </section>
</main>

<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>
