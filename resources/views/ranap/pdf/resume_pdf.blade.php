<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Resume Medis Pasien Rawat Inap - {{ $reg->no_rawat ?? '' }}</title>
    <style>
        @page {
            margin: 1cm 1.2cm 1cm 1.2cm;
        }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.35;
            color: #222;
            margin: 0;
            padding: 0;
        }

        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .kop-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .kop-logo {
            width: 130px;
        }
        .kop-logo img {
            width: 130px;
            height: auto;
        }
        .kop-text {
            text-align: right;
        }
        .kop-text h1 {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            color: #0d47a1;
            letter-spacing: 0.5px;
        }
        .kop-text p {
            margin: 1px 0;
            font-size: 7.5pt;
            color: #333;
        }

        .doc-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 8px 0 12px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #777;
        }

        /* Tabel Identitas */
        .info-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .info-table td {
            border: none;
            padding: 2.5px 4px;
            font-size: 8pt;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #444;
            width: 18%;
        }
        .info-sep {
            width: 2%;
            text-align: center;
        }
        .info-val {
            width: 30%;
        }

        /* Bagian Konten Medis */
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .content-table th, .content-table td {
            border: 1px solid #666;
            padding: 5px 6px;
            font-size: 8pt;
            vertical-align: top;
        }
        .section-header {
            background-color: #f0f4f8;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            color: #0d47a1;
            border: 1px solid #666;
            padding: 4px 6px;
        }

        .badge-code {
            font-weight: bold;
            background-color: #eee;
            padding: 1px 4px;
            border: 1px solid #ccc;
            font-size: 7.5pt;
        }

        /* Tanda Tangan */
        .ttd-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }
        .ttd-table td {
            border: none;
            padding: 2px;
            vertical-align: top;
        }
        .text-center {
            text-align: center;
        }
        .text-end {
            text-align: right;
        }
        .fw-bold {
            font-weight: bold;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 6.5pt;
            color: #777;
            border-top: 1px dashed #ccc;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    {{-- Kop Surat RSU Nirwana --}}
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                <img src="{{ public_path('img/RSU22Nirwana1.png') }}" alt="Logo RSU Nirwana">
            </td>
            <td class="kop-text">
                <h1>RUMAH SAKIT UMUM NIRWANA</h1>
                <p>JL. PANGLIMA BATUR TIMUR NO.42 BANJARBARU KALIMANTAN SELATAN</p>
                <p>Telp: 0511-674 9272 / 0851 0124 0608 | Email: rsu.nirwana@gmail.com</p>
            </td>
        </tr>
    </table>

    <div class="doc-title">RINGKASAN PULANG PASIEN RAWAT INAP (DISCHARGE SUMMARY)</div>

    {{-- Identitas Pasien & Data Rawat --}}
    @php
        $tglMasuk = $kamarMasuk ? \Carbon\Carbon::parse($kamarMasuk->tgl_masuk)->format('d/m/Y') . ' ' . $kamarMasuk->jam_masuk : '-';
        $sudahKeluar = $kamarAkhir && $kamarAkhir->stts_pulang !== '-' && $kamarAkhir->tgl_keluar && $kamarAkhir->tgl_keluar != '0000-00-00';
        $tglKeluar = $sudahKeluar
            ? \Carbon\Carbon::parse($kamarAkhir->tgl_keluar)->format('d/m/Y') . ' ' . $kamarAkhir->jam_keluar
            : '-';
        $lamaRawat = $lamaRawat > 0 ? $lamaRawat . ' Hari' : '-';
        $dokterTtd = $resume->dokter ?? $dpjp;
        $namaDokterDpjp = $dokterTtd->nm_dokter ?? '-';
    @endphp

    <table class="info-table">
        <tr>
            <td class="info-label">No. Rekam Medis</td>
            <td class="info-sep">:</td>
            <td class="info-val"><strong>{{ $reg->no_rkm_medis ?? '-' }}</strong></td>
            <td class="info-label">Kamar / Bangsal</td>
            <td class="info-sep">:</td>
            <td class="info-val">{{ $kamarAkhir->kd_kamar ?? '-' }} ({{ $kamarAkhir->kamar->bangsal->nm_bangsal ?? '-' }})</td>
        </tr>
        <tr>
            <td class="info-label">Nama Pasien</td>
            <td class="info-sep">:</td>
            <td class="info-val"><strong>{{ $reg->pasien->nm_pasien ?? '-' }}</strong></td>
            <td class="info-label">Kelas Perawatan</td>
            <td class="info-sep">:</td>
            <td class="info-val">{{ $kamarAkhir->kamar->kelas ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">No. Rawat</td>
            <td class="info-sep">:</td>
            <td class="info-val">{{ $reg->no_rawat ?? '-' }}</td>
            <td class="info-label">Tanggal Masuk</td>
            <td class="info-sep">:</td>
            <td class="info-val">{{ $tglMasuk }}</td>
        </tr>
        <tr>
            <td class="info-label">Jenis Kelamin / Umur</td>
            <td class="info-sep">:</td>
            <td class="info-val">{{ ($reg->pasien->jk ?? '') === 'P' ? 'Perempuan' : 'Laki-Laki' }} / {{ $reg->umurdaftar ?? '-' }} {{ $reg->sttsumur ?? '' }}</td>
            <td class="info-label">Tanggal Keluar</td>
            <td class="info-sep">:</td>
            <td class="info-val">{{ $tglKeluar }} ({{ $lamaRawat }})</td>
        </tr>
        <tr>
            <td class="info-label">Penjamin (Cara Bayar)</td>
            <td class="info-sep">:</td>
            <td class="info-val">{{ $reg->penjab->png_jawab ?? '-' }}</td>
            <td class="info-label">Dokter DPJP</td>
            <td class="info-sep">:</td>
            <td class="info-val"><strong>{{ $namaDokterDpjp }}</strong></td>
        </tr>
        @if(!empty($alergiPasien))
        <tr>
            <td class="info-label" style="color: #c62828;">Riwayat Alergi</td>
            <td class="info-sep">:</td>
            <td colspan="4" style="color: #c62828; font-weight: bold;">{{ $alergiPasien }}</td>
        </tr>
        @endif
    </table>

    {{-- Konten Ringkasan Medis --}}
    <table class="content-table">
        <tr>
            <td colspan="2" class="section-header">1. Riwayat Perjalanan Penyakit & Keluhan Utama</td>
        </tr>
        <tr>
            <td colspan="2">
                {!! nl2br(e($resume->jalannya_penyakit ?: ($resume->keluhan_utama ?: '-'))) !!}
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">2. Pemeriksaan Fisik</td>
        </tr>
        <tr>
            <td colspan="2">
                {!! nl2br(e($resume->pemeriksaan_fisik ?: '-')) !!}
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">3. Pemeriksaan Penunjang Penting (Laboratorium & Radiologi)</td>
        </tr>
        <tr>
            <td colspan="2">
                {!! nl2br(e($resume->pemeriksaan_penunjang ?: '-')) !!}
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">4. Diagnosa Akhir</td>
        </tr>
        <tr>
            <td colspan="2">
                @php
                    $diagPdf = array_values(array_filter([
                        $resume->diagnosa_utama,
                        $resume->diagnosa_sekunder,
                        $resume->diagnosa_sekunder2,
                        $resume->diagnosa_sekunder3,
                        $resume->diagnosa_sekunder4,
                    ], 'strlen'));

                    $semuaKodeDiag = array_values(array_filter(array_unique(array_merge(
                        [$resume->kd_diagnosa_utama, $resume->kd_diagnosa_sekunder, $resume->kd_diagnosa_sekunder2, $resume->kd_diagnosa_sekunder3, $resume->kd_diagnosa_sekunder4],
                        $kodeDiagnosa ?? []
                    ))));
                @endphp
                @forelse($diagPdf as $i => $d)
                    <div style="margin-bottom: 2px;"><strong>{{ $i === 0 ? 'Utama' : 'Sekunder ' . $i }}:</strong> {{ $d }}</div>
                @empty
                    <div>-</div>
                @endforelse

                @if(!empty($semuaKodeDiag))
                    <div style="font-size: 7.5pt; color: #444; margin-top: 4px; padding-top: 3px; border-top: 1px dashed #ccc;">
                        <strong>Kode ICD-10 (casemix):</strong> {{ implode(', ', $semuaKodeDiag) }}
                    </div>
                @endif
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">5. Prosedur & Tindakan</td>
        </tr>
        <tr>
            <td colspan="2">
                @php
                    $prosPdf = array_values(array_filter([
                        $resume->prosedur_utama,
                        $resume->prosedur_sekunder,
                        $resume->prosedur_sekunder2,
                        $resume->prosedur_sekunder3,
                    ], 'strlen'));

                    $semuaKodePros = array_values(array_filter(array_unique(array_merge(
                        [$resume->kd_prosedur_utama, $resume->kd_prosedur_sekunder, $resume->kd_prosedur_sekunder2, $resume->kd_prosedur_sekunder3],
                        $kodeProsedur ?? []
                    ))));
                @endphp
                @forelse($prosPdf as $i => $p)
                    <div style="margin-bottom: 2px;"><strong>{{ $i === 0 ? 'Utama' : 'Sekunder ' . $i }}:</strong> {{ $p }}</div>
                @empty
                    <div>-</div>
                @endforelse

                @if(!empty($semuaKodePros))
                    <div style="font-size: 7.5pt; color: #444; margin-top: 4px; padding-top: 3px; border-top: 1px dashed #ccc;">
                        <strong>Kode ICD-9-CM (casemix):</strong> {{ implode(', ', $semuaKodePros) }}
                    </div>
                @endif
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">6. Tindakan, Terapi & Operasi Selama di Rumah Sakit</td>
        </tr>
        <tr>
            <td colspan="2">
                {!! nl2br(e($resume->tindakan_dan_operasi ?: ($resume->obat_di_rs ?: '-'))) !!}
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">7. Kondisi Saat Pulang & Keadaan Keluar</td>
        </tr>
        <tr>
            <td style="font-weight: bold; background-color: #fafafa;">Kondisi / Cara Keluar</td>
            <td>
                <strong>{{ $resume->keadaan ?? 'Membaik' }}</strong>
                @if(!empty($resume->cara_keluar) && $resume->cara_keluar != '-')
                    &nbsp;|&nbsp; Cara Keluar: {{ $resume->cara_keluar }}
                @endif
                @if(!empty($resume->ket_keluar))
                    ({{ $resume->ket_keluar }})
                @endif
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">8. Terapi Obat Pulang</td>
        </tr>
        <tr>
            <td colspan="2">
                {!! nl2br(e($resume->obat_pulang ?: '-')) !!}
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">9. Instruksi Tindak Lanjut, Edukasi & Jadwal Kontrol</td>
        </tr>
        <tr>
            <td colspan="2">
                @if(!empty($resume->kontrol) && $resume->kontrol != '0000-00-00 00:00:00')
                    <div style="margin-bottom: 4px;">
                        <strong>Jadwal Kontrol Ulang:</strong> 
                        {{ \Carbon\Carbon::parse($resume->kontrol)->translatedFormat('l, d F Y') }}
                        (Jam: {{ \Carbon\Carbon::parse($resume->kontrol)->format('H:i') }})
                    </div>
                @endif
                <div>{!! nl2br(e($resume->edukasi ?: '-')) !!}</div>
            </td>
        </tr>
    </table>

    {{-- Tanda Tangan Dokter DPJP --}}
    <table class="ttd-table">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%; text-align: center;">
                <div>Banjarbaru, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div style="margin-top: 3px; font-weight: bold;">Dokter Penanggung Jawab Pelayanan (DPJP)</div>
                <div style="margin-top: 45px; font-weight: bold; text-decoration: underline;">
                    {{ $namaDokterDpjp }}
                </div>
                <div style="font-size: 7.5pt; color: #555;">
                    {{ !empty($dokterTtd->no_ijn_praktek) ? 'SIP: ' . $dokterTtd->no_ijn_praktek : 'Kode Dokter: ' . ($dokterTtd->kd_dokter ?? '-') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        * Dokumen Rekam Medis Elektronik ini dicetak secara otomatis melalui Sistem RME RSU Nirwana.
    </div>
</body>
</html>
