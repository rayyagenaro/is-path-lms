@extends('layouts.app')

@section('title', 'Mulai Pre-Assessment')

@section('content')
@if(session('success'))
    <div class="success-banner" role="status">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
        <div><strong>Akunmu sudah siap</strong><p>{{ session('success') }}</p></div>
    </div>
@endif

<section class="onboarding-entry" aria-labelledby="onboarding-entry-title">
    <div class="onboarding-entry-copy">
        <div class="onboarding-entry-navigation">
            <button class="back" type="submit" form="onboarding-logout">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                <span>Kembali ke login</span>
            </button>
        </div>
        <form id="onboarding-logout" method="POST" action="{{ route('logout') }}" hidden>@csrf</form>

        <span class="eyebrow">LANGKAH PERTAMA</span>
        <h1 id="onboarding-entry-title">Petakan kemampuan awal sebelum memilih kelas.</h1>
        <p class="onboarding-entry-lead">Pre-assessment mengukur pengetahuan awal di bidang Sistem Informasi. Hasilnya membantu memilih peran untuk dieksplorasi. Minat dan gaya kerja dapat dilengkapi pada profil karier.</p>

        @if($assessment)
            <div class="onboarding-assessment-brief" aria-label="Ringkasan pre-assessment">
                <div>
                    <span>{{ $assessment->question_count }} pertanyaan</span>
                    <small>pilihan ganda</small>
                </div>
                <div>
                    <span>± {{ $assessment->time_limit_minutes ?: max(5, (int) ceil($assessment->question_count * .75)) }} menit</span>
                    <small>estimasi pengerjaan</small>
                </div>
                <div>
                    <span>1 hasil</span>
                    <small>dasar eksplorasi peran</small>
                </div>
            </div>
            <form method="POST" action="{{ route('onboarding.initial.start') }}" data-submit-loading data-loading-label="Menyiapkan pre-assessment">
                @csrf
                <button class="btn btn-primary" type="submit">Mulai pre-assessment</button>
            </form>
        @else
            <div class="form-errors" role="status">
                <strong>Pre-assessment belum tersedia.</strong>
                <p>Data pertanyaan belum dipublikasikan. Hubungi pengelola aplikasi sebelum melanjutkan.</p>
            </div>
        @endif
    </div>

    <aside class="onboarding-entry-notes" aria-label="Ringkasan pre-assessment">
        <div>
            <span>Selama pengerjaan</span>
            <strong>Jawab sesuai pemahamanmu saat ini</strong>
            <p>Tidak ada pengurangan nilai untuk jawaban yang keliru. Hasilnya dipakai untuk melihat titik awal belajar.</p>
        </div>
        <div>
            <span>Setelah selesai</span>
            <strong>Pilih peran yang ingin kamu eksplorasi</strong>
            <p>IS-Path mengurutkan pilihan berdasarkan hasil, lalu menyiapkan assessment kompetensi peran.</p>
        </div>
        <div>
            <span>Sesudah itu</span>
            <strong>Mulai dari kelas yang paling relevan</strong>
            <p>Learning path menjelaskan kompetensi yang perlu dibangun sebelum kamu mengukur kesiapan peran.</p>
        </div>
    </aside>
</section>

@if($showPrompt && $assessment)
<dialog class="preassessment-dialog" data-preassessment-dialog aria-labelledby="preassessment-dialog-title">
    <button class="icon-button preassessment-dialog-close" type="button" data-dialog-close aria-label="Tutup pengingat pre-assessment">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
    <span class="eyebrow">SEBELUM MEMULAI KELAS</span>
    <h2 id="preassessment-dialog-title">Selesaikan pre-assessment terlebih dahulu.</h2>
    <p>Hasil pengetahuan awal membantu mengurutkan pilihan peran dan kelas yang relevan.</p>
    <div class="preassessment-dialog-actions">
        <form method="POST" action="{{ route('onboarding.initial.start') }}" data-submit-loading data-loading-label="Menyiapkan pre-assessment">
            @csrf
            <button class="btn btn-primary" type="submit">Kerjakan sekarang</button>
        </form>
        <button class="btn btn-ghost" type="button" data-dialog-close>Baca penjelasan dulu</button>
    </div>
</dialog>
@endif
@endsection
