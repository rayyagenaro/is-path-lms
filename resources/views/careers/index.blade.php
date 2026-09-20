@extends('layouts.app')

@section('title', 'Eksplorasi Karier')
@section('eyebrow', 'Eksplorasi Karier')

@section('content')
@if(session('success'))
    <div class="success-banner" role="status">
        <span aria-hidden="true">
            <svg class="inline-icon" viewBox="0 0 24 24"><path d="m6 12 4 4 8-9"/></svg>
        </span>
        <div>
            <strong>Target karier diperbarui</strong>
            <p>{{ session('success') }}</p>
        </div>
    </div>
@endif

<section class="discovery-hero" aria-labelledby="discovery-title">
    <div>
        <span class="eyebrow">MULAI DARI ARAH</span>
        <h1 id="discovery-title">Temukan peran yang terasa<br><em>masuk akal untukmu.</em></h1>
        <p>{{ $hasPostAssessment ? 'Jelajahi peran di bidang Sistem Informasi. Kecocokan membantu menyaring pilihan berdasarkan profil dan bukti kemampuanmu.' : 'Kenali ragam peran di bidang Sistem Informasi. Urutan awal mengikuti minatmu; rekomendasi personal dibuka setelah post-assessment.' }}</p>
    </div>
    <div class="discovery-stats" aria-label="Cakupan IS-Path">
        <div><strong>{{ $clusters->count() }}</strong><span>Bidang</span></div>
        <div><strong>{{ $coverage['roles'] }}</strong><span>Peran karier</span></div>
        <div><strong>{{ $coverage['courses'] }}</strong><span>Kelas</span></div>
    </div>
</section>

<form class="career-filterbar" method="GET" action="{{ route('careers') }}" aria-label="Saring pilihan karier">
    <label class="search-box" for="career-search">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
        <span class="sr-only">Cari peran atau bidang</span>
        <input id="career-search" type="search" name="q" value="{{ request('q') }}" placeholder="Cari peran atau bidang…">
    </label>

    <label class="sr-only" for="career-cluster">Klaster karier</label>
    <select id="career-cluster" name="cluster">
        <option value="">Semua klaster</option>
        @foreach($clusters as $cluster)
            <option value="{{ $cluster->slug }}" @selected(request('cluster') === $cluster->slug)>{{ $cluster->name }}</option>
        @endforeach
    </select>

    <label class="sr-only" for="career-level">Tingkat karier</label>
    <select id="career-level" name="level">
        <option value="">Semua tingkat</option>
        <option value="standard" @selected(request('level') === 'standard')>Dasar sampai menengah</option>
        <option value="advanced" @selected(request('level') === 'advanced')>Lanjutan</option>
    </select>

    <button class="btn btn-outline" type="submit">Terapkan filter</button>
</form>

<nav class="cluster-strip" aria-label="Klaster karier">
    @foreach($clusters as $cluster)
        <a
            class="cluster-pill {{ request('cluster') === $cluster->slug ? 'active' : '' }}"
            href="{{ route('careers', array_merge(request()->except(['cluster', 'page']), ['cluster' => $cluster->slug])) }}"
            @if(request('cluster') === $cluster->slug) aria-current="page" @endif
        >
            <span aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
            <strong>{{ $cluster->name }}</strong>
        </a>
    @endforeach
</nav>

<div class="section-heading">
    <div>
        <span class="eyebrow">{{ $hasPostAssessment ? 'BERDASARKAN PROFIL DAN BUKTI' : 'BERDASARKAN PROFIL AWAL' }}</span>
        <h2>{{ request()->hasAny(['cluster', 'level', 'q']) ? 'Hasil sesuai filter' : 'Pilihan karier untuk dieksplorasi' }}</h2>
    </div>
    <p>{{ $roles->total() }} peran ditemukan</p>
</div>

<section
    class="career-role-grid"
    id="career-results"
    aria-label="Daftar pilihan karier"
>
    @forelse($roles as $role)
        <article class="role-card">
            <div class="role-card-top">
                <span class="role-monogram" aria-hidden="true">{{ \App\Support\RolePresentation::initials($role->name) }}</span>
                <label class="compare-control">
                    <input type="checkbox" value="{{ $role->id }}" data-role-name="{{ $role->name }}" aria-label="Bandingkan {{ $role->name }}">
                    <span>Bandingkan</span>
                </label>
            </div>

            <div class="role-meta">
                <span>{{ $role->cluster_name }}</span>
            </div>

            <h3>{{ $role->name }}</h3>
            <p>{{ Str::limit($role->description, 125) }}</p>

            @if($hasPostAssessment)
            <div class="dual-score">
                <div>
                    <small>{{ $hasPostAssessment ? 'Kecocokan' : 'Kecocokan awal' }}</small>
                    <strong>{{ $role->score }}%</strong>
                    <div class="progress" role="progressbar" aria-label="Kecocokan dengan {{ $role->name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $role->score }}">
                        <i style="width:{{ $role->score }}%"></i>
                    </div>
                </div>
                <div>
                    <small>Kesiapan</small>
                    <strong>{{ $hasPostAssessment ? $role->readiness['score'].'%' : 'Belum' }}</strong>
                    <div class="progress readiness" role="progressbar" aria-label="{{ $hasPostAssessment ? 'Kesiapan untuk '.$role->name : 'Kesiapan belum diukur' }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $hasPostAssessment ? $role->readiness['score'] : 0 }}">
                        <i style="width:{{ $hasPostAssessment ? $role->readiness['score'] : 0 }}%"></i>
                    </div>
                </div>
            </div>
            @else
                <div class="blocking-note" role="note"><strong>Mode eksplorasi</strong><span>Skor personal belum ditampilkan.</span></div>
            @endif

            <div class="skill-chips" aria-label="Kompetensi terkait">
                @foreach($role->top_skills as $skill)<span>{{ $skill }}</span>@endforeach
            </div>

            <a href="{{ route('careers.show', $role->slug) }}" class="role-link">
                <span>Lihat jalur karier</span>
                <span aria-hidden="true"><svg class="inline-icon" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></span>
            </a>
        </article>
    @empty
        <div class="empty-state">
            <strong>Belum ada peran yang sesuai.</strong>
            <p>Coba longgarkan filter atau gunakan kata kunci lain.</p>
            <a href="{{ route('careers') }}" class="btn btn-outline">Hapus semua filter</a>
        </div>
    @endforelse
