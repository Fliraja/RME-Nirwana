@php
    $pilihanTl = ['pulang' => 'Pulang', 'kontrol' => 'Kontrol ulang', 'rawat_inap' => 'Rawat inap',
                  'dirujuk' => 'Dirujuk', 'aps' => 'Pulang APS', 'meninggal' => 'Meninggal dunia'];
    $salinanLabel = \App\Services\Ralan\ResumeRalanBuilder::SALINAN;
@endphp

<div id="resume-ralan" data-status="{{ $status }}">
@if(!$form)
    <div class="ralan-card">
        <div class="ralan-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Isi Penilaian (A) di SOAP untuk menyusun resume.</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-resume-ke-soap">Buka tab SOAP</button>
        </div>
    </div>
@else
    @if($adaDataBaru)
        <div class="resume-banner" id="resume-banner-baru" role="status">
            <span>Ada data baru sejak resume diverifikasi.</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-resume-perbarui">Perbarui</button>
        </div>
    @endif
    @if($pakaiTerbaru)
        <div class="resume-banner" role="status"><span>Form berisi data terbaru dan belum disimpan.</span></div>
    @endif

    <form id="formResumeRalan" novalidate>
        <input type="hidden" name="no_rawat" value="{{ $noRawat }}">
        <input type="hidden" name="sidik" value="{{ $form['sidik'] }}">

        <div class="ralan-card mb-3">
            <div class="ralan-card-head flex-wrap gap-2">
                <span class="d-flex align-items-center gap-2 flex-wrap">
                    Resume Medis Rawat Jalan
                    @if($status === 'diverifikasi')
                        <span class="resume-status resume-status-ok">
                            Diverifikasi {{ $verifikasi['nama'] }} · {{ $verifikasi['pada']?->translatedFormat('d M Y H.i') }}
                        </span>
                    @else
                        <span class="resume-status resume-status-tunggu">Otomatis, belum diverifikasi</span>
                    @endif
                </span>
                <span class="d-flex gap-2 resume-aksi">
                    @if($bolehVerifikasi)
                        <button type="submit" class="btn btn-sm btn-resume-utama">Simpan &amp; Verifikasi</button>
                    @endif
                </span>
            </div>

            <fieldset class="ralan-card-body" @disabled(!$bolehVerifikasi)>
                @unless($bolehVerifikasi)
                    <p class="resume-catatan">Hanya dokter pemeriksa yang dapat memverifikasi resume ini.</p>
                @endunless

                <h3 class="resume-judul" id="resume-judul-diagnosa">Diagnosis <span class="resume-sumber-badge">dari SOAP · A</span></h3>
                <div class="resume-daftar" id="resume-diagnosa" data-nama="diagnosa" data-maks="5" aria-labelledby="resume-judul-diagnosa">
                    @foreach(($form['diagnosa'] ?: ['']) as $teks)
                        @include('ralan.partials.resume_baris', ['nama' => 'diagnosa', 'nilai' => $teks])
                    @endforeach
                </div>
                <button type="button" class="btn btn-link btn-sm resume-tambah px-0">+ Tambah diagnosis</button>

                <h3 class="resume-judul mt-3" id="resume-judul-prosedur">Prosedur / tindakan</h3>
                <div class="resume-daftar" id="resume-prosedur" data-nama="prosedur" data-maks="4" aria-labelledby="resume-judul-prosedur">
                    @foreach($form['prosedur'] as $teks)
                        @include('ralan.partials.resume_baris', ['nama' => 'prosedur', 'nilai' => $teks])
                    @endforeach
                </div>
                <button type="button" class="btn btn-link btn-sm resume-tambah px-0">+ Tambah prosedur</button>
                @if($form['prosedur'] === [])
                    <p class="resume-catatan mb-1">Kosongkan bila kunjungan hanya konsultasi.</p>
                @endif
                @if($usulanProsedur)
                    <div class="resume-usulan">
                        <span class="resume-catatan">Usulan dari tindakan yang ditagih:</span>
                        @foreach($usulanProsedur as $usulan)
                            @unless(in_array($usulan, $form['prosedur'], true))
                                <button type="button" class="resume-chip" data-teks="{{ $usulan }}">+ {{ $usulan }}</button>
                            @endunless
                        @endforeach
                    </div>
                @endif

                @php $tl = $form['tindak_lanjut']; @endphp
                <h3 class="resume-judul mt-3" id="resume-judul-tl">Keadaan saat pulang / tindak lanjut</h3>
                <div class="resume-tl" role="radiogroup" aria-labelledby="resume-judul-tl">
                    @foreach($pilihanTl as $nilai => $teks)
                        @php
                            $aktif = $tl['nilai'] === $nilai;
                            $nonaktif = $tl['terkunci'] ? !$aktif : $nilai === 'rawat_inap';
                        @endphp
                        <label class="resume-tl-pilihan {{ $aktif ? 'aktif' : '' }} {{ $nonaktif ? 'nonaktif' : '' }}">
                            <input type="radio" class="form-check-input mt-0" name="tindak_lanjut" value="{{ $nilai }}" @checked($aktif) @disabled($nonaktif)>
                            {{ $teks }}
                        </label>
                    @endforeach
                </div>
                @if($tl['terkunci'])
                    <input type="hidden" name="tindak_lanjut" value="{{ $tl['nilai'] }}">
                    <p class="resume-catatan mt-1">
                        Dari {{ $tl['sumber'] }}:
                        @foreach($tl['detail'] as $label => $isi){{ $label }} {{ $isi }}@if(!$loop->last) · @endif @endforeach
                    </p>
                @else
                    <div id="resume-tl-kontrol" class="mt-2 {{ $tl['nilai'] === 'kontrol' ? '' : 'd-none' }}">
                        <label class="form-label small" for="resume-tgl-kontrol">Tanggal kontrol ulang</label>
                        <input type="date" class="form-control form-control-sm resume-input-pendek" id="resume-tgl-kontrol" name="tgl_kontrol" value="{{ $tl['tgl_kontrol'] }}">
                    </div>
                    <div id="resume-tl-ket" class="mt-2 {{ in_array($tl['nilai'], ['dirujuk', 'aps'], true) ? '' : 'd-none' }}">
                        <label class="form-label small" for="resume-ket-tl" id="resume-tl-ket-label">{{ $tl['nilai'] === 'dirujuk' ? 'Nama RS / faskes tujuan' : 'Alasan (opsional)' }}</label>
                        <input type="text" class="form-control form-control-sm" id="resume-ket-tl" name="ket_tindak_lanjut" maxlength="100" value="{{ $tl['ket'] }}">
                    </div>
                    <p class="resume-catatan mt-1 mb-0">Rawat inap terisi otomatis dari pendaftaran rawat inap.</p>
                @endif
            </fieldset>
        </div>

        <div class="ralan-card">
            <div class="ralan-card-head flex-wrap gap-2"><span>Disalin otomatis</span><span class="resume-catatan mb-0">diperbarui saat sumber berubah</span></div>
            <fieldset class="ralan-card-body pt-1" @disabled(!$bolehVerifikasi)>
                @foreach($salinanLabel as $kolom => $label)
                    @php $teks = $form['salinan'][$kolom] ?? ''; @endphp
                    <div class="resume-salinan" data-kolom="{{ $kolom }}">
                        <div class="resume-salinan-label" id="resume-label-{{ $kolom }}">{{ $label }}</div>
                        <div class="resume-salinan-isi">
                            <div class="resume-salinan-teks">{{ $teks !== '' ? $teks : '-' }}</div>
                            <textarea name="{{ $kolom }}" class="form-control form-control-sm d-none" rows="4" aria-labelledby="resume-label-{{ $kolom }}">{{ $teks }}</textarea>
                        </div>
                        <button type="button" class="btn btn-link btn-sm resume-ubah">ubah</button>
                    </div>
                @endforeach
            </fieldset>
        </div>
    </form>
@endif
</div>
