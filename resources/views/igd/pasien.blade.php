@extends('layouts.app')

@section('title', 'IGD · ' . $kepala['reg']->nm_pasien)
@section('page-title', 'IGD')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/igd.css') }}">
@endpush

@section('content')
<div class="container-fluid igd-halaman" data-no-rawat="{{ $noRawatUrl }}">
    <a href="{{ route('igd.index') }}" class="igd-kembali"><i class="fas fa-arrow-left" aria-hidden="true"></i> Daftar pasien IGD</a>

    @include('igd.partials.kepala')

    <ul class="nav nav-tabs ralan-tabs igd-tabs" id="igdTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="order-tab" data-bs-toggle="tab" data-bs-target="#tab-order" type="button" role="tab" aria-controls="tab-order" aria-selected="true">
                <i class="fas fa-list-check" aria-hidden="true"></i> Order
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="asesmen-tab" data-bs-toggle="tab" data-bs-target="#tab-asesmen" type="button" role="tab" aria-controls="tab-asesmen" aria-selected="false">
                <i class="fas fa-notes-medical" aria-hidden="true"></i> Asesmen
                @unless($kepala['ada_asesmen'])
                    <span class="igd-tab-tanda igd-tab-tanda-belum">belum</span>
                @endunless
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#tab-riwayat" type="button" role="tab" aria-controls="tab-riwayat" aria-selected="false"
                    data-src="{{ route('igd.riwayat', $noRawatUrl) }}">
                <i class="fas fa-clock-rotate-left" aria-hidden="true"></i> Riwayat
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-order" role="tabpanel" aria-labelledby="order-tab" tabindex="0">
            <div class="ralan-card"><div class="ralan-card-body igd-kosong">Permintaan lab, radiologi, dan resep IGD sedang disiapkan.</div></div>
        </div>
        <div class="tab-pane fade" id="tab-asesmen" role="tabpanel" aria-labelledby="asesmen-tab" tabindex="0">
            <div class="ralan-card"><div class="ralan-card-body igd-kosong">Form asesmen medis IGD sedang disiapkan.</div></div>
        </div>
        <div class="tab-pane fade" id="tab-riwayat" role="tabpanel" aria-labelledby="riwayat-tab" tabindex="0">
            <div class="ralan-card"><div class="ralan-card-body igd-isi-tab"></div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/igd/igd-core.js') }}"></script>
@endpush
