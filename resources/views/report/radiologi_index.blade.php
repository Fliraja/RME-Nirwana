@extends('layouts.app')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-success text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-x-ray"></i> Laporan Permintaan Radiologi</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('report.radiologi-index') }}" method="GET" class="row mb-4">
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
                            <i class="fa fa-search me-1"></i> Filter
                        </button>
                        <a href="{{ route('report.radiologi-pdf', [
                            'tgl_mulai' => $tgl_mulai, 
                            'tgl_selesai' => $tgl_selesai, 
                            'dokter_perujuk' => request('dokter_perujuk')
                        ]) }}" target="_blank" class="btn btn-danger">
                            <i class="fa fa-file-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </div>
            </form>

            <table class="table table-bordered small">
                <thead class="bg-light text-center">
                    <tr>
                        <th>No. Order / Waktu</th>
                        <th>Pasien</th>
                        @if(session('role') === 'admin')
                            <th>Dokter</th>
                        @endif
                        <th>Jenis Pemeriksaan</th>
                        <th>Diagnosa Klinis & Info</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($laporan as $row)
                        @php $rowCount = $row->pemeriksaan->count(); @endphp
                        @foreach($row->pemeriksaan as $index => $item)
                        <tr>
                            @if($index === 0)
                                <td rowspan="{{ $rowCount }}" class="text-center align-middle">
                                    <strong>{{ $row->noorder }}</strong><br>
                                    {{ $row->tgl_permintaan }} <small>{{ $row->jam_permintaan }}</small>
                                </td>
                                <td rowspan="{{ $rowCount }}" class="align-middle">
                                    <strong>{{ $row->regPeriksa->pasien->nm_pasien ?? '-' }}</strong><br>
                                    <small>RM: {{ $row->regPeriksa->no_rkm_medis }}</small>
                                </td>
                                @if(session('role') === 'admin')
                                    <td rowspan="{{ $rowCount }}" class="align-middle">
                                    <strong>{{ $row->regPeriksa->dokter->nm_dokter ?? '-' }}</strong>
                                @endif
                            @endif
                            
                            <td class="bg-light align-middle">{{ $item->jenisPerawatan->nm_perawatan }}</td>
                            
                            @if($index === 0)
                                <td rowspan="{{ $rowCount }}" class="align-middle">
                                    <strong>Klinis:</strong> {{ $row->diagnosa_klinis }} <br>
                                    <strong>Info:</strong> <span class="text-muted small">{{ $row->informasi_tambahan }}</span>
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="4" class="text-center">Data tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection