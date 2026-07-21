<!DOCTYPE html>
<html>
<head>
    <title>Ringkasan Riwayat Medis Pasien</title>
    <style>
        @page { margin: 1cm; }
        body { 
            font-family: 'Helvetica', Arial, sans-serif; 
            font-size: 8pt; 
            color: #333; 
            margin: 0;
            padding: 0;
        }
        
        /* Kop Surat Styling */
        .letterhead {
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
            position: relative;
        }
        .letterhead-content {
            display: table;
            width: 100%;
        }
        .letterhead-logo {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
            padding-right: 15px;
        }
        .letterhead-logo img {
            width: 150px;
            height: auto;
        }
        .letterhead-text {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        .letterhead-text h1 {
            margin: 0;
            font-size: 14pt;
            font-weight: bold;
            color: #000;
        }
        .letterhead-text p {
            margin: 2px 0;
            font-size: 8pt;
        }
        .letterhead-line {
            border-bottom: 1px solid #000;
            margin-top: 5px;
        }
        
        .document-date {
            text-align: right;
            margin-bottom: 10px;
        }
        
        .document-info table {
            border: none;
            margin-bottom: 15px;
            width: auto;
        }
        .document-info td {
            border: none;
            padding: 2px 0;
            vertical-align: top;
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
        }
        .header h2 { 
            margin: 5px 0; 
            text-transform: uppercase;
            font-size: 11pt;
        }
        
        /* Table Styling */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; 
        }
        th, td { 
            border: 1px solid #444; 
            padding: 6px; 
            vertical-align: top; 
            word-wrap: break-word; 
        }
        th { 
            background-color: #f0f0f0; 
            font-weight: bold; 
            text-align: center; 
            text-transform: uppercase; 
        }
        .text-center { text-align: center; }
        .footer { 
            margin-top: 15px; 
            font-style: italic; 
            font-size: 7pt; 
        }
        ul { margin: 0; padding-left: 12px; }
        .section-title { font-weight: bold; display: block; text-decoration: underline; margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="letterhead">
        <div class="letterhead-content">
            <div class="letterhead-logo">
                <img src="{{ public_path('img/RSU22Nirwana1.png') }}" alt="Logo RSU Nirwana">
            </div>
            <div class="letterhead-text">
                <h1>RUMAH SAKIT UMUM NIRWANA</h1>
                <p>JL. PANGLIMA BATUR TIMUR NO.42 BANJARBARU KALIMANTAN SELATAN</p>
                <p>TELP 0511-674 9272 / 0851 0124 0608</p>
                <p>EMAIL : rsu.nirwana@gmail.com</p>
            </div>
        </div>
        <div class="letterhead-line"></div>
    </div>
    
    <div class="document-date">
        Banjarbaru, {{ date('d F Y') }}
    </div>
    
    <div class="document-info">
        <table>
            <tr>
                <td width="100"><strong>Nama Pasien</strong></td>
                <td width="20">:</td>
                <td><strong>{{ $detailPasien->pasien->nm_pasien }}</strong> (RM: {{ $detailPasien->no_rkm_medis }})</td>
            </tr>
            <tr>
                <td><strong>Perihal</strong></td>
                <td>:</td>
                <td><strong>Ringkasan Riwayat Medis (5 Perawatan Terakhir)</strong></td>
            </tr>
        </table>
    </div>
    
    <div class="header">
        <h2>REKAPITULASI RIWAYAT PERAWATAN PASIEN</h2>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="10%">Tanggal / No.Rawat</th>
                <th width="15%">Klinik / Dokter</th>
                <th width="25%">Keluhan & Pemeriksaan</th>
                <th width="15%">Diagnosa & Terapi</th>
                <th width="15%">Obat / Resep</th>
                <th width="10%">Laboratorium</th>
                <th width="10%">Radiologi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riwayat as $item)
                @php 
                    $soap = ($item->status_lanjut == 'Ralan') ? $item->pemeriksaanRalan : $item->pemeriksaanRanap; 
                @endphp
                <tr>
                    <td class="text-center">
                        {{ $item->tgl_registrasi }}<br>
                        <small><strong>{{ $item->no_rawat }}</strong></small>
                    </td>
                    <td>
                        <strong>{{ $item->status_lanjut == 'Ralan' ? ($item->poliklinik->nm_poli ?? '-') : 'Rawat Inap' }}</strong><br>
                        <small>{{ $item->dokter->nm_dokter ?? '-' }}</small>
                    </td>
                    <td>
                        <span class="section-title">Keluhan:</span> {{ $soap->keluhan ?? '-' }}
                        <hr style="border: 0.1px solid #eee; margin: 5px 0;">
                        <span class="section-title">Pemeriksaan:</span> {{ $soap->pemeriksaan ?? '-' }}
                    </td>
                    <td>
                        <span class="section-title">Penilaian:</span> {{ $soap->penilaian ?? '-' }}
                        <hr style="border: 0.1px solid #eee; margin: 5px 0;">
                        <span class="section-title">RTL:</span> {{ $soap->rtl ?? '-' }}
                    </td>
                    <td>
                        <ul>
                            @foreach($item->detailObat as $obat)
                                <li>{{ $obat->barang->nama_brng }} ({{ $obat->jml }})</li>
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        @foreach($item->detailLab as $lab)
                            <div style="margin-bottom: 4px; border-bottom: 1px solid #f0f0f0;">
                                <strong>{{ $lab->template->Pemeriksaan ?? '-' }}</strong>: {{ $lab->nilai }}
                            </div>
                        @endforeach
                    </td>
                    <td class="text-center">
                        @if($item->gambarRadiologi->count() > 0)
                            <span style="color: green;">✔ Tersedia {{ $item->gambarRadiologi->count() }} Gambar</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">Belum ada riwayat medis.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i:s') }}, Oleh: {{ Auth::user()->dokter_data->nm_dokter ?? 'Sistem' }}
    </div>
</body>
</html>