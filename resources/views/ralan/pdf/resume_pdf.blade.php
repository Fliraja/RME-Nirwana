@php
    $r = $resume;
    $pasien = $reg->pasien;
    $diagnosa = array_values(array_filter([$r->diagnosa_utama, $r->diagnosa_sekunder, $r->diagnosa_sekunder2, $r->diagnosa_sekunder3, $r->diagnosa_sekunder4], 'strlen'));
    $prosedur = array_values(array_filter([$r->prosedur_utama, $r->prosedur_sekunder, $r->prosedur_sekunder2, $r->prosedur_sekunder3], 'strlen'));
    $teks = fn ($s) => trim((string) $s) !== '' ? nl2br(e($s)) : '-';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Resume Medis Rawat Jalan - {{ $reg->no_rawat }}</title>
    <style>
        @page { margin: 1cm 1.2cm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; line-height: 1.35; color: #222; }
        .kop { width: 100%; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
        .kop img { height: 48px; }
        .kop h1 { font-size: 13pt; margin: 0; color: #0d7a4a; }
        .kop p { margin: 1px 0; font-size: 7.5pt; }
        .judul { text-align: center; font-weight: bold; font-size: 10pt; margin: 6px 0 10px; }
        .label-tunda { border: 1px solid #92400e; color: #92400e; text-align: center; font-weight: bold; padding: 4px; margin-bottom: 8px; }
        table.info { width: 100%; margin-bottom: 8px; }
        table.info td { padding: 1px 3px; vertical-align: top; }
        .lbl { width: 18%; font-weight: bold; }
        table.isi { width: 100%; border-collapse: collapse; }
        table.isi td { border: 1px solid #999; padding: 5px 6px; vertical-align: top; }
        .seksi { background: #eef5f1; font-weight: bold; color: #0d7a4a; }
        .kode { font-size: 7.5pt; color: #444; margin-top: 3px; }
        .ttd { width: 100%; margin-top: 14px; }
        .catatan { margin-top: 12px; font-size: 7pt; color: #555; border-top: 1px dashed #aaa; padding-top: 4px; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td style="width: 70px;"><img src="{{ public_path('img/RSU22Nirwana1.png') }}" alt="Logo RSU Nirwana"></td>
            <td style="text-align: right;">
                <h1>RUMAH SAKIT UMUM NIRWANA</h1>
                <p>JL. PANGLIMA BATUR TIMUR NO.42 BANJARBARU KALIMANTAN SELATAN</p>
                <p>Telp: 0511-674 9272 / 0851 0124 0608 | Email: rsu.nirwana@gmail.com</p>
            </td>
        </tr>
    </table>

    <div class="judul">RESUME MEDIS RAWAT JALAN</div>
    @unless($diverifikasi)
        <div class="label-tunda">BELUM DIVERIFIKASI DOKTER</div>
    @endunless

    <table class="info">
        <tr><td class="lbl">No. Rekam Medis</td><td>: {{ $reg->no_rkm_medis }}</td><td class="lbl">Tanggal Kunjungan</td><td>: {{ \Carbon\Carbon::parse($reg->tgl_registrasi)->translatedFormat('d F Y') }}</td></tr>
        <tr><td class="lbl">Nama Pasien</td><td>: <strong>{{ $pasien->nm_pasien ?? '-' }}</strong></td><td class="lbl">Poli</td><td>: {{ $reg->poliklinik->nm_poli ?? '-' }}</td></tr>
        <tr><td class="lbl">JK / Umur</td><td>: {{ ($pasien->jk ?? '') === 'P' ? 'Perempuan' : 'Laki-Laki' }} / {{ $reg->umurdaftar }} {{ $reg->sttsumur }}</td><td class="lbl">Dokter</td><td>: {{ $reg->dokter->nm_dokter ?? '-' }}</td></tr>
        <tr><td class="lbl">No. Rawat</td><td>: {{ $reg->no_rawat }}</td><td class="lbl">Penjamin</td><td>: {{ $reg->penjab->png_jawab ?? '-' }}</td></tr>
    </table>

    <table class="isi">
        <tr><td class="seksi">Keluhan Utama</td></tr>
        <tr><td>{!! $teks($r->keluhan_utama) !!}</td></tr>
        <tr><td class="seksi">Pemeriksaan</td></tr>
        <tr><td>{!! $teks($r->jalannya_penyakit) !!}</td></tr>
        <tr><td class="seksi">Hasil Laboratorium</td></tr>
        <tr><td>{!! $teks($r->hasil_laborat) !!}</td></tr>
        <tr><td class="seksi">Pemeriksaan Penunjang Lain</td></tr>
        <tr><td>{!! $teks($r->pemeriksaan_penunjang) !!}</td></tr>
        <tr><td class="seksi">Diagnosis</td></tr>
        <tr>
            <td>
                @forelse($diagnosa as $i => $d)
                    <div>{{ $i === 0 ? 'Utama' : 'Sekunder ' . $i }}: {{ $d }}</div>
                @empty
                    -
                @endforelse
                @if($kodeDiagnosa)
                    <div class="kode">Kode ICD-10 (casemix): {{ implode(', ', $kodeDiagnosa) }}</div>
                @endif
            </td>
        </tr>
        <tr><td class="seksi">Prosedur / Tindakan</td></tr>
        <tr>
            <td>
                @forelse($prosedur as $i => $p)
                    <div>{{ $i === 0 ? 'Utama' : 'Sekunder ' . $i }}: {{ $p }}</div>
                @empty
                    -
                @endforelse
                @if($kodeProsedur)
                    <div class="kode">Kode ICD-9-CM (casemix): {{ implode(', ', $kodeProsedur) }}</div>
                @endif
            </td>
        </tr>
        <tr><td class="seksi">Obat Pulang</td></tr>
        <tr><td>{!! $teks($r->obat_pulang) !!}</td></tr>
        <tr><td class="seksi">Keadaan Saat Pulang / Tindak Lanjut</td></tr>
        <tr><td>{{ $tindakLanjut }}</td></tr>
    </table>

    <table class="ttd">
        <tr>
            <td style="width: 60%;"></td>
            <td style="text-align: center;">
                <div>Banjarbaru, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div style="font-weight: bold; margin-top: 3px;">Dokter Pemeriksa</div>
                <div style="margin-top: 45px; font-weight: bold; text-decoration: underline;">{{ $dokterTtd->nm_dokter ?? ' ' }}</div>
                @if($dokterTtd)
                    <div style="font-size: 7.5pt; color: #555;">{{ $dokterTtd->no_ijn_praktek ? 'SIP: ' . $dokterTtd->no_ijn_praktek : 'Kode Dokter: ' . $dokterTtd->kd_dokter }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="catatan">Dokumen Rekam Medis Elektronik ini dicetak dari Sistem RME RSU Nirwana.</div>
</body>
</html>
