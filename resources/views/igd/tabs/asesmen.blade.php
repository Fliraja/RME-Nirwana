@php
    use App\Services\Igd\AsesmenIgdService as A;

    $nilai = fn (string $k) => (string) ($asesmen->{$k} ?? '');
    $organ = [
        'kepala' => 'Kepala', 'mata' => 'Mata', 'gigi' => 'Gigi & mulut', 'leher' => 'Leher',
        'thoraks' => 'Thoraks', 'abdomen' => 'Abdomen', 'genital' => 'Genital & anus', 'ekstremitas' => 'Ekstremitas',
    ];
    $vital = [
        'gcs' => ['GCS', '', 'text', 'E4V5M6'],
        'td'  => ['TD', 'mmHg', 'text', '120/80'],
        'nadi' => ['Nadi', 'x/menit', 'numeric', ''],
        'rr'  => ['RR', 'x/menit', 'numeric', ''],
        'suhu' => ['Suhu', '°C', 'decimal', ''],
        'spo' => ['SpO2', '%', 'numeric', ''],
        'bb'  => ['BB', 'kg', 'decimal', ''],
        'tb'  => ['TB', 'cm', 'decimal', ''],
    ];
    $teks = function (string $k, string $label, int $baris = 2, bool $wajib = false, int $maks = 10000) use ($nilai) {
        $id = 'as-' . $k;
        return '<label class="igd-label" for="' . $id . '">' . e($label) . ($wajib ? ' <span class="igd-wajib">wajib</span>' : '') . '</label>'
            . '<textarea class="form-control form-control-sm" id="' . $id . '" name="' . $k . '" rows="' . $baris . '" maxlength="' . $maks . '"'
            . ' aria-describedby="galat-' . $id . '">' . e($nilai($k)) . '</textarea>'
            . '<div class="igd-galat" id="galat-' . $id . '" data-galat="' . $k . '"></div>';
    };
