@extends('layouts.app')
@section('title', 'Bukti '.$skill->name)
@section('content')
@if(session('success'))<p class="success-banner" role="status">{{ session('success') }}</p>@endif
@if($errors->any())<div class="form-errors" role="alert"><strong>Referensi belum tersimpan.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<a class="back" href="{{ route('competencies') }}">Kembali ke kompetensi</a>
<header class="page-intro">
    <span class="eyebrow">Sumber penilaian</span>
    <h1>{{ $skill->name }}</h1>
    <p>Periksa hasil yang membentuk nilai kompetensimu, lalu pilih asesmen atau materi yang relevan.</p>
</header>
<div class="evidence-layout">
    <section aria-labelledby="evidence-title">
        <div class="section-heading"><h2 id="evidence-title">Riwayat bukti</h2><span>{{ $valid->count() }} bukti berlaku</span></div>
        @forelse($evidence as $item)
            @php
                $expired = $item->valid_until && now()->gt($item->valid_until);
                $attempt = $item->source_type === 'assessment_attempt' ? $attempts->get($item->source_id) : null;
                $labels = ['technical_assessment' => 'Hasil asesmen', 'course_performance' => 'Hasil kelas', 'project' => 'Proyek', 'self_assessment' => 'Penilaian diri', 'lecturer_assessment' => 'Penilaian dosen'];
            @endphp
            <article class="evidence-row">
                <h3>{{ $attempt?->title ?? ($labels[$item->evidence_type] ?? 'Bukti kompetensi') }}</h3>
                <p>{{ \Illuminate\Support\Carbon::parse($item->earned_at)->translatedFormat('d F Y') }} · {{ $expired ? 'Masa berlaku berakhir' : 'Berlaku' }}</p>
                <dl class="evidence-values"><div><dt>Nilai</dt><dd>{{ (float) $item->score }}/100</dd></div><div><dt>Bobot</dt><dd>{{ (float) $item->weight }}</dd></div></dl>
                @if($item->valid_until)<p>Berlaku hingga {{ \Illuminate\Support\Carbon::parse($item->valid_until)->translatedFormat('d F Y') }}.</p>@endif
                @if($attempt)<a class="btn btn-outline" href="{{ route('assessments.result', $attempt->id) }}">Lihat pembahasan</a>@endif
            </article>
        @empty
            <div class="empty-inline"><strong>Belum ada hasil tercatat.</strong><p>Kerjakan asesmen yang mengukur {{ $skill->name }} untuk mendapatkan bukti pertama.</p></div>
        @endforelse
        <details class="evidence-method">
            <summary>Cara menghitung nilai dan kekuatan bukti</summary>
            <p>Penguasaan adalah rata-rata nilai dengan bobot setiap bukti yang masih berlaku. Jika seluruh bukti berasal dari penilaian diri, nilai dibatasi hingga 40/100.</p>
            <p>Kekuatan bukti menggabungkan jumlah bukti (maksimal 40 poin, target {{ $skill->expected_evidence_count }} bukti), variasi jenis (6 poin per jenis, maksimal 30), dan kebaruan (30 poin di bawah 6 bulan, 20 hingga 12 bulan, lalu 10).</p>
            <p>Dari bukti yang berlaku saat ini: penguasaan {{ $calculated['score'] }}%, kekuatan bukti {{ $calculated['confidence'] }}%. Level 5 mencakup skor di atas 80; level 5 tidak selalu berarti nilai 100%.</p>
            <p>Asesmen merupakan bukti pengetahuan. Menandai materi selesai atau mengulang asesmen tidak otomatis membuktikan kemampuan praktik maupun menambah jenis bukti.</p>
        </details>
        @foreach($projects as $project)
            <section class="evidence-project" aria-labelledby="project-{{ $project->id }}">
                <h2 id="project-{{ $project->id }}">{{ $project->title }}</h2>
                <p>{{ $project->description }}</p>
                <p>Simpan tautan hasil dan catatan kontribusimu sebagai referensi portofolio. Penilaian proyek belum tersedia; penyimpanan ini belum menambah skor atau kekuatan bukti.</p>
                @foreach($submissions->get($project->id, collect()) as $submission)
                    <details class="evidence-row"><summary>Referensi {{ \Illuminate\Support\Carbon::parse($submission->created_at)->translatedFormat('d F Y') }} · {{ $submission->reviewed_at ? 'Sudah ditinjau' : 'Belum dinilai' }}</summary>
                        <p>{{ $submission->student_note }}</p>
                        @if(preg_match('/^https?:\/\//i', $submission->submission_ref))<a class="evidence-course-link" href="{{ $submission->submission_ref }}" target="_blank" rel="noopener noreferrer">Buka hasil proyek (tab baru)</a>@endif
                        @if($submission->review_note)<p>{{ $submission->review_note }}</p>@endif
                    </details>
                @endforeach
                <form method="POST" action="{{ route('competencies.projects.store', [$skill->id, $project->id]) }}" class="evidence-project-form" data-submit-loading data-loading-label="Menyimpan referensi">
                    @csrf
                    <label for="project-url-{{ $project->id }}">Tautan hasil proyek</label>
                    <input id="project-url-{{ $project->id }}" type="url" name="submission_ref" value="{{ old('submission_ref') }}" placeholder="https://github.com/username/proyek" maxlength="255" required>
                    <label for="project-note-{{ $project->id }}">Kontribusi dan hasil yang kamu kerjakan</label>
                    <textarea id="project-note-{{ $project->id }}" name="student_note" rows="4" maxlength="3000" required>{{ old('student_note') }}</textarea>
                    <button type="submit" class="btn btn-outline">Simpan referensi proyek</button>
                </form>
            </section>
        @endforeach
    </section>
    <aside aria-labelledby="evidence-next-title">
        <h2 id="evidence-next-title">Langkah berikutnya</h2>
        @forelse($available as $assessment)
            <article class="evidence-row">
                <h3>{{ $assessment->title }}</h3><p>{{ $assessment->question_count }} pertanyaan · {{ $assessment->max_attempts - $assessment->attempt_count }} kesempatan baru tersisa</p>
                @if($assessment->latest_attempt?->status === 'in_progress')
                    <a class="btn btn-primary" href="{{ route('assessments.take', $assessment->latest_attempt->id) }}">Lanjutkan asesmen</a>
                @else
                    <form method="POST" action="{{ route('assessments.start', $assessment->id) }}" data-submit-loading data-loading-label="Menyiapkan asesmen">@csrf<button class="btn btn-primary" type="submit">Kerjakan asesmen</button></form>
                @endif
            </article>
        @empty
            <p>Belum ada asesmen relevan yang bisa dimulai untuk pilihan peranmu, atau kesempatan pengerjaan telah habis.</p>
        @endforelse
        @if($courses->isNotEmpty())
            <h3 class="evidence-course-heading">Materi untuk berlatih</h3>
            <p>Pelajari materinya, kemudian ukur pengetahuanmu lewat asesmen.</p>
            @foreach($courses as $course)<a class="evidence-course-link" href="{{ route('courses.show', $course->slug) }}">{{ $course->title }}</a>@endforeach
        @endif
    </aside>
</div>
@endsection
