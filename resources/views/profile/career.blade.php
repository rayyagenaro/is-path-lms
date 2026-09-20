@extends('layouts.app')

@section('title', 'Profil Karier')
@section('eyebrow', 'Profil Karier')

@section('content')
@if(session('success'))
    <div class="success-banner" role="status">
        <svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20"><path d="m5 12 4 4L19 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div><strong>Selamat datang di IS-Path</strong><p>{{ session('success') }}</p></div>
    </div>
@endif

<section class="profile-hero" aria-labelledby="profile-title">
    <div>
        <span class="eyebrow">Assessment minat awal</span>
        <h1 id="profile-title">Kenali cara belajarmu.<br><em>Mulai</em> dari arah yang masuk akal.</h1>
        <p>Jawaban ini membentuk profil minat dan arah eksplorasi belajar. Rekomendasi karier baru dibuka setelah kompetensimu diukur.</p>
    </div>
    <div class="profile-formula" aria-label="Komposisi perhitungan rekomendasi">
        <span>80%</span><small>Bukti terverifikasi</small><i aria-hidden="true">+</i><span>20%</span><small>Profil kekuatan diri</small>
    </div>
</section>

<form method="POST" action="{{ route('career-profile.update') }}" class="career-profile-form" data-submit-loading>
    @csrf
    @if($errors->any())
        <div class="form-errors" role="alert" tabindex="-1">
            <strong>Mohon periksa kembali profilmu:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="card form-section" aria-labelledby="interest-title">
        <div class="section-number">01</div>
        <div class="section-copy">
            <span class="eyebrow">Arah minat</span>
            <h2 id="interest-title">Bidang apa yang membuatmu penasaran?</h2>
            <p>Pilih bidang yang ingin kamu eksplorasi. Pilihan ini dapat diubah kapan saja.</p>
        </div>
        <div class="interest-options" role="radiogroup" aria-labelledby="interest-title">
            @foreach(['Data & Analytics' => 'Data, insight, dan keputusan', 'Business & Information Systems' => 'Proses, kebutuhan, dan solusi', 'Software & Product Development' => 'Kode, kualitas, dan produk digital', 'Infrastructure & Cloud' => 'Sistem, jaringan, dan komputasi awan', 'Cybersecurity & Governance' => 'Risiko, keamanan, dan kepatuhan', 'IT Management' => 'Pelaksanaan, layanan, dan tim', 'Architecture' => 'Rancangan solusi lintas sistem', 'Emerging Technology & Consulting' => 'AI, otomasi, dan konsultasi'] as $label => $copy)
                <label class="choice-card">
                    <input type="radio" name="primary_interest" value="{{ $label }}" @checked(old('primary_interest', $profile->primary_interest ?? '') === $label) required>
                    <span><b>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</b><strong>{{ $label }}</strong><small>{{ $copy }}</small></span>
                </label>
            @endforeach
        </div>
    </section>

    <section class="card form-section" aria-labelledby="work-style-title">
        <div class="section-number">02</div>
        <div class="section-copy">
            <span class="eyebrow">Gaya kerja</span>
            <h2 id="work-style-title">Kapan kamu bekerja paling baik?</h2>
            <p>Pilih kecenderungan yang paling menggambarkan dirimu saat ini.</p>
        </div>
        <div class="work-options" role="radiogroup" aria-labelledby="work-style-title">
            @foreach(['analytical' => ['Analitis', 'Membongkar masalah dengan data dan logika'], 'collaborative' => ['Kolaboratif', 'Bertukar ide dan bergerak bersama tim'], 'creative' => ['Eksploratif', 'Mencoba pendekatan dan gagasan baru'], 'structured' => ['Terstruktur', 'Bekerja dengan proses dan target yang jelas']] as $value => $option)
                <label>
                    <input type="radio" name="work_style" value="{{ $value }}" @checked(old('work_style', $profile->work_style ?? '') === $value) required>
                    <span><i aria-hidden="true"></i><strong>{{ $option[0] }}</strong><small>{{ $option[1] }}</small></span>
                </label>
            @endforeach
        </div>
    </section>

    <section class="card form-section strengths-section" aria-labelledby="strength-title">
        <div class="section-number">03</div>
        <div class="section-copy">
            <span class="eyebrow">Peta kekuatan</span>
            <h2 id="strength-title">Nilai kemampuanmu saat ini.</h2>
            <p>Opsional. Geser hanya kompetensi yang ingin kamu nilai berdasarkan pengalamanmu. Slider yang belum disentuh tidak disimpan sebagai penilaian. Penilaian diri melengkapi hasil assessment.</p>
        </div>
        <div class="strength-groups">
            @forelse($competencies->groupBy('category_group') as $group => $items)
                @php $groupLabels = ['Technical' => 'Teknis', 'Business' => 'Bisnis', 'Professional' => 'Profesional']; @endphp
                <details {{ $loop->first ? 'open' : '' }}>
                    <summary>{{ $groupLabels[$group] ?? $group }} <span>{{ $items->count() }} kompetensi</span></summary>
                    <div class="strength-inputs">
                        @foreach($items as $skill)
                            @php
                                $savedRating = old('strengths.'.$skill->id, $ratings[$skill->id] ?? null);
                                $rating = $savedRating ?? 50;
                            @endphp
                            <label>
                                <div><strong>{{ $skill->name }}</strong><small>{{ $skill->category }}</small></div>
                                <input type="range" @if($savedRating !== null) name="strengths[{{ $skill->id }}]" @endif data-strength-name="strengths[{{ $skill->id }}]" min="0" max="100" step="5" value="{{ $rating }}" aria-label="Penilaian {{ $skill->name }}" aria-describedby="strength-output-{{ $skill->id }}" oninput="this.name = this.dataset.strengthName; this.nextElementSibling.value = this.value + '%'">
                                <output id="strength-output-{{ $skill->id }}">{{ $savedRating === null ? 'Belum dinilai' : $rating.'%' }}</output>
                            </label>
                        @endforeach
                    </div>
                </details>
            @empty
                <div class="empty-inline"><strong>Daftar kompetensi belum tersedia.</strong><p>Kamu tetap dapat menyimpan minat, gaya kerja, dan tujuan karier.</p></div>
            @endforelse
        </div>
    </section>

    <section class="card form-section goal-section" aria-labelledby="goal-title">
        <div class="section-number">04</div>
        <div class="section-copy">
            <span class="eyebrow">Tujuanmu</span>
            <h2 id="goal-title">Apa yang ingin kamu capai?</h2>
            <p>Opsional, tetapi membantu menjaga konteks perjalananmu.</p>
        </div>
        <label class="sr-only" for="career-goal">Tujuan karier</label>
        <textarea id="career-goal" name="career_goal" rows="4" maxlength="500" placeholder="Contoh: Saya ingin menjadi analis data yang membantu bisnis mengambil keputusan.">{{ old('career_goal', $profile->career_goal ?? '') }}</textarea>
    </section>

    <div class="profile-submit">
        <div><strong>Simpan profil minatmu</strong><span>Kamu dapat mengubah jawaban ini kapan saja.</span></div>
        <button class="btn btn-primary" type="submit">
            Simpan profil
            <svg aria-hidden="true" viewBox="0 0 24 24" width="17" height="17"><path d="m9 18 6-6-6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>
</form>
@endsection
