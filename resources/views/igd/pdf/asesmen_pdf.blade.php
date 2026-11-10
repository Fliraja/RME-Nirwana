@php
    $a = $asesmen;
    $pasien = $reg->pasien;
    $teks = fn ($s) => trim((string) $s) !== '' ? nl2br(e(trim($s))) : '-';
    $nilai = fn ($s, $satuan = '') => trim((string) $s) !== '' ? e(trim($s)) . ($satuan ? ' ' . $satuan : '') : '-';
    $tanggal = \Carbon\Carbon::parse($a->tanggal);
    $anamnesis = $a->anamnesis === 'Alloanamnesis' && trim($a->hubungan) !== ''
        ? 'Alloanamnesis (' . trim($a->hubungan) . ')' : $a->anamnesis;
    $organ = [
        'kepala' => 'Kepala', 'mata' => 'Mata', 'gigi' => 'Gigi & mulut', 'leher' => 'Leher',
        'thoraks' => 'Thoraks', 'abdomen' => 'Abdomen', 'genital' => 'Genital & anus', 'ekstremitas' => 'Ekstremitas',
    ];
    $tata = trim((string) $a->tata) === '' ? [] : preg_split('/\r\n|\r|\n/', trim($a->tata));
    $tataEkor = array_slice($tata, -2);
    $tataAwal = array_slice($tata, 0, max(0, count($tata) - 2));
    $vital = [
        ['GCS', 'gcs', ''], ['TD', 'td', 'mmHg'], ['Nadi', 'nadi', 'x/mnt'], ['RR', 'rr', 'x/mnt'],
        ['Suhu', 'suhu', '°C'], ['SpO2', 'spo', '%'], ['BB', 'bb', 'kg'], ['TB', 'tb', 'cm'],
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Penilaian Awal Medis IGD - {{ $a->no_rawat }}</title>
    <style>
        @page { margin: 0.8cm 1cm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8pt; line-height: 1.3; color: #222; }
        .kop { width: 100%; border-bottom: 2px solid #000; padding-bottom: 4px; margin-bottom: 6px; }
        .kop img { height: 40px; }
        .kop h1 { font-size: 12pt; margin: 0; color: #0d7a4a; }
        .kop p { margin: 0; font-size: 7pt; }
        .judul { text-align: center; font-weight: bold; font-size: 10pt; margin: 4px 0 6px; }
        table { border-collapse: collapse; }
        table.info { width: 100%; margin-bottom: 6px; }
        table.info td { padding: 1px 3px; vertical-align: top; }
        .lbl { width: 16%; font-weight: bold; }
        table.isi { width: 100%; }
        table.isi td { border: 1px solid #999; padding: 3px 5px; vertical-align: top; }
        .seksi { background: #eef5f1; font-weight: bold; color: #0d7a4a; }
        .sub { width: 22%; font-weight: bold; }
        .rapat td { border: 0 !important; padding: 0 8px 1px 0 !important; }
        .vital { white-space: nowrap; width: 25%; }
        .lokalis img { width: 5.6cm; }
        /* Baris tatalaksana tampil sebagai satu kotak: garis hanya di tepi luar. */
        table.tata td { border-top: 0; border-bottom: 0; padding-top: 0; padding-bottom: 0; }
        table.tata td.seksi { border-top: 1px solid #999; border-bottom: 1px solid #999; padding: 3px 5px; }
        table.tata-ekor tr:last-child td { border-bottom: 1px solid #999; padding-bottom: 3px; }
        .blok-akhir { page-break-inside: avoid; }
        .ttd { width: 100%; margin-top: 8px; }
        .catatan { margin-top: 8px; font-size: 6.5pt; color: #555; border-top: 1px dashed #aaa; padding-top: 3px; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td style="width: 60px;"><img src="{{ public_path('img/RSU22Nirwana1.png') }}" alt="Logo RSU Nirwana"></td>
            <td style="text-align: right;">
                <h1>RUMAH SAKIT UMUM NIRWANA</h1>
                <p>JL. PANGLIMA BATUR TIMUR NO.42 BANJARBARU KALIMANTAN SELATAN</p>
                <p>Telp: 0511-674 9272 / 0851 0124 0608 | Email: rsu.nirwana@gmail.com</p>
            </td>
        </tr>
    </table>

    <div class="judul">PENILAIAN AWAL MEDIS GAWAT DARURAT (IGD)</div>

    <table class="info">
        <tr><td class="lbl">No. Rekam Medis</td><td>: {{ $reg->no_rkm_medis }}</td><td class="lbl">No. Rawat</td><td>: {{ $a->no_rawat }}</td></tr>
        <tr><td class="lbl">Nama Pasien</td><td>: <strong>{{ $pasien->nm_pasien ?? '-' }}</strong></td><td class="lbl">Tanggal Asesmen</td><td>: {{ $tanggal->format('d/m/Y H:i') }}</td></tr>
        <tr><td class="lbl">JK / Tgl Lahir</td><td>: {{ ($pasien->jk ?? '') === 'P' ? 'Perempuan' : 'Laki-Laki' }} / {{ $pasien?->tgl_lahir ? \Carbon\Carbon::parse($pasien->tgl_lahir)->format('d/m/Y') : '-' }} ({{ $reg->umurdaftar }} {{ $reg->sttsumur }})</td><td class="lbl">Anamnesis</td><td>: {{ $anamnesis }}</td></tr>
        <tr><td class="lbl">Penjamin</td><td>: {{ $reg->penjab->png_jawab ?? '-' }}</td><td></td><td></td></tr>
    </table>

    <table class="isi">
        <tr><td class="seksi" colspan="2">I. RIWAYAT KESEHATAN</td></tr>
        <tr><td class="sub">Keluhan utama:</td><td>{!! $teks($a->keluhan_utama) !!}</td></tr>
        <tr><td class="sub">Riwayat penyakit sekarang:</td><td>{!! $teks($a->rps) !!}</td></tr>
        <tr><td class="sub">Riwayat penyakit dahulu:</td><td>{!! $teks($a->rpd) !!}</td></tr>
        <tr><td class="sub">Riwayat penyakit keluarga:</td><td>{!! $teks($a->rpk) !!}</td></tr>
        <tr><td class="sub">Riwayat pengobatan:</td><td>{!! $teks($a->rpo) !!}</td></tr>
        <tr><td class="sub">Riwayat alergi:</td><td>{!! $teks($a->alergi) !!}</td></tr>

        <tr><td class="seksi" colspan="2">II. PEMERIKSAAN FISIK</td></tr>
        <tr><td class="sub">Keadaan umum:</td><td>{{ $a->keadaan ?: '-' }}, kesadaran {{ $a->kesadaran ?: '-' }}</td></tr>
        <tr><td class="sub">Tanda vital:</td><td>
            <table class="rapat">
                @foreach(array_chunk($vital, 4) as $barisVital)
                    <tr>
                        @foreach($barisVital as [$label, $kolom, $satuan])
                            <td class="vital">{{ $label }}: {!! $nilai($a->{$kolom}, $satuan) !!}</td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </td></tr>
        <tr><td class="sub">Pemeriksaan organ:</td><td>
            <table class="rapat">
                @foreach(array_chunk($organ, 4, true) as $baris)
                    <tr>
                        @foreach($baris as $kolom => $label)
                            <td>{{ $label }}: {{ $a->{$kolom} ?: '-' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </td></tr>
        <tr><td class="sub">Keterangan fisik:</td><td>{!! $teks($a->ket_fisik) !!}</td></tr>

        <tr><td class="seksi" colspan="2">III. STATUS LOKALIS</td></tr>
        <tr class="lokalis">
            <td colspan="2">
                <table class="rapat" style="width: 100%;"><tr>
                    <td style="width: 6.3cm;"><img src="{{ public_path('img/semua.png') }}" alt="Gambar status lokalis"></td>
                    <td><strong>Keterangan:</strong><br>{!! $teks($a->ket_lokalis) !!}</td>
                </tr></table>
            </td>
        </tr>

        <tr><td class="seksi" colspan="2">IV. PEMERIKSAAN PENUNJANG</td></tr>
        <tr><td class="sub">EKG:</td><td>{!! $teks($a->ekg) !!}</td></tr>
        <tr><td class="sub">Radiologi:</td><td>{!! $teks($a->rad) !!}</td></tr>
        <tr><td class="sub">Laboratorium:</td><td>{!! $teks($a->lab) !!}</td></tr>

        <tr><td class="seksi" colspan="2">V. DIAGNOSIS</td></tr>
        <tr><td colspan="2">{!! $teks($a->diagnosis) !!}</td></tr>
    </table>

    {{-- Tatalaksana dipecah per baris supaya teks panjang bisa bersambung ke halaman 2; dua baris terakhir
         ikut blok tanda tangan sehingga tanda tangan tidak pernah tercetak sendirian di halaman baru. --}}
    <table class="isi tata">
        <tr><td class="seksi">VI. TATALAKSANA</td></tr>
        @foreach($tataAwal as $b)
            <tr><td>{{ $b === '' ? ' ' : $b }}</td></tr>
        @endforeach
    </table>
    <div class="blok-akhir">
        <table class="isi tata tata-ekor">
            @forelse($tataEkor as $b)
                <tr><td>{{ $b === '' ? ' ' : $b }}</td></tr>
            @empty
                <tr><td>-</td></tr>
            @endforelse
        </table>

        <table class="ttd">
            <tr>
                <td style="width: 60%;"></td>
                <td style="text-align: center;">
                    <div>Banjarbaru, {{ $tanggal->translatedFormat('d F Y') }} pukul {{ $tanggal->format('H.i') }}</div>
                    <div style="font-weight: bold; margin-top: 2px;">Dokter yang memeriksa</div>
                    <div style="margin-top: 38px; font-weight: bold; text-decoration: underline;">{{ $dokter->nm_dokter ?? $a->kd_dokter }}</div>
                    <div style="font-size: 7pt; color: #555;">{{ trim((string) ($dokter->no_ijn_praktek ?? '')) !== '' ? 'SIP: ' . trim($dokter->no_ijn_praktek) : 'Kode Dokter: ' . $a->kd_dokter }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="catatan">Dokumen Rekam Medis Elektronik ini dicetak dari Sistem RME RSU Nirwana.</div>
</body>
</html>
