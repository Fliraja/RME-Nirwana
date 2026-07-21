@extends('layouts.app')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-success text-white">
            <h6 class="m-0 font-weight-bold">Filter Laporan Bulanan SOAP</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('report.soap-index') }}" method="GET" class="row mb-4">
                <div class="col-md-3">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tgl_mulai" class="form-control" value="{{ $tgl_mulai }}">
                </div>
                <div class="col-md-3">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="tgl_selesai" class="form-control" value="{{ $tgl_selesai }}">
                </div>

                @if(session('role') === 'admin')
                <div class="col-md-3">
                    <label>Pilih Dokter</label>
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
                    <label>&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-search"></i> Tampilkan
                        </button>
                        <a href="{{ route('report.soap-pdf', ['tgl_mulai' => $tgl_mulai, 'tgl_selesai' => $tgl_selesai, 'nip' => request('nip')]) }}" 
                        target="_blank" class="btn btn-danger">
                            <i class="fa fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped small" id="tableSoap">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th width="120px">Waktu</th>
                            <th width="150px">Nama Pasien</th>
                            @if(session('role') === 'admin')
                                <th width="150px">Dokter</th>
                            @endif
                            <th width="50px">JK</th>
                            <th>Subject (S)</th>
                            <th>Object (O)</th>
                            <th>Assessment (A)</th>
                            <th>Plan (P)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporan as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->tgl_perawatan }}<br><span class="text-muted">{{ $item->jam_rawat }}</span></td>
                                <td>
                                    <strong>{{ $item->regPeriksa->pasien->nm_pasien ?? '-' }}</strong><br>
                                    <small class="text-primary">{{ $item->regPeriksa->no_rkm_medis }}</small>
                                </td>
                                @if(session('role') === 'admin')
                                    <td>{{ $item->regPeriksa->dokter->nm_dokter ?? '-' }}</td>
                                @endif
                                <td class="text-center">{{ $item->regPeriksa->pasien->jk ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($item->keluhan, 100) }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($item->pemeriksaan, 100) }}</td>
                                <td>{{ $item->penilaian }}</td>
                                <td>{{ $item->rtl }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">Data tidak ditemukan untuk periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection