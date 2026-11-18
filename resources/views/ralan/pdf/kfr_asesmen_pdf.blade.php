@php
    // Formulir kertas RS "FORMULIR ASESMEN LAYANAN KEDOKTERAN FISIK DAN REHABILITASI". Data dari KfrService::dataPdf().
    $pasien = $reg->pasien;
    $isi = fn ($v) => trim((string) $v) !== '' ? trim((string) $v) : '-';
    $denganKode = fn ($teks, $kode) => $isi($teks) . ($kode !== '' ? ' (' . $kode . ')' : '');
    $suspek = $kfr['suspek'] === 'Ya' ? trim('Ya, ' . $kfr['ket_suspek'], ', ') : 'Tidak';
    $baris = [
        'Anamnesis'                    => $isi($soap['anamnesa']),
        'Pemeriksaan Fisik Uji Fungsi' => $isi($soap['pemeriksaan']),
        'Diagnosis Medis (ICD-10)'     => $denganKode($soap['diagnosis'], $kodeDx),
        'Diagnosis Fungsi (ICD-10)'    => $isi($kfr['diagnosa_fungsi']),
        'Pemeriksaan Penunjang'        => $isi($kfr['penunjang']),
        'Tatalaksana KFR'              => $denganKode($soap['tatalaksana'], $kodePr),
        'Target/Goal'                  => $isi($kfr['target']),
        'Edukasi'                      => $isi($kfr['edukasi']),
        'Anjuran Frekuensi Terapi'     => $isi($frekuensi),
        'Rencana Durasi Terapi'        => $isi($durasi),
        'Suspek Penyakit Akibat Kerja' => $suspek,
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Asesmen Layanan KFR - {{ $reg->no_rawat }}</title>
    @include('ralan.pdf.kfr_gaya')
</head>
<body>
    @include('pdf.kop')

    <div class="judul">FORMULIR ASESMEN LAYANAN<br>KEDOKTERAN FISIK DAN REHABILITASI</div>

    <div class="bingkai">
        <div class="no-rm">No.RM : {{ $reg->no_rkm_medis }}</div>
        <table class="identitas">
            <tr><td class="id-lbl">Nama Pasien</td><td class="sep">:</td><td>{{ $isi($pasien->nm_pasien ?? '') }}</td></tr>
            <tr><td class="id-lbl">Umur</td><td class="sep">:</td><td>{{ $umur }}</td></tr>
            <tr><td class="id-lbl">No.HP</td><td class="sep">:</td><td>{{ $isi($pasien->no_tlp ?? '') }}</td></tr>
        </table>
    </div>

    <div class="bingkai" style="margin-top: 8px;">
        <div class="kepala">Diisi Dokter Sp.KFR</div>
        <table class="isian">
            <tr><td class="lbl">Tanggal Pelayanan</td><td class="sep">:</td><td>{{ $tglPelayanan }}</td></tr>
            @foreach($baris as $label => $nilai)
                <tr><td class="lbl lbl-butir"><span class="butir">&bull;</span>{{ $label }}</td><td class="sep">:</td><td class="teks">{{ $nilai }}</td></tr>
            @endforeach
        </table>
    </div>

    <table class="bingkai" style="margin-top: 10px; page-break-inside: avoid;">
        <tr>
            <td style="width: 50%; text-align: center; font-weight: bold; padding: 4px 6px; border-right: 0.8px solid #000;">
                Tanda Tangan Pasien
                <div class="ttd-ruang"></div>
            </td>
            <td style="width: 50%; text-align: center; padding: 4px 6px;">
                <strong>Dokter Sp.KFR</strong>
                <div class="ttd-ruang"></div>
                <div class="ttd-nama">{{ $dokter->nm_dokter ?? '' }}</div>
                @if($sip !== '')
                    <div class="ttd-sip">SIP.No.{{ $sip }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="catatan">Dicetak dari RME RSU Nirwana, {{ now()->format('d/m/Y H.i') }}.</div>
</body>
</html>
