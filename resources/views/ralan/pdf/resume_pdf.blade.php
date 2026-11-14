@php
    // Formulir RS "RESUME MEDIS RAWAT JALAN". Data dari ResumeRalanService::dataPdf().
    $r = $resume;
    $pasien = $reg->pasien;
    $tgl = \Carbon\Carbon::parse($reg->tgl_registrasi)->format('d-m-Y');
    $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $kosongTulis = '<span class="kosong-tulis">Rp ............</span>';
    $nomor = ['administrasi' => '1', 'jasa_dokter' => '2', 'tindakan' => '', 'apotek' => '3', 'laboratorium' => '4', 'lain' => '5'];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Resume Medis Rawat Jalan - {{ $reg->no_rawat }}</title>
    <style>
        @page { margin: 1cm 1.2cm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; line-height: 1.35; color: #000; }
        .judul { text-align: center; font-weight: bold; font-size: 10.5pt; margin: 0 0 6px; }
        .label-tunda { border: 1px solid #92400e; color: #92400e; text-align: center; font-weight: bold; padding: 3px; margin-bottom: 6px; }
        table.blok { width: 100%; border-collapse: collapse; }
        table.blok td.sel { border: 0.8px solid #000; padding: 5px 7px; vertical-align: top; }
        table.identitas { width: 100%; border-collapse: collapse; }
        table.identitas td { padding: 2px; vertical-align: top; }
        .id-lbl { width: 2.6cm; }
        .id-sep { width: 0.3cm; }
        .jk span { padding: 0 3px; }
        .jk span.pilih { border: 0.8px solid #000; border-radius: 6px; font-weight: bold; }
        .judul-isian { font-weight: bold; margin-bottom: 3px; }
        .teks { white-space: pre-line; }
        .kode { width: 32%; }
        .tinggi-anamnesa { height: 3.2cm; }
        .tinggi-dx { height: 1.4cm; }
        .tinggi-terapi { height: 2.6cm; }
        table.ttv { border-collapse: collapse; margin-top: 6px; }
        table.ttv td { border: 0.8px solid #000; padding: 2px 8px; }
        .penunjang { margin-top: 4px; }
        table.biaya { border-collapse: collapse; width: 100%; }
        table.biaya td { padding: 1px 2px; }
        table.biaya .rp { text-align: right; width: 2.6cm; }
        table.biaya .total td { border-top: 0.8px solid #000; font-weight: bold; }
        .kosong-tulis { color: #555; }
        .ttd { text-align: center; }
        .ttd-ruang { height: 1.7cm; }
        .catatan { margin-top: 6px; font-size: 6.5pt; color: #555; }
    </style>
</head>
<body>
    @include('pdf.kop')

    <div class="judul">RESUME MEDIS RAWAT JALAN</div>
    @unless($diverifikasi)
        <div class="label-tunda">BELUM DIVERIFIKASI DOKTER</div>
    @endunless

    <table class="blok">
        <tr><td class="sel">
            <table class="identitas">
                <tr>
                    <td class="id-lbl">NAMA PASIEN</td><td class="id-sep">:</td>
                    <td><strong>{{ $pasien->nm_pasien ?? '-' }}</strong> &nbsp;
                        <span class="jk"><span class="{{ ($pasien->jk ?? '') === 'L' ? 'pilih' : '' }}">L</span>/<span class="{{ ($pasien->jk ?? '') === 'P' ? 'pilih' : '' }}">P</span></span>
                    </td>
                    <td class="id-lbl">NO. RM</td><td class="id-sep">:</td><td>{{ $reg->no_rkm_medis }}</td>
                </tr>
                <tr>
                    <td class="id-lbl">UMUR</td><td class="id-sep">:</td><td>{{ $umur }}</td>
                    <td class="id-lbl">NO. TELPON</td><td class="id-sep">:</td><td>{{ trim((string) ($pasien->no_tlp ?? '')) !== '' ? $pasien->no_tlp : '-' }}</td>
                </tr>
                <tr>
                    <td class="id-lbl">POLIKLINIK/UNIT</td><td class="id-sep">:</td><td>{{ $reg->poliklinik->nm_poli ?? '-' }}</td>
                    <td class="id-lbl">TGL PELAYANAN</td><td class="id-sep">:</td><td>{{ $tgl }}</td>
                </tr>
            </table>
        </td></tr>
    </table>

    <table class="blok" style="margin-top: 6px;">
        <tr>
            <td class="sel tinggi-anamnesa" colspan="2">
                <div class="judul-isian">Anamnesa :</div>
                <div class="teks">{{ $anamnesa !== '' ? $anamnesa : '-' }}</div>
                @if($penunjang !== '')
                    <div class="penunjang"><strong>Penunjang:</strong> <span class="teks">{{ $penunjang }}</span></div>
                @endif
                <table class="ttv">
                    <tr>
                        <td>Tensi : {{ $ttv['tensi'] }}</td>
                        <td>Nadi : {{ $ttv['nadi'] }}</td>
                        <td>SpO2 : {{ $ttv['spo2'] }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="sel tinggi-dx">
                <div class="judul-isian">Diagnosa Primer :</div>
                <div>{{ trim((string) $r->diagnosa_utama) !== '' ? $r->diagnosa_utama : '-' }}</div>
            </td>
            <td class="sel kode">
                <div class="judul-isian">Kode ICD 10 :</div>
                <div>{{ $kodePrimer }}</div>
            </td>
        </tr>
        <tr>
            <td class="sel tinggi-dx">
                <div class="judul-isian">Diagnosa Sekunder :</div>
                @foreach($dxSekunder as $i => $dx)
                    <div>{{ $i + 1 }}. {{ $dx }}</div>
                @endforeach
            </td>
            <td class="sel kode">
                <div class="judul-isian">Kode ICD 10 :</div>
                <div>{{ $kodeSekunder }}</div>
            </td>
        </tr>
        <tr>
            <td class="sel tinggi-terapi">
                <div class="judul-isian">Terapi / Tindakan :</div>
                <div class="teks">{{ $terapi !== '' ? $terapi : '-' }}</div>
            </td>
            <td class="sel kode">
                <div class="judul-isian">Kode ICD 9 :</div>
                <div>{{ $kodeProsedur }}</div>
            </td>
        </tr>
    </table>
    <table class="blok" style="margin-top: -0.8px;">
        <tr>
            <td class="sel" style="width: 50%;">
                <div class="judul-isian">Rincian Biaya :</div>
                <table class="biaya">
                    @foreach(\App\Services\Ralan\RincianBiayaRalan::BARIS as $kunci => $label)
                        <tr>
                            <td style="width: 0.4cm;">{{ $nomor[$kunci] }}</td><td>{{ $label }}</td>
                            <td class="rp">{!! $biaya ? e($rupiah($biaya['baris'][$kunci])) : $kosongTulis !!}</td>
                        </tr>
                    @endforeach
                    <tr class="total"><td></td><td>Total</td><td class="rp">{!! $biaya ? e($rupiah($biaya['total'])) : $kosongTulis !!}</td></tr>
                </table>
                <div style="margin-top: 6px;">Kasir</div>
                <div style="margin-top: 22px;">( ...................................... )</div>
            </td>
            <td class="sel ttd" style="width: 50%;">
                <div>Banjarbaru, {{ $tgl }}</div>
                <div style="margin-top: 2px;">Dokter</div>
                <div class="ttd-ruang"></div>
                {{-- Nama tanpa SIP: no_ijn_praktek di Khanza banyak yang kosong. Belum diverifikasi = tanpa nama. --}}
                <div><strong><u>{{ $dokterTtd->nm_dokter ?? '' }}</u></strong>&nbsp;</div>
            </td>
        </tr>
    </table>

    <div class="catatan">Dicetak dari RME RSU Nirwana, {{ now()->format('d/m/Y H.i') }}.{{ $biaya ? ' Rincian biaya dari nota kasir.' : ' Nota kasir belum dibuat; rincian biaya diisi kasir.' }}</div>
</body>
</html>
