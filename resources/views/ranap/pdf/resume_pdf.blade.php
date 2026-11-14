@php
    // Formulir RS "RESUME MEDIS" (rawat inap). Data dari RanapResumeService::getPdfData().
    $r = $resume;
    $pasien = $reg->pasien;
    $isi = fn ($v) => trim((string) $v);
    $tglJam = fn ($tgl, $jam) => \Carbon\Carbon::parse($tgl)->format('d/m/Y') . ' ' . substr((string) $jam, 0, 5);

    $sudahKeluar = $kamarAkhir && $kamarAkhir->stts_pulang !== '-' && $kamarAkhir->tgl_keluar && $kamarAkhir->tgl_keluar !== '0000-00-00';
    $masuk = $kamarMasuk ? str_replace(':', '.', $tglJam($kamarMasuk->tgl_masuk, $kamarMasuk->jam_masuk)) : '-';
    $keluar = $sudahKeluar ? str_replace(':', '.', $tglJam($kamarAkhir->tgl_keluar, $kamarAkhir->jam_keluar)) . ($lamaRawat > 0 ? " ({$lamaRawat} hari)" : '') : '-';
    $umur = trim(($reg->umurdaftar ?? '') . ' ' . ($reg->sttsumur ?? ''));
    $kamar = $kamarAkhir ? trim($kamarAkhir->kd_kamar . ' ' . ($kamarAkhir->kamar->bangsal->nm_bangsal ?? '') . ($kamarAkhir->kamar->kelas ?? '' ? ' (' . $kamarAkhir->kamar->kelas . ')' : '')) : '-';

    $riwayat = array_filter([
        $isi($r->keluhan_utama) !== '' ? 'Keluhan utama: ' . $isi($r->keluhan_utama) : '',
        $isi($r->alasan) !== '' ? 'Alasan dirawat: ' . $isi($r->alasan) : '',
        $isi($r->jalannya_penyakit),
    ], 'strlen');
    $penunjang = array_filter([
        $isi($r->hasil_laborat),
        $isi($r->pemeriksaan_penunjang),
        $isi($r->lab_belum) !== '' ? 'Hasil yang belum keluar: ' . $isi($r->lab_belum) : '',
    ], 'strlen');
    $prosedur = array_filter([$r->prosedur_utama, $r->prosedur_sekunder, $r->prosedur_sekunder2, $r->prosedur_sekunder3], fn ($v) => trim((string) $v) !== '');
    $terapi = array_filter([
        implode(', ', $prosedur),
        $isi($r->tindakan_dan_operasi),
        $isi($r->obat_di_rs) !== '' ? 'Terapi selama dirawat: ' . $isi($r->obat_di_rs) : '',
    ], 'strlen');
    $adaKontrol = !empty($r->kontrol) && $r->kontrol !== '0000-00-00 00:00:00';
    $instruksi = array_filter([
        $adaKontrol ? 'Kontrol: ' . \Carbon\Carbon::parse($r->kontrol)->format('d/m/Y H.i') : '',
        $isi($r->obat_pulang) !== '' ? 'Obat pulang: ' . $isi($r->obat_pulang) : '',
        $isi($r->edukasi),
        $isi($r->diet) !== '' ? 'Diet: ' . $isi($r->diet) : '',
    ], 'strlen');
    $sekunder = [$r->diagnosa_sekunder, $r->diagnosa_sekunder2, $r->diagnosa_sekunder3, $r->diagnosa_sekunder4];
    $kotak = fn ($ya) => '<span class="cek">' . ($ya ? '&#9745;' : '&#9744;') . '</span>';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Resume Medis Rawat Inap - {{ $reg->no_rawat }}</title>
    <style>
        @page { margin: 1cm 1.2cm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; line-height: 1.35; color: #000; }
        .label-draf { border: 1px solid #92400e; color: #92400e; text-align: center; font-weight: bold; padding: 3px; margin-bottom: 6px; }
        table.blok { width: 100%; border-collapse: collapse; }
        table.blok > tr > td, table.blok > tbody > tr > td { border: 0.8px solid #000; padding: 4px 6px; vertical-align: top; }
        .judul { text-align: center; font-weight: bold; font-size: 11pt; padding: 5px; }
        .lbl { font-size: 8pt; }
        .teks { white-space: pre-line; }
        /* Kotak centang: Helvetica dompdf tidak punya glyph ini, DejaVu Sans punya. */
        .cek { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; }
        table.rm { border-collapse: collapse; margin-top: 3px; }
        table.rm td { border: 0.8px solid #000; width: 0.55cm; height: 0.5cm; text-align: center; vertical-align: middle; padding: 0; font-weight: bold; }
        .tambahan { font-size: 7.5pt; color: #333; }
        .alergi { color: #be123c; font-weight: bold; }
        .tinggi-1 { height: 1.3cm; }
        .tinggi-2 { height: 1.9cm; }
        table.dx, table.kondisi { width: 100%; border-collapse: collapse; }
        table.dx td, table.kondisi td { border: 0; padding: 1px 0; vertical-align: top; }
        table.dx .kode { width: 4.2cm; }
        table.kondisi td { padding: 2px 0; }
        .ttd { text-align: center; }
        .ttd-ruang { height: 1.6cm; }
        .catatan { margin-top: 6px; font-size: 6.5pt; color: #555; }
    </style>
</head>
<body>
    @include('pdf.kop')

    @unless($selesai)
        <div class="label-draf">DRAF, BELUM DITANDAI SELESAI</div>
    @endunless

    <table class="blok">
        <tr><td colspan="3" class="judul">RESUME MEDIS</td></tr>
        <tr>
            <td style="width: 38%;"><span class="lbl">NAMA :</span><br><strong>{{ $pasien->nm_pasien ?? '-' }}</strong></td>
            <td style="width: 24%;"><span class="lbl">JENIS KELAMIN</span><br>
                {!! $kotak(($pasien->jk ?? '') === 'L') !!} LAKI-LAKI<br>{!! $kotak(($pasien->jk ?? '') === 'P') !!} PEREMPUAN</td>
            <td><span class="lbl">NO. RM</span>
                <table class="rm"><tr>@foreach(str_split((string) $reg->no_rkm_medis) as $c)<td>{{ $c }}</td>@endforeach</tr></table>
            </td>
        </tr>
        <tr>
            <td><span class="lbl">UMUR :</span> {{ $umur !== '' ? $umur : '-' }}</td>
            <td><span class="lbl">TANGGAL MASUK</span><br>{{ $masuk }}</td>
            <td><span class="lbl">TANGGAL KELUAR</span><br>{{ $keluar }}</td>
        </tr>
        <tr><td colspan="3" class="tambahan">
            Kamar: {{ $kamar }} &nbsp;&middot;&nbsp; DPJP: {{ $dpjp->nm_dokter ?? '-' }}
            @if(!empty($alergiPasien))
                &nbsp;&middot;&nbsp; <span class="alergi">Alergi: {{ $alergiPasien }}</span>
            @endif
        </td></tr>
        <tr><td colspan="3" class="tinggi-2"><span class="lbl">RIWAYAT PENYAKIT</span><div class="teks">{{ implode("\n", $riwayat) }}</div></td></tr>
        <tr><td colspan="3" class="tinggi-1"><span class="lbl">PEMERIKSAAN FISIK</span><div class="teks">{{ $isi($r->pemeriksaan_fisik) }}</div></td></tr>
        <tr><td colspan="3" class="tinggi-1"><span class="lbl">PEMERIKSAAN PENUNJANG (LABORATORIUM, KONSULTASI, DSB)</span><div class="teks">{{ implode("\n", $penunjang) }}</div></td></tr>
        <tr><td colspan="3">
            <table class="dx">
                <tr><td class="lbl">DIAGNOSA PRIMER</td><td class="kode lbl">KODE ICD : {{ $kodeDiagnosa[0] ?? '' }}</td></tr>
                <tr><td>{{ $isi($r->diagnosa_utama) }}</td><td></td></tr>
                <tr><td class="lbl" style="padding-top: 5px;">DIAGNOSA SEKUNDER</td><td></td></tr>
                @foreach($sekunder as $i => $dx)
                    <tr><td>{{ $i + 1 }}. {{ $isi($dx) }}</td><td class="kode lbl">KODE ICD : {{ $kodeDiagnosa[$i + 1] ?? '' }}</td></tr>
                @endforeach
            </table>
        </td></tr>
        <tr><td colspan="3" class="tinggi-2"><span class="lbl">TINDAKAN / TERAPI YANG DIBERIKAN</span><div class="teks">{{ implode("\n", $terapi) }}</div>
            @if(!empty($kodeProsedur))
                <div class="lbl">KODE ICD 9 : {{ implode(', ', $kodeProsedur) }}</div>
            @endif
        </td></tr>
        <tr><td colspan="3">
            <span class="lbl">KONDISI PASIEN WAKTU PULANG</span>
            <table class="kondisi">
                <tr>
                    <td style="width: 30%;">{!! $kotak($kondisi === 'membaik') !!} SEMBUH/MEMBAIK</td>
                    <td style="width: 40%;">{!! $kotak($kondisi === 'aps') !!} PULANG ATAS PERMINTAAN SENDIRI</td>
                    <td>{!! $kotak(str_starts_with((string) $kondisi, 'meninggal')) !!} MENINGGAL DUNIA</td>
                </tr>
                <tr>
                    <td colspan="2">{!! $kotak($kondisi === 'dirujuk') !!} DIRUJUK KE {{ $kondisi === 'dirujuk' ? $isi($r->ket_keluar) : '' }}</td>
                    <td>&nbsp;&nbsp;&nbsp;{!! $kotak($kondisi === 'meninggal_kurang_48') !!} &lt; 48 jam &nbsp; {!! $kotak($kondisi === 'meninggal_lebih_48') !!} <span class="cek">&ge;</span> 48 jam</td>
                </tr>
            </table>
        </td></tr>
        <tr><td colspan="3" class="tinggi-1"><span class="lbl">INSTRUKSI FOLLOW-UP/TINDAK LANJUT</span><div class="teks">{{ implode("\n", $instruksi) }}</div></td></tr>
        <tr><td colspan="3" class="ttd" style="padding-left: 55%;">
            {{-- Nama hanya untuk resume Selesai, oleh dokter yang menandainya. Tanpa SIP: no_ijn_praktek banyak kosong. --}}
            @if($selesai && $penanda)
                <div>Banjarbaru, {{ $selesaiPada->translatedFormat('d F Y') }}</div>
            @endif
            <div class="lbl">DOKTER YANG MERAWAT,</div>
            <div class="ttd-ruang"></div>
            @if($selesai && $penanda)
                <div class="ttd-nama">( <strong>{{ $penanda->nm_dokter }}</strong> )</div>
            @else
                <div>( ...................................... )</div>
            @endif
        </td></tr>
    </table>

    <div class="catatan">Dicetak dari RME RSU Nirwana, {{ now()->format('d/m/Y H.i') }}.</div>
</body>
</html>
