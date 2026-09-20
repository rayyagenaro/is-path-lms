@extends('layouts.app')

@section('title', 'Hasil Assessment')
@section('eyebrow', 'Hasil Assessment')

@section('content')
<span hidden data-completed-draft="ispath:assessment:{{ auth()->id() }}:{{ $attempt->id }}"></span>
@php
    $score = (int) round($attempt->score);
    $passed = $score >= $attempt->passing_score;
@endphp

@if(session('success'))
    <div class="success-banner" role="status">
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>
        <div><strong>Profil kompetensi diperbarui</strong><p>{{ session('success') }}</p></div>
    </div>
@endif

<a class="back" href="{{ route('assessments.index') }}">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    <span>Kembali ke daftar assessment</span>
</a>

<section class="assessment-result-hero" aria-labelledby="result-title">
    <div class="assessment-result-copy">
        <span class="eyebrow">{{ $attempt->assessment_purpose === 'role_competency_assessment' ? 'ROLE ASSESSMENT SELESAI' : 'PEMETAAN KOMPETENSI SELESAI' }}</span>
        <h1 id="result-title">{{ $attempt->title }}</h1>
        <p>{{ $passed ? 'Fondasi utamamu sudah terlihat. Gunakan rincian hasil untuk menentukan kompetensi yang perlu dipertahankan dan diperkuat.' : 'Hasil ini adalah titik awal, bukan batas kemampuan. Fokuskan pembelajaran pada kompetensi dengan gap terbesar.' }}</p>
        <div class="assessment-result-actions">
            <a class="btn btn-primary" href="{{ route('competencies') }}">Lihat profil kompetensi</a>
            <a class="btn btn-outline" href="{{ route('careers') }}">Periksa kecocokan karier</a>
        </div>
    </div>
    <div class="assessment-score-block">
        <span>{{ $attempt->assessment_purpose === 'role_competency_assessment' ? 'SKOR KESIAPAN ROLE' : 'SKOR PEMETAAN KOMPETENSI' }}</span>
        <strong>{{ $score }}<small>/100</small></strong>
        <p class="{{ $passed ? 'result-pass' : 'result-develop' }}">{{ $passed ? 'Fondasi tercapai' : 'Perlu penguatan' }}</p>
        <div class="progress" role="progressbar" aria-label="Skor assessment" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $score }}">
            <i class="progress-fill" style="--progress-scale: {{ $score / 100 }}"></i>
        </div>
    </div>
</section>

<div class="assessment-result-layout">
    <section class="card assessment-breakdown" aria-labelledby="breakdown-title">
        <div class="card-head">
            <div><span class="eyebrow">PER KOMPETENSI</span><h2 id="breakdown-title">Kekuatan dan area belajar</h2></div>
            <span class="muted">{{ $breakdown->count() }} kompetensi terukur</span>
        </div>
        <div class="assessment-breakdown-list">
            @foreach($breakdown as $item)
                <div class="assessment-breakdown-row">
                    <div><strong>{{ $item->name }}</strong><small>{{ $item->correct }} dari {{ $item->total }} jawaban tepat</small></div>
                    <div class="progress" role="progressbar" aria-label="Hasil {{ $item->name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $item->score }}"><i class="progress-fill" style="--progress-scale: {{ $item->score / 100 }}"></i></div>
                    <b>{{ $item->score }}%</b>
                </div>
            @endforeach
        </div>
    </section>

    @if($roleReadiness->isNotEmpty())
        <section class="card assessment-breakdown" aria-labelledby="role-readiness-title">
            <div class="card-head">
                <div><span class="eyebrow">Kesiapan peran</span><h2 id="role-readiness-title">Kompetensi yang perlu diperkuat</h2></div>
                <span class="muted">Kompetensi wajib dan pendukung</span>
            </div>
            <div class="assessment-breakdown-list">
                @foreach($roleReadiness as $item)
                    <div class="assessment-breakdown-row">
                        <div>
                            <strong>{{ $item->name }}</strong>
                            <small>{{ ucfirst($item->requirement_type) }} · target {{ $item->minimum_score }}% · {{ $item->ready ? 'siap' : 'gap '.$item->gap.'%' }}</small>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Kesiapan {{ $item->name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $item->score }}"><i class="progress-fill" style="--progress-scale: {{ $item->score / 100 }}"></i></div>
                        <b>{{ $item->score }}%</b>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <aside class="assessment-result-next">
        <span class="eyebrow">LANGKAH BERIKUTNYA</span>
        <h2>Jadikan hasil ini keputusan belajar.</h2>
        <p>IS-Path sudah mencatat hasil sebagai bukti. Kecocokan karier akan menggabungkannya dengan profil kekuatanmu.</p>
                <a class="text-link" href="{{ route('careers') }}">Lihat rekomendasi terbaru</a>
    </aside>
</div>

<section class="assessment-review" aria-labelledby="review-title">
    <div class="section-heading">
        <div><span class="eyebrow">PEMBAHASAN</span><h2 id="review-title">Kunci jawaban dan pembahasan</h2></div>
        <span class="muted">Cek jawabanmu, kunci benar, dan alasan tiap soal.</span>
    </div>
    <div class="assessment-review-list">
        @foreach($answers as $answer)
            <article class="assessment-review-item {{ $answer->is_correct ? 'is-correct' : 'is-wrong' }}">
                <div class="assessment-review-question">
                <span class="review-index">{{ str_pad($answer->position, 2, '0', STR_PAD_LEFT) }}</span>
                <strong>{{ $answer->prompt }}</strong>
                <span class="review-status {{ $answer->is_correct ? 'is-correct' : 'is-wrong' }}">{{ $answer->is_correct ? 'Tepat' : 'Perlu ditinjau' }}</span>
                </div>
                <div class="assessment-review-body">
                    <p><span>Jawabanmu</span><strong>{{ $answer->selected_label }}</strong></p>
                    <p><span>Kunci jawaban</span><strong>{{ $answer->correct_label }}</strong></p>
                    <p class="review-explanation"><span>Penjelasan</span><strong>{{ $answer->feedback }}</strong></p>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endsection
