<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar · IS-Path</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/is-path-v3.css') }}?v={{ filemtime(public_path('css/is-path-v3.css')) }}">
</head>
<body class="login-body">
<main class="login-main register-main">
    <section class="login-story" aria-labelledby="register-story-title">
        <pre class="ascii-field" data-ascii-field data-ascii-seed="register" aria-hidden="true"></pre>

        <a class="brand brand-light" href="{{ route('login') }}" aria-label="IS-Path, halaman masuk">
            <span class="brand-mark light" aria-hidden="true">IS</span>
            <span>IS-Path</span>
        </a>

        <div class="story-copy">
            <h1 id="register-story-title">Mulai dari dirimu.<br>Bukan dari tebakan.</h1>
            <p>Isi identitas mahasiswa, lalu petakan minat dan kekuatanmu. IS-Path akan menyusun rekomendasi karier dan urutan belajar yang relevan.</p>
        </div>
    </section>

    <section class="login-panel register-panel" aria-labelledby="register-form-title">
        <div class="login-box register-box">
            <a class="mobile-brand" href="{{ route('login') }}" aria-label="IS-Path, halaman masuk">
                <span class="brand-mark" aria-hidden="true">IS</span>
                <span>IS-Path</span>
            </a>

            <span class="eyebrow">AKUN MAHASISWA</span>
            <h2 id="register-form-title">Buat akun IS-Path</h2>
            <p class="muted">Data ini digunakan untuk membuat profil belajarmu.</p>

            @if($errors->any())
                <div class="form-errors auth-errors" role="alert">
                    <strong>Ada data yang perlu diperbaiki.</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="login-form register-form" data-submit-loading data-loading-label="Membuat akun mahasiswa">
                @csrf

                <div class="form-field register-span-2">
                    <label for="name">Nama lengkap</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Nama sesuai identitas" autocomplete="name" required autofocus @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')<p class="form-error error" id="name-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field register-span-2">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@kampus.ac.id" autocomplete="email" inputmode="email" required @error('email') aria-invalid="true" aria-describedby="register-email-error" @enderror>
                    @error('email')<p class="form-error error" id="register-email-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="nim">NIM</label>
                    <input id="nim" type="text" name="nim" value="{{ old('nim') }}" placeholder="12345678910" autocomplete="off" required @error('nim') aria-invalid="true" aria-describedby="nim-error" @enderror>
                    @error('nim')<p class="form-error error" id="nim-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="cohort_year">Tahun Angkatan</label>
                    <input id="cohort_year" type="number" name="cohort_year" value="{{ old('cohort_year') }}" min="2000" max="{{ now()->year + 1 }}" placeholder="2024" inputmode="numeric" required @error('cohort_year') aria-invalid="true" aria-describedby="cohort-error" @enderror>
                    @error('cohort_year')<p class="form-error error" id="cohort-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field register-span-2">
                    <label for="study_program">Program Studi</label>
                    <input id="study_program" type="text" name="study_program" value="{{ old('study_program', 'Sistem Informasi') }}" autocomplete="organization-title" required @error('study_program') aria-invalid="true" aria-describedby="study-program-error" @enderror>
                    @error('study_program')<p class="form-error error" id="study-program-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="register-password">Kata Sandi</label>
                    <div class="password-field">
                        <input id="register-password" type="password" name="password" autocomplete="new-password" minlength="8" aria-describedby="password-help @error('password') register-password-error @enderror" required @error('password') aria-invalid="true" @enderror>
                        <button class="password-toggle" type="button" data-password-toggle aria-label="Tampilkan kata sandi" aria-controls="register-password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.8 12s3.2-5 9.2-5 9.2 5 9.2 5-3.2 5-9.2 5-9.2-5-9.2-5Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </div>
                    <small id="password-help" class="field-help">Minimal 8 karakter.</small>
                    @error('password')<p class="form-error error" id="register-password-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="password_confirmation">Ulangi Kata Sandi</label>
                    <div class="password-field">
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                        <button class="password-toggle" type="button" data-password-toggle aria-label="Tampilkan ulang kata sandi" aria-controls="password_confirmation" aria-pressed="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.8 12s3.2-5 9.2-5 9.2 5 9.2 5-3.2 5-9.2 5-9.2-5-9.2-5Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </div>
                </div>

                <button class="btn btn-primary btn-full register-span-2" type="submit">
                    <span>Buat akun mahasiswa</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"/></svg>
                </button>
            </form>

            <p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk ke IS-Path</a></p>
        </div>
    </section>
</main>

<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>
