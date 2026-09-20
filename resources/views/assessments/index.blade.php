@extends('layouts.app')

@section('title', 'Assessment Kompetensi')
@section('eyebrow', 'Assessment Kompetensi')

@section('content')
<section class="page-intro assessment-intro" aria-labelledby="assessment-title">
    <span class="eyebrow">BUKTI KOMPETENSI</span>
    <h1 id="assessment-title">Ukur dari bukti, bukan perkiraan.</h1>
    <p>Pilih topik untuk mengukur pengetahuanmu. Hasil assessment membantu menentukan kompetensi yang perlu diperkuat dan materi untuk dipelajari berikutnya.</p>
</section>

@if($errors->any())
    <div class="form-errors assessment-error-summary" role="alert" tabindex="-1" data-error-summary>
        <strong>Assessment belum dapat dimulai.</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@php
    $activeAssessments = $assessments->filter(fn ($assessment) => $assessment->latest_attempt?->status !== 'graded')->values();
    $historyAssessments = $assessments->filter(fn ($assessment) => $assessment->latest_attempt?->status === 'graded')->values();
@endphp

<div class="assessment-index-layout">
    <section class="assessment-list" aria-labelledby="available-assessment-title">
        <div class="section-heading">
            <div>
                <span class="eyebrow">TERSEDIA SEKARANG</span>
                <h2 id="available-assessment-title">Assessment untukmu</h2>
            </div>
            <span class="muted">{{ $activeAssessments->count() }} tersedia</span>
        </div>

        @forelse($activeAssessments as $assessment)
            @php
                $latest = $assessment->latest_attempt;
                $isInProgress = $latest?->status === 'in_progress';
                $isCompleted = $latest?->status === 'graded';
            @endphp
            <article class="assessment-row">
                <div class="assessment-row-main">
                    <div class="assessment-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M7 3h10v4H7zM6 5H4v16h16V5h-2M8 12l2.5 2.5L16 9M8 18h8"/></svg>
                    </div>
                    <div>
                        <div class="assessment-row-labels">
                            <span class="tag">{{ $assessment->assessment_purpose === 'role_competency_assessment' ? 'Kompetensi peran' : ($assessment->assessment_purpose === 'career_diagnostic' ? 'Pengetahuan awal' : 'Pemetaan topik') }}</span>
                            @if($isInProgress)
                                <span class="status warning">Sedang dikerjakan</span>
                            @elseif($isCompleted)
                                <span class="status success">Selesai</span>
                            @else
                                <span class="status">Belum dimulai</span>
                            @endif
                        </div>
                        <h3>{{ $assessment->title }}</h3>
                        <p>{{ $assessment->assessment_purpose === 'role_competency_assessment' ? 'Meninjau kompetensi wajib dan pendukung untuk peran pilihanmu. Hasil ini adalah indikator belajar, bukan sertifikasi kesiapan kerja.' : ($assessment->assessment_purpose === 'career_diagnostic' ? 'Mengukur pengetahuan awal di beberapa bidang Sistem Informasi, bukan mengukur preferensi minat.' : 'Mengukur pengetahuan pada bidang ini untuk membantu menentukan materi yang perlu dipelajari.') }}</p>
                        <dl class="assessment-facts">
                            <div><dt>Pertanyaan</dt><dd>{{ $assessment->question_count }}</dd></div>
                            <div><dt>Estimasi</dt><dd>{{ $assessment->time_limit_minutes ?: max(5, (int) ceil($assessment->question_count * 0.75)) }} menit</dd></div>
                            <div><dt>Nilai acuan</dt><dd>{{ round($assessment->passing_score) }}/100</dd></div>
                            <div><dt>Percobaan</dt><dd>{{ $assessment->attempt_count }}/{{ $assessment->max_attempts }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="assessment-row-action">
                    @if($isInProgress)
                        <a class="btn btn-primary" href="{{ route('assessments.take', $latest->id) }}">
                            Lanjutkan assessment
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @elseif($assessment->can_attempt)
                        <form method="POST" action="{{ route('assessments.start', $assessment->id) }}" data-submit-loading data-loading-label="Menyiapkan assessment">
                            @csrf
                            <button class="btn btn-primary" type="submit">
                                Mulai assessment
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </form>
                    @else
                        <span class="assessment-limit">Batas percobaan tercapai</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty-state">
                <strong>Belum ada assessment aktif.</strong>
                <p>Assessment baru akan muncul ketika tersedia. Riwayat pengerjaanmu tetap tersimpan di bagian bawah.</p>
            </div>
        @endforelse
    </section>

    @if($historyAssessments->isNotEmpty())
        <section class="assessment-history" aria-labelledby="assessment-history-title">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">TERSIMPAN DI AKUNMU</span>
                    <h2 id="assessment-history-title">Riwayat assessment</h2>
                </div>
                <span class="muted">{{ $historyAssessments->sum(fn ($assessment) => $assessment->attempt_history->count()) }} pengerjaan</span>
            </div>
            <div class="assessment-history-list">
                @foreach($historyAssessments as $assessment)
                    <article class="assessment-history-group">
                        <div class="assessment-history-heading">
                            <div>
                                <span class="tag">{{ $assessment->assessment_purpose === 'career_diagnostic' ? 'Pengetahuan awal' : 'Pemetaan topik' }}</span>
                                <h3>{{ $assessment->title }}</h3>
                            </div>
                            <span class="status success">Selesai</span>
                        </div>
                        <div class="assessment-history-attempts">
                            @foreach($assessment->attempt_history as $attempt)
                                <div class="assessment-history-attempt">
                                    <span>Percobaan {{ $attempt->attempt_number }}</span>
                                    <time datetime="{{ \Illuminate\Support\Carbon::parse($attempt->submitted_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($attempt->submitted_at)->format('d M Y, H:i') }}</time>
                                    <strong>{{ round($attempt->score) }}/100</strong>
                                    <a class="btn btn-ghost" href="{{ route('assessments.result', $attempt->id) }}">Lihat Pembahasan</a>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <aside class="assessment-context" aria-labelledby="assessment-context-title">
        <span class="eyebrow">SETELAH SELESAI</span>
        <h2 id="assessment-context-title">Hasilnya dipakai untuk apa?</h2>
        <ol>
            <li><span>01</span><div><strong>Membentuk bukti</strong><p>Jawaban dinilai dan dicatat sebagai bukti pengetahuan.</p></div></li>
            <li><span>02</span><div><strong>Memperbarui profil</strong><p>Skor dan tingkat kompetensi dihitung ulang secara otomatis.</p></div></li>
            <li><span>03</span><div><strong>Memperjelas arah</strong><p>Kecocokan karier dan kebutuhan belajar memakai hasil yang sudah terukur.</p></div></li>
        </ol>
        <p class="assessment-note">Gunakan hasil ini sebagai titik awal belajar, bukan label kemampuan permanen.</p>
    </aside>
</div>
@endsection

