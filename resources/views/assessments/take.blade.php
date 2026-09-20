@extends('layouts.app')

@section('title', $attempt->title)
@section('eyebrow', 'Pengerjaan Assessment')

@section('content')
<a class="back" href="{{ route('assessments.index') }}">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    <span>Kembali ke daftar assessment</span>
</a>

<header class="assessment-take-header" aria-labelledby="take-title">
    <div>
        <span class="eyebrow">PERCOBAAN {{ $attempt->attempt_number }} DARI {{ $attempt->max_attempts }}</span>
        <h1 id="take-title">{{ $attempt->title }}</h1>
        <p>Pilih satu jawaban yang paling tepat. Baca konteksnya dengan tenang. Jawaban yang keliru tidak mengurangi nilai jawaban lain.</p>
    </div>
    <div class="assessment-header-meta" aria-label="Informasi assessment">
        <span>{{ $questions->count() }} pertanyaan</span>
        <span>Estimasi {{ $attempt->time_limit_minutes ?: max(5, (int) ceil($questions->count() * 0.75)) }} menit</span>
    </div>
</header>

@if($errors->any())
    <div class="form-errors assessment-error-summary" role="alert" tabindex="-1" data-error-summary>
        <strong>Masih ada jawaban yang perlu diperiksa.</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('assessments.submit', $attempt->id) }}" class="assessment-form" data-assessment-form data-draft-url="{{ route('assessments.draft', $attempt->id) }}" data-session-seconds="{{ (int) config('session.lifetime') * 60 }}" data-draft-key="ispath:assessment:{{ auth()->id() }}:{{ $attempt->id }}" data-server-draft-at="{{ $draftSavedAt ? \Illuminate\Support\Carbon::parse($draftSavedAt)->toIso8601String() : '' }}" data-submit-loading data-loading-label="Menilai jawaban">
    @csrf
    <div class="assessment-workspace-main">
        <div class="assessment-page-heading">
            <div>
                <span data-assessment-section>Bagian 1 dari {{ $questions->chunk(5)->count() }}</span>
                <strong data-assessment-range>Soal 1-{{ min(5, $questions->count()) }}</strong>
            </div>
            <p data-draft-status role="status">{{ $draftSavedAt ? 'Draft dari akunmu telah dipulihkan.' : 'Jawaban disimpan otomatis setelah kamu memilih.' }}</p>
        </div>

        <div class="assessment-question-list">
            @foreach($questions->chunk(5) as $pageIndex => $pageQuestions)
                <section class="assessment-page {{ $pageIndex === 0 ? 'is-active' : '' }}" data-assessment-page="{{ $pageIndex }}" @if($pageIndex !== 0) hidden @endif aria-label="Bagian {{ $pageIndex + 1 }}">
                    @foreach($pageQuestions as $question)
                        @php($questionNumber = ($pageIndex * 5) + $loop->iteration)
                        <div class="assessment-question" data-assessment-question data-question-number="{{ $questionNumber }}" role="group" aria-labelledby="question-{{ $question->id }}" tabindex="-1">
                          <fieldset class="assessment-question-group">
                            <legend class="assessment-question-title" id="question-{{ $question->id }}">
                                <span>{{ str_pad($questionNumber, 2, '0', STR_PAD_LEFT) }}</span>
                                <strong>{{ $question->prompt }}</strong>
                            </legend>
                            <div class="assessment-options">
                                @foreach($question->options as $value => $label)
                                    <label class="assessment-option">
                                        <input
                                            type="radio"
                                            name="answers[{{ $question->id }}]"
                                            value="{{ $value }}"
                                            @checked(old('answers.'.$question->id, $draftAnswers[$question->id] ?? null) === $value)
                                        >
                                        <span><b aria-hidden="true">{{ chr(65 + $loop->index) }}</b><em>{{ $label }}</em></span>
                                    </label>
                                @endforeach
                            </div>
                          </fieldset>
                        </div>
                    @endforeach
                </section>
            @endforeach
        </div>

        <nav class="assessment-page-controls" aria-label="Navigasi bagian assessment">
            <button class="btn btn-outline" type="button" data-assessment-previous disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Bagian sebelumnya
            </button>
            <button class="btn btn-primary" type="button" data-assessment-next>
                Bagian berikutnya
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </nav>
    </div>

    <aside class="assessment-submit-panel">
        <p data-session-notice role="status" hidden></p>
        <div class="assessment-live-progress">
            <div>
                <span>PROGRES JAWABAN</span>
                <strong><b data-answered-count>0</b>/{{ $questions->count() }}</strong>
            </div>
            <div class="progress" role="progressbar" aria-label="Pertanyaan yang sudah dijawab" aria-valuemin="0" aria-valuemax="{{ $questions->count() }}" aria-valuenow="0" data-assessment-progress>
                <i class="progress-fill is-filled" style="--progress-scale: 0"></i>
            </div>
            <p data-assessment-status aria-live="polite">Jawab seluruh pertanyaan sebelum mengirim hasil.</p>
        </div>

        <div class="assessment-tracker" aria-labelledby="assessment-tracker-title">
            <strong id="assessment-tracker-title">Nomor soal</strong>
            <div class="assessment-tracker-grid">
                @foreach($questions as $question)
                    <button type="button" data-question-jump="{{ $loop->iteration }}" aria-label="Buka soal {{ $loop->iteration }}">{{ $loop->iteration }}</button>
                @endforeach
            </div>
            <div class="assessment-tracker-legend">
                <span><i class="is-current" aria-hidden="true"></i>Soal aktif</span>
                <span><i class="is-answered" aria-hidden="true"></i>Sudah dijawab</span>
            </div>
        </div>

        <button class="btn btn-primary btn-full" type="submit" data-assessment-submit disabled>
            Kirim dan lihat hasil
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
        </button>
        <small>Kirim aktif setelah seluruh soal terjawab. Jawaban tidak dapat diubah setelah dikirim.</small>
    </aside>
</form>
@endsection
