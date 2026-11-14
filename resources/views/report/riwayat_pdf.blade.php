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
        .kunjungan { margin-bottom: 8px; }
        .kunjungan th.kepala { text-align: left; text-transform: none; background: #eef5f1; color: #0d7a4a; }
        .label { display: block; font-weight: bold; margin-top: 4px; }
        .isi { display: block; white-space: pre-line; }
    </style>
</head>
<body>
    @include('pdf.kop')
    
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
                <td><strong>Ringkasan riwayat medis (5 kunjungan terakhir)</strong></td>
            </tr>
        </table>
    </div>
    
    <div class="header">
        <h2>Rekapitulasi riwayat perawatan pasien</h2>
    </div>
    @php
        use App\Services\Kunjungan\RiwayatKunjungan as RK;
        $jam = fn ($w) => $w ? \Carbon\Carbon::parse($w)->format('d/m H.i') : '';
    @endphp
    @forelse($riwayat as $k)
        <table class="kunjungan">
            <tr>
                <th colspan="2" class="kepala">
                    {{ RK::tanggal($k->tanggal) }} ·
                    @if($k->jenis === 'ranap')
                        Rawat inap{{ $k->ranap['lama'] ? ' ' . $k->ranap['lama'] . ' hari (' . RK::tanggal($k->ranap['masuk'], 'd/m') . ' s.d. ' . RK::tanggal($k->ranap['keluar'], 'd/m') . ')' : '' }}{{ $k->ranap['pulang'] ? ', pulang: ' . $k->ranap['pulang'] : '' }}
                        · DPJP {{ $k->ranap['dpjp'] ?: ($k->dokter ?? '-') }}
                    @else
                        {{ $k->unit }} · {{ $k->dokter ?? '-' }}
                    @endif
                    · {{ $k->no_rawat }}
                </th>
            </tr>
            <tr>
                <td width="55%">
                    @if($k->jenis === 'ranap')
                        <span class="label">Diagnosis</span> Masuk: {{ $k->ranap['dx_awal'] ?: '-' }}{{ $k->ranap['dx_akhir'] !== '' ? ' → akhir: ' . $k->ranap['dx_akhir'] : '' }}
                        @if($k->igd)
                            <span class="label">Asesmen IGD ({{ $k->igd['dokter'] }} · {{ $jam($k->igd['waktu']) }})</span>{{ $k->igd['keluhan'] ?: '-' }}{{ $k->igd['diagnosis'] !== '' ? '. Dx: ' . $k->igd['diagnosis'] : '' }}
                        @endif
                        @if($k->ranap['cppt'])
                            @php($c = $k->ranap['cppt'][0])
                            <span class="label">CPPT dokter terakhir ({{ $c['dokter'] }} · {{ $jam($c['waktu']) }})</span><span class="isi">{{ $c['A'] !== '' ? 'A: ' . $c['A'] : '' }}{{ $c['A'] !== '' && $c['P'] !== '' ? "
" : '' }}{{ $c['P'] !== '' ? 'P: ' . $c['P'] : '' }}</span>
                        @else
                            <span class="label">CPPT dokter</span> Belum ada CPPT dokter.
                        @endif
                    @elseif($k->jenis === 'igd')
                        @if($k->igd)
                            <span class="label">Keluhan utama</span><span class="isi">{{ $k->igd['keluhan'] ?: '-' }}</span>
                            <span class="label">Pemeriksaan</span><span class="isi">{{ $k->igd['pemeriksaan'] ?: '-' }}</span>
                            <span class="label">Diagnosis</span><span class="isi">{{ $k->igd['diagnosis'] ?: '-' }}</span>
                            <span class="label">Tatalaksana</span><span class="isi">{{ $k->igd['tatalaksana'] ?: '-' }}</span>
                        @else
                            Asesmen IGD belum ditulis.
                        @endif
                    @elseif($k->soap)
                        @foreach(['S', 'O', 'A', 'P'] as $b)
                            <span class="label">{{ $b }}</span><span class="isi">{{ $k->soap[$b] ?: '-' }}</span>
                        @endforeach
                    @else
                        SOAP belum ditulis.
                    @endif
                </td>
                <td>
                    <span class="label">Laboratorium{{ $k->lab['jumlah'] ? ' (' . $k->lab['jumlah'] . ' pemeriksaan)' : '' }}</span>
                    @if(!$k->lab['jumlah'])
                        Tidak ada hasil lab.
                    @elseif($k->lab['luar'])
                        <span class="isi">Di luar rujukan:{{ "
" . implode("
", $k->lab['luar']) }}</span>
                    @else
                        Semua dalam rujukan.
                    @endif
                    <span class="label">Radiologi</span>
                    @forelse($k->radiologi as $r)
                        <span class="isi">{{ $r['pemeriksaan'] }} ({{ RK::tanggal($r['tanggal'], 'd/m') }}): {{ $r['hasil'] }}</span>
                    @empty
                        Tidak ada hasil radiologi.
                    @endforelse
                    <span class="label">Obat{{ $k->obat ? ' (' . count($k->obat) . ' jenis)' : '' }}</span>
                    {{ $k->obat ? collect($k->obat)->map(fn ($o) => "{$o['nama']} ({$o['jumlah']} {$o['satuan']})")->implode(', ') : 'Tidak ada obat dari apotek.' }}
                </td>
            </tr>
        </table>
    @empty
        <p class="text-center">Belum ada kunjungan untuk pasien ini.</p>
    @endforelse
    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i:s') }}, Oleh: {{ Auth::user()->dokter_data->nm_dokter ?? 'Sistem' }}
    </div>
</body>
</html>