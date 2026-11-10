@extends('layouts.app')

@section('title', 'IGD')
@section('page-title', 'IGD')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/igd.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <div class="ralan-card">
        <div class="ralan-card-head igd-daftar-head">
            <span>Pasien IGD
                <span class="igd-jumlah">{{ $daftarPasien->count() }} pasien</span>
            </span>
            <form method="GET" action="{{ route('igd.index') }}" class="igd-filter" role="search">
                <label class="visually-hidden" for="igd-cari">Cari pasien</label>
                <input type="search" id="igd-cari" name="cari" value="{{ $cari }}" maxlength="60"
                       class="form-control form-control-sm igd-cari" placeholder="Nama, no. RM, atau no. rawat">
                <label class="igd-filter-label" for="igd-dari">Dari</label>
                <input type="date" id="igd-dari" name="dari" value="{{ $dari }}" class="form-control form-control-sm">
                <label class="igd-filter-label" for="igd-sampai">s.d.</label>
                <input type="date" id="igd-sampai" name="sampai" value="{{ $sampai }}" class="form-control form-control-sm">
                <button type="submit" class="btn btn-sm igd-btn-utama">Tampilkan</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 igd-tabel">
                <thead>
                    <tr>
                        <th scope="col">Datang</th>
                        <th scope="col">Pasien</th>
                        <th scope="col">JK / Umur</th>
                        <th scope="col">Bayar</th>
                        <th scope="col">Dokter registrasi</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftarPasien as $p)
                        <tr>
                            <td class="text-nowrap">
                                <div class="fw-semibold">{{ substr($p->jam_reg, 0, 5) }}</div>
                                <div class="small text-muted">
                                    @if($dari !== $sampai){{ \Carbon\Carbon::parse($p->tgl_registrasi)->format('d/m') }} · @endif
                                    No. {{ $p->no_reg }}
                                </div>
                            </td>
                            <td>
                                <a href="{{ url('/igd/' . str_replace('/', '-', $p->no_rawat)) }}" class="igd-link-pasien">
                                    {{ $p->nm_pasien }}
                                </a>
                                <div class="small text-muted">RM {{ $p->no_rkm_medis }} · {{ $p->no_rawat }}</div>
                            </td>
                            <td class="text-nowrap">{{ $p->jk }}, {{ $p->umurdaftar }} {{ $p->sttsumur }}</td>
                            <td>{{ $p->png_jawab ?? '-' }}</td>
                            <td>{{ $p->nm_dokter ?? '-' }}</td>
                            <td>
                                <div class="igd-status-list">
                                    @if($p->rawat_inap)
                                        <span class="resume-status igd-status-ranap">Rawat inap</span>
                                    @endif
                                    @if($p->ada_asesmen)
                                        <span class="resume-status resume-status-ok">Asesmen terisi</span>
                                    @else
                                        <span class="resume-status resume-status-tunggu">Asesmen belum</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                @if($cari !== '')
                                    Tidak ada pasien IGD yang cocok dengan "{{ $cari }}" pada rentang tanggal ini.
                                    <a href="{{ route('igd.index', ['dari' => $dari, 'sampai' => $sampai]) }}">Hapus pencarian</a>
                                @else
                                    Belum ada pasien IGD terdaftar pada tanggal ini. Pasien didaftarkan dari Khanza.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