</section>

@if($roles->total() > 0)
    <div class="pagination-wrap" aria-label="Navigasi halaman karier">
        <p class="result-count">
            Menampilkan {{ $roles->firstItem() }}-{{ $roles->lastItem() }} dari {{ $roles->total() }} peran
        </p>
        {{ $roles->onEachSide(1)->links('vendor.pagination.ispath') }}
    </div>
@endif

@if($comparison)
    <section class="comparison-panel" id="comparison" aria-labelledby="comparison-title">
        <div class="section-heading">
            <div>
                <span class="eyebrow">PERBANDINGAN KARIER</span>
                <h2 id="comparison-title">{{ $comparison['role_a']->name }} <i>dan</i> {{ $comparison['role_b']->name }}</h2>
            </div>
            <strong class="transfer-score">{{ $comparison['transferability'] }}% kompetensi dapat dialihkan</strong>
        </div>
        <div class="comparison-grid">
            <div><small>DIPAKAI DI KEDUANYA</small>@forelse($comparison['shared'] as $item)<span class="comparison-skill shared">{{ $item }}</span>@empty<p>Tidak ada kompetensi yang sama.</p>@endforelse</div>
            <div><small>KHUSUS {{ strtoupper($comparison['role_a']->name) }}</small>@foreach($comparison['unique_a'] as $item)<span class="comparison-skill">{{ $item }}</span>@endforeach</div>
            <div><small>KHUSUS {{ strtoupper($comparison['role_b']->name) }}</small>@foreach($comparison['unique_b'] as $item)<span class="comparison-skill">{{ $item }}</span>@endforeach</div>
        </div>
    </section>
@endif

<div class="compare-dock" id="compareDock" hidden>
    <div>
        <strong>Bandingkan peran karier</strong>
        <span id="compareHint" role="status" aria-live="polite">Pilih dua peran</span>
    </div>
    <button class="btn btn-primary" id="compareButton" type="button" disabled>Bandingkan</button>
    <button class="btn btn-outline" id="clearComparison" type="button">Hapus pilihan</button>
</div>
@endsection

@push('scripts')
<script>
const checks = [...document.querySelectorAll('.compare-control input')];
const dock = document.getElementById('compareDock');
const compareButton = document.getElementById('compareButton');
const compareHint = document.getElementById('compareHint');

const comparisonKey = 'ispath:compare:{{ auth()->id() }}';
let selected = new Map();
try {
    const saved = JSON.parse(sessionStorage.getItem(comparisonKey) || '[]');
    if (Array.isArray(saved)) selected = new Map(saved.filter(item => Array.isArray(item) && /^\d+$/.test(item[0]) && typeof item[1] === 'string').slice(0, 2));
} catch {}
const updateComparison = () => {
    checks.forEach(check => { check.checked = selected.has(check.value); });
    dock.hidden = selected.size === 0;
    compareButton.disabled = selected.size !== 2;
    compareHint.textContent = `${selected.size}/2 dipilih: ${[...selected.values()].join(' dan ')}`;
    try { sessionStorage.setItem(comparisonKey, JSON.stringify([...selected])); } catch {}
};
checks.forEach(check => check.addEventListener('change', () => {
    if (check.checked && selected.size >= 2 && !selected.has(check.value)) {
        check.checked = false;
        compareHint.textContent = 'Dua peran sudah dipilih. Hapus pilihan untuk menggantinya.';
        return;
    }
    if (check.checked) selected.set(check.value, check.dataset.roleName);
    else selected.delete(check.value);
    updateComparison();
}));
document.getElementById('clearComparison').addEventListener('click', () => { selected.clear(); updateComparison(); });
compareButton.onclick = () => {
        if (selected.size !== 2) return;
        const params = new URLSearchParams(window.location.search);
        params.delete('page');
        params.set('compare', [...selected.keys()].join(','));
        window.location.href = `{{ route('careers') }}?${params.toString()}#comparison`;
};
updateComparison();
</script>
@endpush
