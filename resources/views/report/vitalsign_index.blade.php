@extends('layouts.app')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-success text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-heartbeat"></i> Laporan Vital Sign Pasien</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('report.vitalsign-index') }}" method="GET" class="row mb-4">
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
                    <select name="nip" class="form-select select2">
                        <option value="">-- Semua Dokter --</option>
                        @foreach($listDokter as $dr)
                            <option value="{{ $dr->kd_dokter }}" {{ (isset($filter_nip) && $filter_nip == $dr->kd_dokter) ? 'selected' : '' }}>
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
                        <a href="{{ route('report.vitalsign-pdf', ['tgl_mulai' => $tgl_mulai, 'tgl_selesai' => $tgl_selesai, 'nip' => request('nip')]) }}" 
                        target="_blank" class="btn btn-danger">
                            <i class="fa fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover small">
                    <thead class="bg-light text-center">
                        <tr>
                            <th rowspan="2" class="align-middle">Waktu</th>
                            <th rowspan="2" class="align-middle">Nama Pasien</th>
                            @if(session('role') === 'admin')
                                <th rowspan="2" class="align-middle">Dokter</th>
                            @endif
                            <th colspan="6" class="bg-white">Parameter Vital Sign</th>
                        </tr>
                        <tr>
                            <th width="80px">Suhu (°C)</th>
                            <th width="100px">Tensi (mmHg)</th>
                            <th width="80px">Nadi (/mnt)</th>
                            <th width="80px">RR (/mnt)</th>
                            <th width="80px">Tinggi (cm)</th>
                            <th width="80px">Berat (kg)</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        @forelse($laporan as $item)
                            <tr>
                                <td>{{ $item->tgl_perawatan }}<br><small class="text-muted">{{ $item->jam_rawat }}</small></td>
                                <td class="text-left">
                                    <strong>{{ $item->regPeriksa->pasien->nm_pasien ?? '-' }}</strong><br>
                                    <small>{{ $item->regPeriksa->no_rkm_medis }}</small>
                                </td>
                                @if(session('role') === 'admin')
                                    <td>{{ $item->regPeriksa->dokter->nm_dokter ?? '-' }}</td>
                                @endif
                                <td>{{ $item->suhu_tubuh }}</td>
                                <td>{{ $item->tensi }}</td>
                                <td>{{ $item->nadi }}</td>
                                <td>{{ $item->respirasi }}</td>
                                <td>{{ $item->tinggi }}</td>
                                <td>{{ $item->berat }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4">Tidak ada data vital sign pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection