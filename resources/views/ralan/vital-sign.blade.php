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
                    <label class="small fw-bold">SpO2 (%)</label>
                    <input type="text" name="spo2" class="form-control form-control-sm" value="{{ $pasien->pemeriksaanRalan->spo2 ?? '' }}" placeholder="98">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="small fw-bold">GCS (E,V,M)</label>
                    <input type="text" name="gcs" class="form-control form-control-sm" value="{{ $pasien->pemeriksaanRalan->gcs ?? '' }}">
                </div>
            </div>
            <div class="col-md-4">
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

    <div class="d-flex align-items-center mb-2">
        <h6 class="fw-bold mb-0"><i class="fas fa-history me-1"></i> Data Terakhir Pasien</h6>
        <span class="badge bg-light text-muted border ms-2 small fw-normal">
            <i class="fas fa-mouse-pointer me-1 text-primary"></i>Klik baris untuk salin ke form
        </span>
    </div>
    <div id="ralan-vital-table">
        @include('ralan.partials.vital_table')
    </div>
</div>
