@extends('layouts.app')

@section('title', $role->name)
@section('eyebrow', 'Eksplorasi Karier')

@section('content')
@php $readinessLabels = ['exploring' => 'Mulai menjelajah', 'beginner' => 'Tahap awal', 'developing' => 'Sedang berkembang', 'career_ready' => 'Siap berkarier', 'highly_ready' => 'Sangat siap']; @endphp
@if(session('success'))
    <div class="success-banner" role="status">
        <svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20"><path d="m5 12 4 4L19 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div><strong>Target karier diperbarui</strong><p>{{ session('success') }}</p></div>
    </div>
@endif
@if(session('error'))
    <div class="form-errors" role="alert"><strong>Target belum dapat ditetapkan</strong><p>{{ session('error') }}</p></div>
@endif

<a class="back" href="{{ route('careers') }}">
    <svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18"><path d="m15 18-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Kembali ke eksplorasi karier
</a>

<section class="career-detail-hero" aria-labelledby="career-title">
    <div>
        <div class="role-meta">
            <span>{{ $role->cluster_name }}</span>
        </div>
        <h1 id="career-title">{{ $role->name }}</h1>
        <p>{{ $role->description }}</p>
        <div class="hero-actions">
            @if(!$hasPostAssessment)
                <a class="btn btn-primary" href="#next-course-title">Lihat kelas eksplorasi</a>
                <a class="btn btn-outline" href="{{ route('assessments.index') }}">Buka post-assessment</a>
            @elseif($role->interest_type === 'target_active')
                <span class="target-badge">
                    <svg aria-hidden="true" viewBox="0 0 24 24" width="17" height="17"><path d="m5 12 4 4L19 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Target aktif
                </span>
            @else
                <form method="POST" action="{{ route('careers.target', $role->slug) }}" data-submit-loading>
                    @csrf
                    <button class="btn btn-primary" type="submit">Jadikan target utama</button>
                </form>
            @endif
            @if($hasPostAssessment)
                <a class="btn btn-outline" href="#learning-path">Lihat jalur belajar</a>
            @endif
        </div>
    </div>
    @if($hasPostAssessment)
    <div class="score-pair" aria-label="Ringkasan kecocokan karier">
        <div><small>{{ $hasPostAssessment ? 'Kecocokan' : 'Kecocokan awal' }}</small><strong>{{ $role->match['score'] }}%</strong><span>{{ $hasPostAssessment ? 'Profil dan bukti kemampuan' : 'Berdasarkan profil mandiri' }}</span></div>
        <div class="primary"><small>Kesiapan</small><strong>{{ $hasPostAssessment ? $role->readiness['score'].'%' : 'Belum' }}</strong><span>{{ $hasPostAssessment ? ($readinessLabels[$role->readiness['status_key'] ?? ''] ?? $role->readiness['status']) : 'Selesaikan assessment' }}</span></div>
    </div>
    @else
    <div class="score-pair" aria-label="Status rekomendasi karier">
        <div class="primary"><small>Mode eksplorasi</small><strong>Belum</strong><span>Rekomendasi terbuka setelah post-assessment</span></div>
    </div>
    @endif
</section>

@if($hasPostAssessment && $role->readiness['has_blocking_gap'])
    <div class="blocking-note" role="note">
        <strong>Ada kompetensi wajib yang perlu dikuatkan</strong>
        <span>Skor kesiapan dibatasi sampai kompetensi yang tertinggal dua tingkat atau lebih berhasil ditingkatkan.</span>
    </div>
@endif

