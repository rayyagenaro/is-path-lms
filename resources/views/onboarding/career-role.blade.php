@extends('layouts.app')

@section('title', 'Pilih Arah Karier')

@section('content')
<section class="onboarding-role-intro" aria-labelledby="onboarding-title">
    <span class="eyebrow">PILIH ARAH BELAJAR</span>
    <h1 id="onboarding-title">Tentukan bidang, lalu tandai peran yang ingin kamu pelajari.</h1>
    <p>Mulai dari satu bidang yang terasa dekat, kemudian pilih hingga tiga peran. Pilihan pertama menjadi fokus assessment; pilihan berikutnya disimpan untuk eksplorasi.</p>
</section>

<form method="POST" action="{{ route('onboarding.choose') }}" class="onboarding-role-form" aria-label="Pilih peran untuk dieksplorasi" data-role-selector data-max-selection="3">
    @csrf
    <input type="hidden" name="primary_role_id" value="" data-primary-role>
    @if($errors->any())
        <div class="form-errors" role="alert" tabindex="-1" data-error-summary><strong>Pilihan belum tersimpan.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="onboarding-cluster-list">
        @forelse($clusters as $cluster)
            <section class="onboarding-cluster" aria-labelledby="cluster-{{ $cluster->id }}">
                <header class="onboarding-cluster-header">
                    <div><span class="eyebrow">BIDANG</span><h2 id="cluster-{{ $cluster->id }}">{{ $cluster->name }}</h2></div>
                    <p>{{ $cluster->description }}</p>
                </header>
                <div class="onboarding-role-grid">
                    @foreach($cluster->roles as $role)
                        <label class="onboarding-role-card" data-role-card>
                            <input type="checkbox" name="career_role_ids[]" value="{{ $role->id }}" data-role-choice data-role-score="{{ $role->score }}" @checked(in_array((string) $role->id, old('career_role_ids', [])))>
                            <span class="onboarding-role-topline"><span class="onboarding-role-mark" aria-hidden="true">{{ \App\Support\RolePresentation::initials($role->name) }}</span><span class="role-meta"><span>Peran</span></span></span>
                            <span class="onboarding-role-title"><strong>{{ $role->name }}</strong>@if($role->score === $cluster->roles->max('score'))<b class="role-recommendation">Paling sesuai di bidang ini</b>@endif</span>
                            <span class="role-match-score"><strong>{{ $role->score }}%</strong><span>kecocokan · {{ $role->category }}</span></span>
                            <span class="onboarding-role-description">{{ Str::limit($role->description, 138) }}</span>
                            @if(!empty($role->details['strong']))<span class="onboarding-role-detail"><b>Kekuatan</b>{{ collect($role->details['strong'])->take(2)->pluck('name')->join(', ') }}</span>@endif
                            @if(!empty($role->details['gaps']))<span class="onboarding-role-detail"><b>Bangun berikutnya</b>{{ collect($role->details['gaps'])->take(2)->pluck('name')->join(', ') }}</span>@endif
                        </label>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="empty-state"><strong>Belum ada peran dengan assessment yang tersedia.</strong><p>Hubungi pengelola untuk melengkapi data assessment sebelum melanjutkan.</p></div>
        @endforelse
    </div>

    <div class="onboarding-actions">
        <div><strong data-role-selection-count aria-live="polite">0 dari 3 peran dipilih</strong><p class="muted" data-role-selection-help>Pilih peran yang ingin kamu eksplorasi.</p></div>
        <button class="btn btn-primary" type="submit" data-role-submit @disabled($roles->isEmpty())>Simpan pilihan dan mulai assessment</button>
    </div>
</form>
@endsection
