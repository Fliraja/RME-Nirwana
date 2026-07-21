<!DOCTYPE html>
<html>
<head>
    <title>Laporan Permintaan Laboratorium</title>
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
            vertical-align: middle; 
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
        .signature-wrapper {
            margin-top: 30px;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            border: none;
            padding: 0;
            text-align: center;
        }
        .signature-space {
            height: 60px; /* Ruang untuk tanda tangan basah */
        }
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
                <td width="70"><strong>Nomor</strong></td>
                <td width="20">:</td>
                <td>{{ sprintf('%03d', rand(100, 999)) }}/Lap-Lab/RSUN/{{ date('m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Perihal</strong></td>
                <td>:</td>
                <td><strong>Laporan Permintaan Laboratorium</strong></td>
            </tr>
        </table>
    </div>
    
    <div class="header">
        <h2>REKAP PERMINTAAN PEMERIKSAAN LABORATORIUM</h2>
        <div class="periode">Periode: {{ date('d/m/Y', strtotime($tgl_mulai)) }} s/d {{ date('d/m/Y', strtotime($tgl_selesai)) }}</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="4%">#</th>
                <th width="12%">No. Order / Waktu</th>
                <th width="18%">Nama Pasien / RM</th>
                <th width="20%">Grup Pemeriksaan</th>
                <th width="46%">Detail Item Pemeriksaan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($laporan as $row)
                @php $rowCount = $row->pemeriksaan->count(); @endphp
                @foreach($row->pemeriksaan as $index => $item)
                <tr>
                    @if($index === 0)
                        <td rowspan="{{ $rowCount }}" class="text-center">{{ $no++ }}</td>
                        <td rowspan="{{ $rowCount }}" class="text-center">
                            <strong>{{ $row->noorder }}</strong><br>
                            {{ $row->tgl_permintaan }}<br>
                            <span style="color: #666;">{{ $row->jam_permintaan }}</span>
                        </td>
                        <td rowspan="{{ $rowCount }}">
                            <strong>{{ $row->regPeriksa->pasien->nm_pasien ?? '-' }}</strong><br>
                            RM: {{ $row->regPeriksa->no_rkm_medis }}
                        </td>
                    @endif
                    
                    <td style="background-color: #f9f9f9;">{{ $item->jenisPerawatan->nm_perawatan }}</td>
                    <td>
                        @php
                            $detailNames = $item->detailTemplate
                                ->where('kd_jenis_prw', $item->kd_jenis_prw)
                                ->map(function($d) {
                                    return $d->template->Pemeriksaan ?? '-';
                                })
                                ->implode(', ');
                        @endphp
                        {{ $detailNames ?: '- Umum -' }}
                    </td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="5" class="text-center">Data tidak ditemukan untuk periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-wrapper">
        <table class="signature-table">
            <tr>
                <td width="70%"></td> <td width="30%">
                    <p>Banjarbaru, {{ date('d F Y') }}</p>
                    <p>Hormat Kami,</p>
                    <div class="signature-space"></div>
                    <p><strong><u>{{ $nama_dokter }}</u></strong></p>
                    <p>Dokter Pemeriksa</p>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 30px; font-size: 7pt; font-style: italic; border-top: 1px solid #eee; padding-top: 5px;">
        Dicetak pada: {{ date('d/m/Y H:i:s') }} | 
        Oleh: {{ session('role') === 'admin' ? 'Administrator' : (Auth::user()->dokter_data->nm_dokter ?? 'Dokter') }}
    </div>
</body>
</html>