<div class="career-detail-grid">
    <section class="card readiness-card span-2" aria-labelledby="readiness-title">
        <div class="card-head">
            <div><span class="eyebrow">Kesiapan karier</span><h2 id="readiness-title">Jarak kompetensimu</h2></div>
            <strong class="readiness-total">{{ $hasPostAssessment ? $role->readiness['score'].'%' : 'Belum' }}</strong>
        </div>
        @if(!$hasPostAssessment)
            <div class="empty-inline">
                <strong>Kesiapan belum diukur.</strong>
                <p>Assessment mengubah penilaian awal menjadi gap kompetensi yang dapat dijelaskan dan ditindaklanjuti.</p>
                <a class="btn btn-primary" href="{{ route('assessments.index') }}">Buka assessment</a>
            </div>
        @else
        <div class="readiness-list">
            @forelse($role->requirements as $item)
                @php $gapLabels = ['ready' => 'Siap', 'minor_gap' => 'Perlu diperkuat', 'major_gap' => 'Gap utama']; @endphp
                <div class="readiness-row">
                    <div><strong>{{ $item->name }}</strong><span>Level {{ $item->student_level }}/5 saat ini · target {{ $item->minimum_level }}/5</span></div>
                    <div class="progress" role="progressbar" aria-label="Kemajuan {{ $item->name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $item->progress }}"><i class="progress-fill" style="width: {{ $item->progress }}%"></i></div>
                    <span class="gap-label {{ $item->gap_status }}">{{ $gapLabels[$item->gap_status] ?? 'Perlu ditinjau' }}</span>
                    @if($item->reused)<em title="Kompetensi ini berlaku untuk berbagai pilihan karier">Sudah terpenuhi dari profil kompetensi</em>@endif
                </div>
            @empty
                <div class="empty-inline"><strong>Persyaratan kompetensi belum tersedia.</strong><p>Tim akademik sedang melengkapi peta kompetensi untuk peran ini.</p></div>
            @endforelse
        </div>
        @endif
    </section>

    <aside class="card role-facts" aria-labelledby="role-facts-title">
        <span class="eyebrow">Gambaran peran</span>
        <h2 id="role-facts-title">Yang akan dikerjakan</h2>
        @foreach($role->responsibilities as $item)
            <p><svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18"><path d="m5 12 4 4L19 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $item }}</p>
        @endforeach
        <hr>
        <small>Perangkat dan domain</small>
        <div class="skill-chips">@foreach($role->tools as $item)<span>{{ $item }}</span>@endforeach</div>
    </aside>
</div>

@if($hasPostAssessment)
<section class="card learning-path-section" id="learning-path" aria-labelledby="learning-path-title">
    <div class="card-head">
        <div><span class="eyebrow">Jalur belajar adaptif</span><h2 id="learning-path-title">Langkah yang relevan untukmu</h2></div>
        <span class="status success">Disusun untukmu</span>
    </div>
    @if($pathItems->isNotEmpty())
        <div class="path-rail">
            @foreach($pathItems as $item)
                @php
                    $status = $item->display_status ?? $item->status;
                    $pathLabels = ['completed' => 'Kelas selesai', 'in_progress' => 'Sedang dipelajari', 'already_competent' => 'Sudah dikuasai', 'recommended' => 'Direkomendasikan', 'required' => 'Penting'];
                @endphp
                <div class="path-item {{ $status }}">
                    <span class="path-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <div><small>{{ $pathLabels[$status] ?? ucwords(str_replace('_', ' ', $status)) }}</small><strong>{{ $item->course_code }} · {{ $item->course_title }}</strong><p>Memperkuat {{ $item->competency_name }}</p></div>
                    @if($status === 'completed')
                        <span class="path-action">Selesai dari progres kelas</span>
                    @elseif($status === 'already_competent')
                        <span class="path-action">Opsional, kompetensi sudah cukup</span>
                    @elseif($item->course_slug)
                        <a href="{{ route('courses.show', $item->course_slug) }}">{{ $status === 'in_progress' ? 'Lanjutkan kelas' : 'Buka kelas' }} <svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16"><path d="m9 18 6-6-6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-inline"><strong>Jalur belajar lengkap belum tersedia.</strong><p>Rekomendasi kelas di bawah tetap disusun dari kompetensi yang belum terpenuhi.</p></div>
    @endif
</section>
@endif

<section aria-labelledby="next-course-title">
    <div class="section-heading">
        <div><span class="eyebrow">{{ $hasPostAssessment ? 'Berdasarkan kebutuhan kompetensi' : 'Untuk eksplorasi bidang' }}</span><h2 id="next-course-title">Kelas berikutnya</h2></div>
        <a href="{{ route('courses') }}">Lihat semua kelas <svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16"><path d="m9 18 6-6-6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
    </div>
    <div class="compact-course-grid">
        @forelse($role->recommended_courses as $course)
            <a href="{{ route('courses.show', $course->slug) }}"><span>{{ $course->code }}</span><div><strong>{{ $course->title }}</strong><small>Memperkuat {{ $course->closes_gap }}</small></div><svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18"><path d="m9 18 6-6-6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        @empty
            <div class="empty-inline"><strong>Belum ada saran kelas untuk ditampilkan.</strong><p>Periksa rincian kompetensi di atas atau buka katalog untuk memilih materi yang relevan.</p></div>
        @endforelse
    </div>
</section>
@endsection
