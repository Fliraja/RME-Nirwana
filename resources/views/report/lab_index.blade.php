@extends('layouts.app')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-success text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-flask"></i> Laporan Permintaan Laboratorium</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('report.lab-index') }}" method="GET" class="row mb-4">
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
                    <label class="form-label">Pilih Dokter Perujuk</label>
                    <select name="dokter_perujuk" class="form-select select2">
                        <option value="">-- Semua Dokter --</option>
                        @foreach($listDokter as $dr)
                            <option value="{{ $dr->kd_dokter }}" {{ (isset($filter_dokter) && $filter_dokter == $dr->kd_dokter) ? 'selected' : '' }}>
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
                            <i class="fa fa-search"></i> Filter
                        </button>
                        <a href="{{ route('report.lab-pdf', ['tgl_mulai' => $tgl_mulai, 'tgl_selesai' => $tgl_selesai, 'dokter_perujuk' => request('dokter_perujuk')]) }}" 
                        target="_blank" class="btn btn-danger">
                            <i class="fa fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered small">
                    <thead class="bg-light">
                        <tr class="text-center">
                            <th>No. Order / Waktu</th>
                            <th>Pasien</th>
                            @if(session('role') === 'admin')
                            <th>Dokter Perujuk</th>
                            @endif
                            <th>Grup Pemeriksaan</th>
                            <th>Detail Item Lab (Koma)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporan as $row)
                            @foreach($row->pemeriksaan as $index => $item)
                            <tr>
                                @if($index === 0)
                                    <td rowspan="{{ $row->pemeriksaan->count() }}" class="text-center align-middle">
                                        <strong>{{ $row->noorder }}</strong><br>
                                        <small>{{ $row->tgl_permintaan }} {{ $row->jam_permintaan }}</small>
                                    </td>
                                    <td rowspan="{{ $row->pemeriksaan->count() }}" class="align-middle">
                                        <strong>{{ $row->regPeriksa->pasien->nm_pasien ?? '-' }}</strong><br>
                                        <small>RM: {{ $row->regPeriksa->no_rkm_medis }}</small>
                                    </td>
                                    @if(session('role') === 'admin')
                                        <td rowspan="{{ $row->pemeriksaan->count() }}" class="align-middle">
                                        <strong>{{ $row->regPeriksa->dokter->nm_dokter ?? '-' }}</strong>
                                    @endif
                                @endif
                                
                                <td class="bg-light"><strong>{{ $item->jenisPerawatan->nm_perawatan }}</strong></td>
                                <td class="align-middle">
                                    @php
                                        $detailNames = $item->detailTemplate
                                            ->where('kd_jenis_prw', $item->kd_jenis_prw) 
                                            ->map(function($d) {
                                                return $d->template->Pemeriksaan ?? '-';
                                            })
                                            ->implode(', ');
                                    @endphp
                                    
                                    @if($detailNames)
                                        {{ $detailNames }}
                                    @else
                                        <span class="text-muted italic small">- Umum -</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="4" class="text-center py-4">Tidak ada data permintaan lab.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection