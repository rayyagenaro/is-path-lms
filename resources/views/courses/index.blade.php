@extends('layouts.app')

@section('title', 'Katalog Pembelajaran')
@section('eyebrow', 'Katalog Pembelajaran')

@section('content')
<section class="page-intro catalog-intro" aria-labelledby="catalog-title">
    <span class="eyebrow">KATALOG KELAS</span>
    <h1 id="catalog-title">Pilih kelas yang paling dekat dengan target kariermu.</h1>
    <p>Setiap kelas memetakan kompetensi lintas peran. Progres tetap terbaca walau arah kariermu berubah.</p>
</section>

<form class="catalog-toolbar" method="GET" action="{{ route('courses') }}" role="search">
    <label class="search-box" for="course-search">
        <span class="sr-only">Cari kelas berdasarkan judul atau kode</span>
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <circle cx="11" cy="11" r="6"/>
            <path d="m16 16 4 4"/>
        </svg>
        <input
            id="course-search"
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Cari kelas, kode, atau topik"
            autocomplete="off"
        >
    </label>

    <label class="sr-only" for="course-category">Filter kelas berdasarkan kategori</label>
    <select id="course-category" name="category">
        <option value="">Semua kategori</option>
        @foreach($categories as $category)
            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
        @endforeach
    </select>

    <button class="btn btn-outline" type="submit">Terapkan</button>
    <span role="status" aria-live="polite">{{ $courses->count() }} kelas tersedia</span>
</form>

<section class="catalog-grid v2-catalog" aria-label="Daftar kelas">
    @forelse($courses as $course)
        <a
            href="{{ route('courses.show', $course->slug) }}"
            class="catalog-card"
            aria-label="Buka kelas {{ $course->title }}"
        >
            <div class="catalog-cover">
                <span>{{ $course->code }}</span>

                @if(Str::contains($course->category, ['Data', 'Software', 'Infrastructure', 'Technology']))
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="m8 9-3 3 3 3M16 9l3 3-3 3M14 5l-4 14"/>
                    </svg>
                @else
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5zM4 5.5v16M8 7h8M8 11h7"/>
                    </svg>
                @endif
            </div>

            <div class="catalog-body">
                <span class="tag">{{ $course->category }}</span>
                <h2>{{ $course->title }}</h2>
                <p>{{ Str::limit($course->description, 110) }}</p>
                <div>
                    <span>{{ round($course->duration_minutes / 60, 1) }} jam</span>
                    <span>{{ $course->level }}</span>
                </div>
            </div>
        </a>
    @empty
        <div class="empty-state">
            <strong>Belum ada kelas yang sesuai.</strong>
            <p>Ubah kata kunci atau pilih kembali semua kategori untuk melihat kelas lain.</p>
            <a href="{{ route('courses') }}" class="btn btn-outline">Hapus filter</a>
        </div>
    @endforelse
</section>
@endsection
