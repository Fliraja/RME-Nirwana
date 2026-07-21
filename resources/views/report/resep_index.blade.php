@extends('layouts.app')

@section('content')
<div class="card shadow mb-4">
    <div class="card-header py-3 bg-success text-white">
        <h6 class="m-0 font-weight-bold"><i class="fas fa-pills"></i> Laporan Permintaan Resep Obat</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('report.resep-index') }}" method="GET" class="row mb-4">
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="tgl_mulai" class="form-control" value="{{ $tgl_mulai }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" name="tgl_selesai" class="form-control" value="{{ $tgl_selesai }}">
            </div>

            @if(session('role') === 'admin')
            <div class="col-md-3">
                <label class="form-label">Pilih Dokter</label>
                <select name="kd_dokter" class="form-select select2">
                    <option value="">-- Semua Dokter --</option>
                    @foreach($listDokter as $dr)
                        <option value="{{ $dr->kd_dokter }}" {{ (isset($filter_kd_dokter) && $filter_kd_dokter == $dr->kd_dokter) ? 'selected' : '' }}>
                            {{ $dr->nm_dokter }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('report.resep-pdf', [
                        'tgl_mulai' => $tgl_mulai, 
                        'tgl_selesai' => $tgl_selesai, 
                        'kd_dokter' => request('kd_dokter')
                    ]) }}" target="_blank" class="btn btn-danger">
                        <i class="fa fa-file-pdf me-1"></i> Export PDF
                    </a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered small">
                <thead class="bg-light text-center">
                    <tr>
                        <th width="150px">No. Resep / Waktu</th>
                        <th width="200px">Pasien</th>
                        @if(session('role') === 'admin')
                            <th>Dokter</th>
                        @endif
                        <th>Daftar Obat (Non-Racik & Racikan)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($laporan as $row)
                    <tr>
                        <td class="text-center align-middle">
                            <strong>{{ $row->no_resep }}</strong><br>
                            {{ $row->tgl_peresepan }}<br>
                            <small class="text-muted">{{ $row->jam_peresepan }}</small>
                        </td>
                        <td class="align-middle">
                            <strong>{{ $row->regPeriksa->pasien->nm_pasien ?? '-' }}</strong><br>
                            <small class="text-primary">RM: {{ $row->regPeriksa->no_rkm_medis }}</small>
                        </td>
                        @if(session('role') === 'admin')
                            <td class="align-middle">
                                <strong>{{ $row->regPeriksa->dokter->nm_dokter ?? '-' }}</strong>
                            </td>
                        @endif
                        <td>
                            {{-- Obat Umum --}}
                            @if($row->resepDokter->count() > 0)
                                <div class="mb-1">
                                    <span class="badge bg-secondary text-white">Umum</span> 
                                    @php
                                        echo $row->resepDokter->map(function($item) {
                                            return $item->dataBarang->nama_brng . " (qty: " . $item->jml . ") [" . $item->aturan_pakai . "]";
                                        })->implode('; ');
                                    @endphp
                                </div>
                            @endif

                            {{-- Obat Racikan --}}
                            @if($row->resepRacikan->count() > 0)
                                <div class="mt-2 pt-2 border-top">
                                    <span class="badge bg-info text-white">Racikan</span>
                                    <ul class="list-unstyled mb-0">
                                    @foreach($row->resepRacikan as $racik)
                                        <li class="mb-1">
                                            <i class="fas fa-mortar-pestle small text-info"></i> 
                                            <strong>{{ $racik->nama_racik }} ({{ $racik->metodeRacik->nm_racik ?? $racik->kd_racik }})</strong>: 
                                            @php
                                                echo $racik->detailRacikan->map(function($det) {
                                                    return $det->dataBarang->nama_brng . " (" . $det->jml . ")";
                                                })->implode(', ');
                                            @endphp
                                            <br><small class="text-muted font-italic pl-4">Aturan: {{ $racik->aturan_pakai }}</small>
                                        </li>
                                    @endforeach
                                    </ul>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center py-4 text-muted">Tidak ada data resep dalam periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection