@php
    $r = $resume;
    $nilai = fn (string $kolom) => (string) ($r->{$kolom} ?? '');
    $kunci = !$bolehMenulis;

    $baris = fn (array $kolom) => array_values(array_filter(array_map($nilai, $kolom), 'strlen')) ?: [''];
    $formDiagnosa = $baris(['diagnosa_utama', 'diagnosa_sekunder', 'diagnosa_sekunder2', 'diagnosa_sekunder3', 'diagnosa_sekunder4']);
    $formProsedur = $baris(['prosedur_utama', 'prosedur_sekunder', 'prosedur_sekunder2', 'prosedur_sekunder3']);

    // Pilihan kondisi pulang dibaca balik dari kolom Khanza; resume baru tidak punya pilihan bawaan.
    $kondisi = null;
    if ($r && $nilai('keadaan') !== '') {
        $kondisi = match (true) {
            $r->cara_keluar === 'Pulang Atas Permintaan Sendiri' => 'aps',
            $r->keadaan === 'Meninggal' => str_contains((string) $r->ket_keadaan, '<') ? 'meninggal_kurang_48' : 'meninggal_lebih_48',
            $r->cara_keluar === 'Pindah RS' || $r->dilanjutkan === 'RS Lain' => 'dirujuk',
            $r->keadaan === 'Sembuh' => 'sembuh',
            default => 'membaik',
        };
    }
    $namaRujukan = $kondisi === 'dirujuk' ? ($nilai('ket_keluar') ?: $nilai('ket_dilanjutkan')) : '';
    $opsiKondisi = [
        'sembuh'              => ['Sembuh', 'Izin dokter', 'fa-check-circle'],
        'membaik'             => ['Membaik', 'Izin dokter', 'fa-arrow-trend-up'],
        'aps'                 => ['Pulang APS', 'Permintaan sendiri', 'fa-person-walking-arrow-right'],
        'dirujuk'             => ['Dirujuk', 'Pindah RS lain', 'fa-truck-medical'],
        'meninggal_kurang_48' => ['Meninggal < 48 jam', 'Sejak masuk RS', ''],
        'meninggal_lebih_48'  => ['Meninggal > 48 jam', 'Sejak masuk RS', ''],
    ];

    $labelStatus = ['belum' => 'Belum dibuat', 'draf' => 'Draf', 'selesai' => 'Selesai'][$status];
    $jumlahTerisi = count($terisi);
    $isianProps = fn (string $kunciIsian, int $nomor, string $ikon, string $judul, string $kolom) =>
        ['kunci' => $kunciIsian, 'nomor' => $nomor, 'ikon' => $ikon, 'judul' => $judul, 'kolom' => $kolom, 'terisi' => $terisi];
@endphp

