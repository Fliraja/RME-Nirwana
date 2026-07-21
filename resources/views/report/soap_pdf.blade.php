<!DOCTYPE html>
<html>
<head>
    <title>Laporan SOAP Pasien</title>
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
            letter-spacing: 0.5px;
        }
        
        .letterhead-text p {
            margin: 2px 0;
            font-size: 8pt;
            line-height: 1.4;
        }
        
        .letterhead-line {
            border-bottom: 1px solid #000;
            margin-top: 5px;
        }
        
        /* Document Info */
       .document-info {
            margin-bottom: 20px;
            font-size: 9pt;
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
        
        .document-info td:first-child {
            padding-right: 10px;
        }
        
        .document-info td:nth-child(2) {
            padding-right: 10px;
        }
        
        .document-date {
            text-align: right;
            margin-bottom: 10px;
        }
        
        /* Header Section */
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            padding-bottom: 10px; 
        }
        
        .header h2 { 
            margin: 5px 0; 
            text-transform: uppercase;
            font-size: 11pt;
        }
        
        .header .periode {
            font-size: 9pt;
            margin-top: 5px;
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
        .bg-grey { background-color: #f9f9f9; }
        
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
                <td>{{ sprintf('%03d', rand(100, 999)) }}/Lap/RSUN/{{ date('m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Lamp</strong></td>
                <td>:</td>
                <td>-</td>
            </tr>
            <tr>
                <td><strong>Perihal</strong></td>
                <td>:</td>
                <td><strong>Laporan SOAP Rawat Jalan</strong></td>
            </tr>
        </table>
    </div>
    
    <div class="header">
        <h2>Laporan Pemeriksaan Rawat Jalan (SOAP)</h2>
        <div class="periode">Periode: {{ date('d/m/Y', strtotime($tgl_mulai)) }} s/d {{ date('d/m/Y', strtotime($tgl_selesai)) }}</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="4%">#</th>
                <th width="10%">Waktu</th>
                <th width="15%">Pasien</th>
                <th width="4%">JK</th>
                <th width="17%">Subject (S)</th>
                <th width="17%">Object (O)</th>
                <th width="17%">Assessment (A)</th>
                <th width="16%">Plan (P)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($laporan as $row)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center">
                    {{ $row->tgl_perawatan }}<br>
                    <span style="color: #666;">{{ $row->jam_rawat }}</span>
                </td>
                <td>
                    <strong>{{ $row->regPeriksa->pasien->nm_pasien ?? '-' }}</strong><br>
                    RM: {{ $row->regPeriksa->no_rkm_medis }}
                </td>
                <td class="text-center">{{ $row->regPeriksa->pasien->jk ?? '-' }}</td>
                <td>{{ $row->keluhan }}</td>
                <td>{{ $row->pemeriksaan }}</td>
                <td>{{ $row->penilaian }}</td>
                <td>{{ $row->rtl }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $userName = Auth::user()->dokter_data->nm_dokter ?? 'User';
    @endphp
    
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