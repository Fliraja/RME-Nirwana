<div class="igd-permintaan" data-jumlah="{{ $jumlah }}" data-no-rawat="{{ $noRawat }}">
    @if($pesanTerkunci)
        <div class="igd-terkunci" role="status">
            <i class="fas fa-lock" aria-hidden="true"></i> {{ $pesanTerkunci }} Daftar di bawah tetap bisa dibaca.
        </div>
    @endif

    {{-- Laboratorium --}}
    <section class="ralan-card igd-kartu" aria-labelledby="igd-judul-lab">
        <div class="igd-kartu-kepala">
            <span class="igd-ikon" aria-hidden="true"><i class="fas fa-flask"></i></span>
            <h3 class="igd-kartu-judul" id="igd-judul-lab">Laboratorium <small>{{ $lab->count() }} permintaan</small></h3>
            @unless($pesanTerkunci)
                <button type="button" class="btn btn-sm igd-btn-utama" data-bs-toggle="collapse" data-bs-target="#igd-form-lab"
                        aria-expanded="false" aria-controls="igd-form-lab">
                    <i class="fas fa-plus" aria-hidden="true"></i> Minta lab
                </button>
            @endunless
        </div>
        @unless($pesanTerkunci)
            <form class="collapse igd-form" id="igd-form-lab" data-jenis="lab" novalidate>
                <div class="igd-form-isi">
                    <label class="igd-label" for="igd-select-lab">Pemeriksaan</label>
                    <select id="igd-select-lab" multiple placeholder="Ketik nama pemeriksaan lab..."></select>
                    <div class="igd-template" aria-live="polite"></div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-7">
                            <label class="igd-label" for="igd-dx-lab">Diagnosa klinis</label>
                            <input type="text" id="igd-dx-lab" name="diagnosa_klinis" maxlength="80" class="form-control form-control-sm"
                                   value="{{ $usulanDiagnosa }}" placeholder="Contoh: obs febris H3">
                        </div>
                        <div class="col-md-5">
                            <label class="igd-label" for="igd-info-lab">Catatan untuk petugas</label>
                            <input type="text" id="igd-info-lab" name="informasi_tambahan" maxlength="60" class="form-control form-control-sm" placeholder="Cito, puasa, dll.">
                        </div>
                    </div>
                    <div class="igd-form-aksi">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#igd-form-lab">Batal</button>
                        <button type="submit" class="btn btn-sm igd-btn-utama">Kirim permintaan lab</button>
                    </div>
                </div>
            </form>
        @endunless
        <div class="igd-kartu-badan">
            @include('igd.tabs.partials.daftar_order', ['daftar' => $lab, 'jenis' => 'lab'])
        </div>
    </section>

    {{-- Radiologi --}}
    <section class="ralan-card igd-kartu" aria-labelledby="igd-judul-rad">
        <div class="igd-kartu-kepala">
            <span class="igd-ikon" aria-hidden="true"><i class="fas fa-x-ray"></i></span>
            <h3 class="igd-kartu-judul" id="igd-judul-rad">Radiologi <small>{{ $radiologi->count() }} permintaan</small></h3>
            @unless($pesanTerkunci)
                <button type="button" class="btn btn-sm igd-btn-garis" data-bs-toggle="collapse" data-bs-target="#igd-form-radiologi"
                        aria-expanded="false" aria-controls="igd-form-radiologi">
                    <i class="fas fa-plus" aria-hidden="true"></i> Minta radiologi
                </button>
            @endunless
        </div>
        @unless($pesanTerkunci)
            <form class="collapse igd-form" id="igd-form-radiologi" data-jenis="radiologi" novalidate>
                <div class="igd-form-isi">
                    <label class="igd-label" for="igd-select-radiologi">Pemeriksaan</label>
                    <select id="igd-select-radiologi" multiple placeholder="Ketik nama pemeriksaan radiologi..."></select>
                    <div class="row g-2 mt-1">
                        <div class="col-md-7">
                            <label class="igd-label" for="igd-dx-rad">Diagnosa klinis</label>
                            <input type="text" id="igd-dx-rad" name="diagnosa_klinis" maxlength="80" class="form-control form-control-sm"
                                   value="{{ $usulanDiagnosa }}" placeholder="Contoh: susp. pneumonia">
                        </div>
                        <div class="col-md-5">
                            <label class="igd-label" for="igd-info-rad">Catatan untuk petugas</label>
                            <input type="text" id="igd-info-rad" name="informasi_tambahan" maxlength="60" class="form-control form-control-sm" placeholder="Cito, posisi, dll.">
                        </div>
                    </div>
                    <div class="igd-form-aksi">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#igd-form-radiologi">Batal</button>
                        <button type="submit" class="btn btn-sm igd-btn-utama">Kirim permintaan radiologi</button>
                    </div>
                </div>
            </form>
        @endunless
        <div class="igd-kartu-badan">
            @include('igd.tabs.partials.daftar_order', ['daftar' => $radiologi, 'jenis' => 'radiologi'])
        </div>
    </section>

    @include('igd.tabs.partials.kartu_resep')
</div>