<div id="wrapperResumeRanap" data-boleh="{{ $bolehMenulis ? 1 : 0 }}">
    <div class="ralan-card mb-3">
        <div class="ralan-card-head flex-wrap gap-2">
            <span class="d-flex align-items-center gap-2 flex-wrap">
                Resume Medis Rawat Inap
                <span id="badgeStatusResume" class="resume-status {{ $status === 'selesai' ? 'resume-status-ok' : 'resume-status-tunggu' }}">{{ $labelStatus }}</span>
                <span class="resume-catatan" id="resumeDiubah">
                    @if($meta)
                        Terakhir diubah {{ $meta->pengubah?->nm_dokter ?? $meta->diubah_oleh }}, {{ $meta->diubah_pada->format('d/m H:i') }}
                    @endif
                </span>
            </span>
            <span class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResumeKeCppt">
                    <i class="fas fa-notes-medical me-1" aria-hidden="true"></i>CPPT lengkap
                </button>
                <a href="{{ route('ranap.resume-pdf', ['no_rawat' => str_replace('/', '-', $reg->no_rawat)]) }}" target="_blank"
                   id="btnCetakResumePdf" class="btn btn-sm btn-outline-secondary {{ $r ? '' : 'disabled' }}"
                   @unless($r) aria-disabled="true" tabindex="-1" @endunless>
                    <i class="fas fa-file-pdf me-1" aria-hidden="true"></i>Cetak PDF
                </a>
            </span>
        </div>
        <div class="ralan-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span id="resumeProgresTeks" class="resume-catatan">{{ $jumlahTerisi }} dari 10 isian terisi</span>
                <button type="button" class="btn btn-link btn-sm p-0" id="btnMuatUlangReferensi">Muat ulang referensi</button>
            </div>
            <div class="resume-progres-jalur" role="progressbar" aria-label="Isian resume terisi"
                 aria-valuemin="0" aria-valuemax="10" aria-valuenow="{{ $jumlahTerisi }}">
                <span style="width: {{ $jumlahTerisi * 10 }}%"></span>
            </div>
            <p class="resume-catatan mb-0 mt-2">Kotak hijau di bawah tiap isian berisi catatan yang sudah ada sebagai bahan. Isian berubah hanya saat Anda menekan "Pakai" atau chip.</p>
        </div>
    </div>

    @if($kunci)
        <div class="resume-banner" role="status">{{ \App\Services\Ranap\ResumeRanapService::PESAN_TIDAK_BERHAK }}</div>
    @endif

    <form id="formResumePasienRanap" novalidate>
        @csrf
        <input type="hidden" name="no_rawat" value="{{ $reg->no_rawat }}">
        <input type="hidden" name="diubah_pada" value="{{ $meta?->diubah_pada?->format('Y-m-d H:i:s') }}">
        <fieldset @disabled($kunci)>

        @component('ranap.tabs.partials.resume_isian', $isianProps('keluhan', 1, 'fa-comment-medical', 'Keluhan utama & alasan dirawat', 'keluhan_utama'))
            <label class="form-label small fw-semibold" for="resume_keluhan_utama">Keluhan utama</label>
            <textarea name="keluhan_utama" id="resume_keluhan_utama" class="form-control" rows="3">{{ $nilai('keluhan_utama') }}</textarea>
            <div class="row g-2 mt-1">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="resume_alasan">Alasan dirawat</label>
                    <input type="text" name="alasan" id="resume_alasan" class="form-control form-control-sm" maxlength="100" value="{{ $nilai('alasan') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="resume_diagnosa_awal">Diagnosa awal masuk</label>
                    <input type="text" name="diagnosa_awal" id="resume_diagnosa_awal" class="form-control form-control-sm" maxlength="100" value="{{ $nilai('diagnosa_awal') }}">
                </div>
            </div>
            <div class="resume-ref" data-ref="keluhan" data-target="keluhan_utama"></div>
        @endcomponent

        @component('ranap.tabs.partials.resume_isian', $isianProps('perjalanan', 2, 'fa-timeline', 'Perjalanan penyakit selama dirawat', 'jalannya_penyakit'))
            <textarea name="jalannya_penyakit" id="resume_jalannya_penyakit" class="form-control" rows="5" aria-labelledby="judul-isian-perjalanan">{{ $nilai('jalannya_penyakit') }}</textarea>
            <div class="resume-ref" data-ref="perjalanan" data-target="jalannya_penyakit"></div>
        @endcomponent

        @component('ranap.tabs.partials.resume_isian', $isianProps('pemeriksaan', 3, 'fa-stethoscope', 'Pemeriksaan fisik saat pulang', 'pemeriksaan_fisik'))
            <textarea name="pemeriksaan_fisik" id="resume_pemeriksaan_fisik" class="form-control" rows="4" aria-labelledby="judul-isian-pemeriksaan">{{ $nilai('pemeriksaan_fisik') }}</textarea>
            <div class="resume-ref" data-ref="pemeriksaan" data-target="pemeriksaan_fisik"></div>
        @endcomponent

        @component('ranap.tabs.partials.resume_isian', $isianProps('penunjang', 4, 'fa-flask', 'Pemeriksaan penunjang', 'hasil_laborat pemeriksaan_penunjang'))
            <label class="form-label small fw-semibold" for="resume_hasil_laborat">Laboratorium</label>
            <textarea name="hasil_laborat" id="resume_hasil_laborat" class="form-control" rows="3">{{ $nilai('hasil_laborat') }}</textarea>
            <div class="resume-ref" data-ref="lab" data-target="hasil_laborat"></div>
            <label class="form-label small fw-semibold mt-3" for="resume_pemeriksaan_penunjang">Radiologi dan penunjang lain</label>
            <textarea name="pemeriksaan_penunjang" id="resume_pemeriksaan_penunjang" class="form-control" rows="3">{{ $nilai('pemeriksaan_penunjang') }}</textarea>
            <div class="resume-ref" data-ref="penunjang" data-target="pemeriksaan_penunjang"></div>
        @endcomponent

        @component('ranap.tabs.partials.resume_isian', $isianProps('diagnosis', 5, 'fa-file-medical', 'Diagnosis akhir', 'diagnosa[]'))
            <div class="resume-daftar" id="resume-ranap-diagnosa" data-nama="diagnosa" data-maks="5">
                @foreach($formDiagnosa as $teks)
                    @include('ranap.tabs.partials.resume_baris', ['nama' => 'diagnosa', 'nilai' => $teks])
                @endforeach
            </div>
            <button type="button" class="btn btn-link btn-sm resume-tambah px-0">
                <i class="fas fa-plus me-1" aria-hidden="true"></i>Tambah diagnosis
            </button>
            <span class="resume-catatan ms-2">Maksimal 5 baris. Kode ICD diisi casemix.</span>
            <div class="resume-ref" data-ref="diagnosis" data-target="diagnosa"></div>
        @endcomponent

        @component('ranap.tabs.partials.resume_isian', $isianProps('tindakan', 6, 'fa-syringe', 'Tindakan / prosedur', 'prosedur[]'))
            <div class="resume-daftar" id="resume-ranap-prosedur" data-nama="prosedur" data-maks="4">
                @foreach($formProsedur as $teks)
                    @include('ranap.tabs.partials.resume_baris', ['nama' => 'prosedur', 'nilai' => $teks])
                @endforeach
            </div>
            <button type="button" class="btn btn-link btn-sm resume-tambah px-0">
                <i class="fas fa-plus me-1" aria-hidden="true"></i>Tambah prosedur
            </button>
            <span class="resume-catatan ms-2">Maksimal 4 baris.</span>
            <div class="resume-ref" data-ref="tindakan" data-target="prosedur"></div>
        @endcomponent

        @component('ranap.tabs.partials.resume_isian', $isianProps('terapi', 7, 'fa-pills', 'Terapi / obat selama dirawat', 'obat_di_rs'))
            <textarea name="obat_di_rs" id="resume_obat_di_rs" class="form-control" rows="3" aria-labelledby="judul-isian-terapi">{{ $nilai('obat_di_rs') }}</textarea>
            <div class="resume-ref" data-ref="terapi" data-target="obat_di_rs"></div>
        @endcomponent

        @component('ranap.tabs.partials.resume_isian', $isianProps('kondisi', 8, 'fa-bed', 'Kondisi waktu pulang', 'kondisi_pulang_opsi'))
            <div class="row g-2" role="radiogroup" aria-labelledby="judul-isian-kondisi">
                @foreach($opsiKondisi as $opsi => [$label, $ket, $ikon])
                    <div class="col-6 col-lg-4 col-xxl-2">
                        <label class="kondisi-pulang-card resume-kondisi {{ $kondisi === $opsi ? 'aktif' : '' }}" for="kondisi_{{ $opsi }}">
                            <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi"
                                   id="kondisi_{{ $opsi }}" value="{{ $opsi }}" @checked($kondisi === $opsi)>
                            <span>
                                <span class="d-block small fw-semibold">@if($ikon)<i class="fas {{ $ikon }} me-1" aria-hidden="true"></i>@endif{{ $label }}</span>
                                <span class="d-block resume-catatan">{{ $ket }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 {{ $kondisi === 'dirujuk' ? '' : 'd-none' }}" id="containerNamaRujukan">
                <label class="form-label small fw-semibold" for="resume_nama_faskes_rujukan">Rumah sakit / faskes rujukan tujuan</label>
                <input type="text" name="nama_faskes_rujukan" id="resume_nama_faskes_rujukan" class="form-control form-control-sm" maxlength="50" value="{{ $namaRujukan }}">
            </div>
            <div class="resume-ref" data-ref="kondisi"></div>
        @endcomponent

        @component('ranap.tabs.partials.resume_isian', $isianProps('obat_pulang', 9, 'fa-prescription-bottle-medical', 'Obat pulang', 'obat_pulang'))
            <textarea name="obat_pulang" id="resume_obat_pulang" class="form-control" rows="3" aria-labelledby="judul-isian-obat_pulang">{{ $nilai('obat_pulang') }}</textarea>
            <div class="resume-ref" data-ref="obat_pulang" data-target="obat_pulang"></div>
        @endcomponent

        @component('ranap.tabs.partials.resume_isian', $isianProps('instruksi', 10, 'fa-clipboard-list', 'Instruksi, edukasi & kontrol', 'edukasi kontrol'))
            <label class="form-label small fw-semibold" for="resume_edukasi">Instruksi dan edukasi</label>
            <textarea name="edukasi" id="resume_edukasi" class="form-control" rows="3">{{ $nilai('edukasi') }}</textarea>
            <div class="resume-ref" data-ref="instruksi" data-target="edukasi"></div>
            <div class="row g-2 mt-2">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="resume_kontrol">Tanggal kontrol</label>
                    <input type="date" name="kontrol" id="resume_kontrol" class="form-control form-control-sm" value="{{ $r?->kontrol ? substr($r->kontrol, 0, 10) : '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="resume_diet">Diet</label>
                    <textarea name="diet" id="resume_diet" class="form-control form-control-sm" rows="1">{{ $nilai('diet') }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="resume_lab_belum">Hasil lab yang belum keluar</label>
                    <textarea name="lab_belum" id="resume_lab_belum" class="form-control form-control-sm" rows="1">{{ $nilai('lab_belum') }}</textarea>
                </div>
            </div>
            <div class="resume-ref" data-ref="kontrol" data-target="kontrol"></div>
        @endcomponent

        </fieldset>

        <div class="resume-bilah-simpan">
            @if($bolehMenulis)
                <span class="resume-catatan" id="resumeInfoSimpan">
                    {{ $meta ? 'Tersimpan ' . $meta->diubah_pada->format('d/m H:i') : ($r ? 'Draf dibuat di luar aplikasi ini' : 'Belum disimpan') }}
                </span>
                <div class="resume-aksi d-flex gap-2">
                    <button type="submit" class="btn btn-outline-secondary" id="btnSimpanDraf" data-selesai="0">Simpan draf</button>
                    <button type="submit" class="btn btn-resume-utama" id="btnTandaiSelesai" data-selesai="1">Simpan &amp; Tandai Selesai</button>
                </div>
            @else
                <span class="resume-catatan">{{ \App\Services\Ranap\ResumeRanapService::PESAN_TIDAK_BERHAK }}</span>
            @endif
        </div>
    </form>
</div>