@endphp
<form class="igd-asesmen" id="formAsesmenIgd" novalidate>
    <input type="hidden" name="no_rawat" value="{{ $noRawat }}">
    <input type="hidden" name="sidik" value="{{ $sidik }}">

    <p class="igd-asesmen-jejak" data-jejak>
        @if($jejak)
            <i class="fas fa-user-doctor" aria-hidden="true"></i> {{ $jejak }}
        @else
            Asesmen belum ditulis. Isian bertanda wajib harus diisi sebelum simpan.
        @endif
    </p>

    <section class="resume-isian" aria-labelledby="judul-as-riwayat">
        <div class="resume-isian-kepala">
            <span class="resume-isian-ikon" aria-hidden="true"><i class="fas fa-comment-medical"></i></span>
            <h3 class="resume-isian-judul" id="judul-as-riwayat">I. Riwayat kesehatan</h3>
        </div>
        <div class="resume-isian-badan">
            <div class="row g-2">
                <div class="col-12">{!! $teks('keluhan_utama', 'Keluhan utama', 2, true, 2000) !!}</div>
                <div class="col-12">{!! $teks('rps', 'Riwayat penyakit sekarang', 2, false, 2000) !!}</div>
                <div class="col-md-6">{!! $teks('rpd', 'Riwayat penyakit dahulu', 1, false, 1000) !!}</div>
                <div class="col-md-6">{!! $teks('rpk', 'Riwayat penyakit keluarga', 1, false, 1000) !!}</div>
                <div class="col-md-6">{!! $teks('rpo', 'Riwayat pengobatan', 1, false, 1000) !!}</div>
                <div class="col-md-6">
                    <label class="igd-label" for="as-alergi">Riwayat alergi</label>
                    <input type="text" class="form-control form-control-sm" id="as-alergi" name="alergi" maxlength="100" value="{{ $nilai('alergi') }}" aria-describedby="galat-as-alergi">
                    <div class="igd-galat" id="galat-as-alergi" data-galat="alergi"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="resume-isian" aria-labelledby="judul-as-fisik">
        <div class="resume-isian-kepala">
            <span class="resume-isian-ikon" aria-hidden="true"><i class="fas fa-stethoscope"></i></span>
            <h3 class="resume-isian-judul" id="judul-as-fisik">II. Pemeriksaan fisik</h3>
        </div>
        <div class="resume-isian-badan">
            <div class="igd-asesmen-baris">
                @include('igd.tabs.partials.pilihan', ['nama' => 'anamnesis', 'label' => 'Anamnesis', 'opsi' => A::PILIHAN['anamnesis'], 'nilai' => $nilai('anamnesis'),
                    'tampil' => ['Autoanamnesis' => 'Auto', 'Alloanamnesis' => 'Allo']])
                <div class="igd-hubungan" data-hubungan @if($nilai('anamnesis') !== 'Alloanamnesis') hidden @endif>
                    <label class="igd-label" for="as-hubungan">Hubungan dengan pasien <span class="igd-wajib">wajib</span></label>
                    <input type="text" class="form-control form-control-sm" id="as-hubungan" name="hubungan" maxlength="100" value="{{ $nilai('hubungan') }}"
                           placeholder="mis. istri, anak" aria-describedby="galat-as-hubungan">
                    <div class="igd-galat" id="galat-as-hubungan" data-galat="hubungan"></div>
                </div>
            </div>
            @include('igd.tabs.partials.pilihan', ['nama' => 'keadaan', 'label' => 'Keadaan umum', 'opsi' => A::PILIHAN['keadaan'], 'nilai' => $nilai('keadaan')])
            @include('igd.tabs.partials.pilihan', ['nama' => 'kesadaran', 'label' => 'Kesadaran', 'opsi' => A::PILIHAN['kesadaran'], 'nilai' => $nilai('kesadaran')])

            <h4 class="igd-subjudul">Tanda vital</h4>
            <div class="igd-vital">
                @foreach($vital as $k => [$label, $satuan, $mode, $contoh])
                    <div class="igd-vital-isi">
                        <label class="igd-label" for="as-{{ $k }}">{{ $label }}</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="as-{{ $k }}" name="{{ $k }}" value="{{ $nilai($k) }}"
                                   maxlength="{{ $k === 'td' ? 7 : ($k === 'gcs' ? 10 : 5) }}" autocomplete="off"
                                   @if($mode !== 'text') inputmode="{{ $mode }}" @endif
                                   @if($contoh) placeholder="{{ $contoh }}" @endif aria-describedby="galat-as-{{ $k }}">
                            @if($satuan)<span class="input-group-text">{{ $satuan }}</span>@endif
                        </div>
                        <div class="igd-galat" id="galat-as-{{ $k }}" data-galat="{{ $k }}"></div>
                    </div>
                @endforeach
            </div>

            <div class="igd-organ-kepala">
                <h4 class="igd-subjudul mb-0">Pemeriksaan organ</h4>
                <button type="button" class="btn btn-sm igd-btn-garis" data-semua-normal>Tandai semua Normal</button>
            </div>
            <div class="igd-organ">
                @foreach($organ as $k => $label)
                    @include('igd.tabs.partials.pilihan', ['nama' => $k, 'label' => $label, 'opsi' => A::PILIHAN['organ'], 'nilai' => $nilai($k),
                        'wajib' => false, 'kelasGrup' => 'igd-pilih-organ',
                        'tampil' => ['Tidak Diperiksa' => 'Tidak diperiksa'],
                        'kelasPilihan' => ['Normal' => 'opsi-normal', 'Abnormal' => 'opsi-abnormal', 'Tidak Diperiksa' => 'opsi-tidak']])
                @endforeach
            </div>
            <p class="igd-bantu mt-1">Kedelapan organ wajib dipilih. Tandai semua Normal hanya mengisi organ yang belum dipilih.</p>

            <div class="mt-2">
                {!! $teks('ket_fisik', 'Keterangan fisik', 2) !!}
                <p class="igd-ingat" data-ingat-abnormal hidden></p>
            </div>
        </div>
    </section>

    <section class="resume-isian" aria-labelledby="judul-as-lokalis">
        <div class="resume-isian-kepala">
            <span class="resume-isian-ikon" aria-hidden="true"><i class="fas fa-person"></i></span>
            <h3 class="resume-isian-judul" id="judul-as-lokalis">III. Status lokalis</h3>
        </div>
        <div class="resume-isian-badan igd-lokalis">
            <figure class="igd-lokalis-gambar">
                <img src="{{ asset('img/semua.png') }}" width="506" height="334" loading="lazy"
                     alt="Gambar tubuh bernomor 1 sampai 12: 1 dan 6 kepala dari samping, 2 tubuh depan, 3 tubuh belakang, 4 dan 5 telapak tangan, 7 dan 8 punggung tangan, 9 dan 10 punggung kaki, 11 dan 12 telapak kaki.">
                <figcaption>Gambar acuan. Sebut nomor area di keterangan, mis. 2: luka lecet dahi 2 cm.</figcaption>
            </figure>
            <div class="igd-lokalis-isi">{!! $teks('ket_lokalis', 'Keterangan lokalis', 5) !!}</div>
        </div>
    </section>

    <section class="resume-isian" aria-labelledby="judul-as-penunjang">
        <div class="resume-isian-kepala">
            <span class="resume-isian-ikon" aria-hidden="true"><i class="fas fa-flask-vial"></i></span>
            <h3 class="resume-isian-judul" id="judul-as-penunjang">IV. Pemeriksaan penunjang</h3>
        </div>
        <div class="resume-isian-badan">
            <div class="igd-penunjang">
                <div>{!! $teks('ekg', 'EKG', 2) !!}</div>
                <div>
                    {!! $teks('rad', 'Radiologi', 2) !!}
                    <div class="resume-ref" data-ref="radiologi" data-target="rad" aria-live="polite"></div>
                </div>
                <div>
                    {!! $teks('lab', 'Laboratorium', 2) !!}
                    <div class="resume-ref" data-ref="lab" data-target="lab" aria-live="polite"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="resume-isian" aria-labelledby="judul-as-diagnosis">
        <div class="resume-isian-kepala">
            <span class="resume-isian-ikon" aria-hidden="true"><i class="fas fa-file-medical"></i></span>
            <h3 class="resume-isian-judul" id="judul-as-diagnosis">V. Diagnosis</h3>
        </div>
        <div class="resume-isian-badan">{!! $teks('diagnosis', 'Diagnosis kerja', 2, true, 500) !!}</div>
    </section>

    <section class="resume-isian" aria-labelledby="judul-as-tata">
        <div class="resume-isian-kepala">
            <span class="resume-isian-ikon" aria-hidden="true"><i class="fas fa-prescription-bottle-medical"></i></span>
            <h3 class="resume-isian-judul" id="judul-as-tata">VI. Tatalaksana</h3>
        </div>
        <div class="resume-isian-badan">
            {!! $teks('tata', 'Tatalaksana', 3) !!}
            <div class="resume-ref" data-ref="tatalaksana" data-target="tata" aria-live="polite"></div>
        </div>
    </section>

    <div class="resume-bilah-simpan igd-asesmen-bilah">
        <div class="igd-asesmen-status">
            <span class="resume-catatan" data-info-simpan>{{ $meta ? 'Tersimpan ' . A::waktu($meta->diubah_pada) : ($asesmen ? 'Ditulis di Khanza desktop' : 'Belum disimpan') }}</span>
            <div class="igd-asesmen-pesan" data-pesan role="alert"></div>
        </div>
        <div class="resume-aksi d-flex gap-2">
            {{-- Cetak memakai asesmen tersimpan terakhir; sebelum pernah disimpan belum ada yang bisa dicetak. --}}
            <a class="btn igd-btn-garis" data-cetak href="{{ route('igd.asesmen.pdf', str_replace('/', '-', $noRawat)) }}" target="_blank" rel="noopener"
               @unless($asesmen) hidden @endunless>Cetak PDF</a>
            <button type="button" class="btn igd-btn-garis" data-cetak-nonaktif disabled aria-describedby="igd-cetak-ket"
                    @if($asesmen) hidden @endif>Cetak PDF</button>
            <span class="visually-hidden" id="igd-cetak-ket">Simpan asesmen dulu sebelum mencetak.</span>
            <button type="submit" class="btn igd-btn-utama" data-simpan>Simpan asesmen</button>
        </div>
    </div>
</form>
