{{-- Kop surat semua PDF RME (dompdf), teks sama dengan formulir cetak RS. Style ikut di sini supaya cukup satu @include. --}}
<style>
    .kop-rs { width: 100%; border-collapse: collapse; }
    .kop-rs td { vertical-align: middle; padding: 0; }
    .kop-rs .kop-logo { width: 2.4cm; }
    .kop-rs .kop-logo img { height: 1.2cm; }
    .kop-rs .kop-teks { text-align: center; padding-right: 2.4cm; color: #000; }
    .kop-rs .kop-nama { font-size: 12pt; font-weight: bold; letter-spacing: 0.3px; line-height: 1.2; }
    .kop-rs .kop-baris { font-size: 7.5pt; line-height: 1.2; }
    .kop-garis { border-top: 2px solid #000; border-bottom: 0.6px solid #000; height: 1.5px; margin: 3px 0 6px; }
</style>
<table class="kop-rs">
    <tr>
        <td class="kop-logo"><img src="{{ public_path('img/RSU22Nirwana1.png') }}" alt="Logo RSU Nirwana"></td>
        <td class="kop-teks">
            <div class="kop-nama">RUMAH SAKIT UMUM NIRWANA</div>
            <div class="kop-baris">JL. Panglima Batur Timur No. 42 Banjarbaru Kalimantan Selatan</div>
            <div class="kop-baris">Telp. 0511-674 9272 / 0821 5084 1882</div>
            <div class="kop-baris">Email: official@rsunirwana.id | Website: rsunirwana.id</div>
        </td>
    </tr>
</table>
<div class="kop-garis"></div>
