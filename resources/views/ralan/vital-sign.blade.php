<div class="p-3">
    @if(session('success-vital'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success-vital') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('ralan.store-vital') }}" method="POST" id="formVital">
        @csrf
        <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">

        <div class="row">
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="small fw-bold">Tensi (mmHg)</label>
                    <input type="text" name="tensi" class="form-control form-control-sm"
                           value="{{ $pasien->pemeriksaanRalan->tensi ?? '' }}" placeholder="120/80">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="small fw-bold">Suhu (°C)</label>
                    <input type="text" name="suhu_tubuh" class="form-control form-control-sm"
                           value="{{ $pasien->pemeriksaanRalan->suhu_tubuh ?? '' }}" placeholder="36.5">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="small fw-bold">Nadi (/mnt)</label>
                    <input type="text" name="nadi" class="form-control form-control-sm"
                           value="{{ $pasien->pemeriksaanRalan->nadi ?? '' }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="small fw-bold">Respirasi</label>
                    <input type="text" name="respirasi" class="form-control form-control-sm"
                           value="{{ $pasien->pemeriksaanRalan->respirasi ?? '' }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="small fw-bold">Kesadaran</label>
                    <select name="kesadaran" class="form-select form-select-sm">
                        <option value="Compos Mentis" {{ ($pasien->pemeriksaanRalan->kesadaran ?? '') == 'Compos Mentis' ? 'selected' : '' }}>Compos Mentis</option>
                        <option value="Somnolence" {{ ($pasien->pemeriksaanRalan->kesadaran ?? '') == 'Somnolence' ? 'selected' : '' }}>Somnolence</option>
                        <option value="Sopor" {{ ($pasien->pemeriksaanRalan->kesadaran ?? '') == 'Sopor' ? 'selected' : '' }}>Sopor</option>
                        <option value="Coma" {{ ($pasien->pemeriksaanRalan->kesadaran ?? '') == 'Coma' ? 'selected' : '' }}>Coma</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="small fw-bold">Tinggi (cm)</label>
                    <input type="text" name="tinggi" class="form-control form-control-sm" value="{{ $pasien->pemeriksaanRalan->tinggi ?? '' }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="small fw-bold">Berat (Kg)</label>
                    <input type="text" name="berat" class="form-control form-control-sm" value="{{ $pasien->pemeriksaanRalan->berat ?? '' }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="small fw-bold">GCS (E,V,M)</label>
                    <input type="text" name="gcs" class="form-control form-control-sm" value="{{ $pasien->pemeriksaanRalan->gcs ?? '' }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="small fw-bold">Alergi</label>
                    <input type="text" name="alergi" class="form-control form-control-sm" value="{{ $pasien->pemeriksaanRalan->alergi ?? '' }}">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-save me-1"></i> Simpan Vital Sign
        </button>
    </form>

    <hr>

    <h6 class="fw-bold"><i class="fas fa-history me-1"></i> Data Terakhir Pasien</h6>
    <div class="table-responsive">
        <table class="table table-bordered table-sm table-hover">
            <thead class="bg-light text-center small">
                <tr>
                    <th>Tgl/Jam</th>
                    <th>Tensi</th>
                    <th>Suhu</th>
                    <th>Nadi</th>
                    <th>Respi</th>
                    <th>TB/BB</th>
                    <th>GCS</th>
                    <th>Alergi</th>
                </tr>
            </thead>
            <tbody class="small text-center">
                @if($pasien->pemeriksaanRalan)
                <tr>
                    <td>{{ $pasien->pemeriksaanRalan->tgl_perawatan }} <br> {{ $pasien->pemeriksaanRalan->jam_rawat }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->tensi }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->suhu_tubuh }} °C</td>
                    <td>{{ $pasien->pemeriksaanRalan->nadi }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->respirasi }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->tinggi }} / {{ $pasien->pemeriksaanRalan->berat }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->gcs }} ({{ $pasien->pemeriksaanRalan->kesadaran }})</td>
                    <td class="text-start text-danger fw-bold">{{ $pasien->pemeriksaanRalan->alergi }}</td>
                </tr>
                @else
                <tr>
                    <td colspan="8" class="text-muted p-3">Belum ada riwayat pemeriksaan hari ini.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
