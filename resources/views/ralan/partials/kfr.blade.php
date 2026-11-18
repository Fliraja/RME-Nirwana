{{-- Formulir Rehab Medik (KFR) kunjungan poli P003, bagian dari #formSoap. Data dari KfrService::isianAwal(). design.md bagian 13. --}}
@php
    $isian = $kfr['isian'];
    $batas = \App\Support\BatasIsianKfr::BATAS;
    $tersimpan = $kfr['sumber'] === 'tersimpan';
    $instruksi = \App\Support\KfrFormat::pecahInstruksi($pasien->pemeriksaanRalan->instruksi ?? '');
@endphp
<section class="ralan-card kfr-kartu mb-3" id="kfr-kartu" aria-labelledby="kfr-judul">
    <div class="ralan-card-head kfr-kepala">
        <div class="kfr-judul-grup">
            <span class="resume-isian-ikon" aria-hidden="true"><i class="fas fa-person-walking"></i></span>
            <h3 class="kfr-judul" id="kfr-judul">Formulir KFR</h3>
            @if($tersimpan)
                <span class="resume-status resume-status-ok" id="kfr-status">Tersimpan {{ \Illuminate\Support\Carbon::parse($kfr['disimpanPada'])->format('d/m H.i') }}</span>
            @else
                <span class="resume-status resume-status-tunggu" id="kfr-status">Belum disimpan</span>
            @endif
        </div>
        <div class="kfr-aksi">
            <span class="visually-hidden" id="kfr-cetak-ket">Simpan dulu sebelum mencetak.</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="kfr-cetak-asesmen" aria-disabled="true" aria-describedby="kfr-cetak-ket" title="Simpan dulu sebelum mencetak.">
                <i class="fas fa-print me-1" aria-hidden="true"></i>Cetak asesmen
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="kfr-cetak-uji" aria-disabled="true" aria-describedby="kfr-cetak-ket" title="Simpan dulu sebelum mencetak.">
                <i class="fas fa-print me-1" aria-hidden="true"></i>Cetak uji fungsi
            </button>
        </div>
    </div>

    <div class="ralan-card-body">
        <div class="kfr-catatan">
            @if($kfr['sumber'] === 'kunjungan')
                <p id="kfr-catatan-sumber"><strong>Terisi dari kunjungan {{ \Illuminate\Support\Carbon::parse($kfr['tglSumber'])->format('d/m') }}.</strong> Periksa, ubah bila perlu, lalu simpan.</p>
            @elseif($kfr['sumber'] === 'kosong')
                <p id="kfr-catatan-sumber"><strong>Kunjungan KFR pertama pasien ini.</strong> Isian masih kosong.</p>
            @endif
            <p>Anamnesa, pemeriksaan, diagnosis medis, dan tatalaksana dicetak dari SOAP di atas.
                Dari Instruksi: frekuensi <strong id="kfr-frekuensi">{{ $instruksi['frekuensi'] ?: 'belum diisi' }}</strong>,
                durasi <strong id="kfr-durasi">{{ $instruksi['durasi'] ?: 'belum diisi' }}</strong>.</p>
        </div>

        <h4 class="kfr-sub">Formulir asesmen</h4>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="fw-bold mb-2" for="kfr_diagnosa_fungsi">Diagnosis fungsi</label>
                <input type="text" class="form-control" name="kfr[diagnosa_fungsi]" id="kfr_diagnosa_fungsi"
                    aria-label="Diagnosis fungsi, formulir asesmen" data-batas="{{ $batas['diagnosa_fungsi'] }}" value="{{ $isian['diagnosa_fungsi'] }}">
            </div>
            <div class="col-md-4 mb-3">
                <fieldset class="igd-pilih mb-0">
                    <legend class="fw-bold mb-2 kfr-legend">Suspek penyakit akibat kerja</legend>
                    <div class="igd-pilih-daftar">
                        @foreach(['Tidak', 'Ya'] as $pilihan)
                            <label class="igd-pilih-opsi">
                                <input type="radio" name="kfr[suspek]" value="{{ $pilihan }}" @checked($isian['suspek'] === $pilihan)>
                                <i class="fas fa-check igd-pilih-centang" aria-hidden="true"></i>{{ $pilihan }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="mt-2" id="kfr-ket-suspek-wadah" @if($isian['suspek'] !== 'Ya') hidden @endif>
                    <label class="fw-bold mb-1" for="kfr_ket_suspek">Keterangan (wajib bila Ya)</label>
                    <input type="text" class="form-control" name="kfr[ket_suspek]" id="kfr_ket_suspek"
                        data-batas="{{ $batas['ket_suspek'] }}" value="{{ $isian['ket_suspek'] }}">
                </div>
            </div>
        </div>
        <div class="row">
            @foreach(['penunjang' => 'Pemeriksaan penunjang', 'target' => 'Target / goal', 'edukasi' => 'Edukasi'] as $kolom => $label)
                <div class="col-md-4 mb-3">
                    <label class="fw-bold mb-2" for="kfr_{{ $kolom }}">{{ $label }}</label>
                    <textarea class="form-control" name="kfr[{{ $kolom }}]" id="kfr_{{ $kolom }}" rows="2" data-batas="{{ $batas[$kolom] }}">{{ $isian[$kolom] }}</textarea>
                </div>
            @endforeach
        </div>

        <h4 class="kfr-sub kfr-sub-pisah">Lembar uji fungsi <span>hasil yang didapat dicetak dari Objek</span></h4>
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <label class="fw-bold mb-2" for="kfr_diagnosis_fungsional">Diagnosis fungsi</label>
                <input type="text" class="form-control" name="kfr[diagnosis_fungsional]" id="kfr_diagnosis_fungsional"
                    aria-label="Diagnosis fungsi, lembar uji fungsi" data-batas="{{ $batas['diagnosis_fungsional'] }}" value="{{ $isian['diagnosis_fungsional'] }}">
            </div>
            @foreach(['kesimpulan' => 'Kesimpulan', 'rekomendasi' => 'Rekomendasi'] as $kolom => $label)
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="fw-bold mb-2" for="kfr_{{ $kolom }}">{{ $label }}</label>
                    <textarea class="form-control" name="kfr[{{ $kolom }}]" id="kfr_{{ $kolom }}" rows="2" data-batas="{{ $batas[$kolom] }}">{{ $isian[$kolom] }}</textarea>
                </div>
            @endforeach
        </div>
    </div>
</section>
<script src="{{ asset('js/ralan/kfr.js') }}?v={{ filemtime(public_path('js/ralan/kfr.js')) }}"></script>
