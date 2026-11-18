@php
    // Formulir kertas RS "LEMBAR HASIL UJI FUNGSI/PROSEDUR KFR". Diagnosis medis tidak ada di kertas ini. Data dari KfrService::dataPdf().
    $pasien = $reg->pasien;
    $isi = fn ($v) => trim((string) $v) !== '' ? trim((string) $v) : '-';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Uji Fungsi KFR - {{ $reg->no_rawat }}</title>
    @include('ralan.pdf.kfr_gaya')
</head>
<body>
    @include('pdf.kop')

    <div class="judul">LEMBAR HASIL UJI FUNGSI/PROSEDUR KFR</div>

    <div class="bingkai">
        <div class="no-rm">No.RM : {{ $reg->no_rkm_medis }}</div>
        <table class="identitas">
            <tr><td class="id-lbl">Nama Pasien</td><td class="sep">:</td><td>{{ $isi($pasien->nm_pasien ?? '') }}</td></tr>
            <tr><td class="id-lbl">Umur</td><td class="sep">:</td><td>{{ $umur }}</td></tr>
            <tr><td class="id-lbl">Diagnosis Fungsi</td><td class="sep">:</td><td>{{ $isi($kfr['diagnosis_fungsional']) }}</td></tr>
            <tr><td class="id-lbl">Tanggal</td><td class="sep">:</td><td>{{ $tglPendek }}</td></tr>
        </table>
    </div>

    <div class="bingkai" style="margin-top: 8px;">
        <div class="kepala">Instrumen Uji Fungsi/Prosedur KFR</div>
        <table class="isian">
            <tr><td class="lbl">Hasil</td><td class="sep">:</td><td class="teks">{{ $isi($soap['pemeriksaan']) }}</td></tr>
            <tr><td class="lbl lbl-butir">Kesimpulan</td><td class="sep">:</td><td class="teks" style="height: 1cm;">{{ $isi($kfr['kesimpulan']) }}</td></tr>
            <tr><td class="lbl lbl-butir">Rekomendasi</td><td class="sep">:</td><td class="teks" style="height: 1cm;">{{ $isi($kfr['rekomendasi']) }}</td></tr>
        </table>
    </div>

    <table style="margin-top: 16px; page-break-inside: avoid;">
        <tr>
            <td style="width: 27%;"></td>
            <td style="width: 46%; text-align: center;">
                <strong>Dokter Sp.KFR</strong>
                <div class="ttd-ruang"></div>
                <div class="ttd-nama">{{ $dokter->nm_dokter ?? '' }}</div>
                @if($sip !== '')
                    <div class="ttd-sip">SIP.No.{{ $sip }}</div>
                @endif
            </td>
            <td style="width: 27%;"></td>
        </tr>
    </table>

    <div class="catatan">Dicetak dari RME RSU Nirwana, {{ now()->format('d/m/Y H.i') }}.</div>
</body>
</html>
