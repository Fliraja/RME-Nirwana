@php
    $reg = $kepala['reg'];
    $lama = $kepala['lama_menit'];
    $teksLama = ($lama >= 60 ? intdiv($lama, 60) . ' j ' : '') . ($lama % 60) . ' m';
@endphp
<section class="igd-kepala" aria-label="Identitas pasien">
    <div class="igd-kepala-identitas">
        <h2 class="igd-kepala-nama">{{ $reg->nm_pasien }}
            <span class="igd-kepala-rm">RM {{ $reg->no_rkm_medis }} · {{ $reg->no_rawat }}</span>
        </h2>
        <ul class="igd-chips">
            <li class="igd-chip">{{ $reg->jk === 'P' ? 'Perempuan' : 'Laki-laki' }}, {{ $reg->umurdaftar }} {{ $reg->sttsumur }}</li>
            <li class="igd-chip">{{ $reg->png_jawab ?? '-' }}</li>
            <li class="igd-chip">Reg. {{ $reg->nm_dokter ?? '-' }}</li>
            @if($kepala['alergi'])
                <li class="igd-chip igd-chip-alergi"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> Alergi: {{ $kepala['alergi'] }}</li>
            @endif
        </ul>
    </div>
    <div class="igd-kepala-waktu">
        @if($kepala['rawat_inap_sejak'])
            <div class="igd-kepala-label">Rawat inap sejak {{ $kepala['rawat_inap_sejak']->format('d/m H.i') }}</div>
            <div class="igd-kepala-lama">di IGD {{ $teksLama }}</div>
        @else
            <div class="igd-kepala-label">Di IGD sejak {{ $kepala['datang']->format('H.i') }}</div>
            <div class="igd-kepala-lama">{{ $teksLama }}</div>
        @endif
    </div>
</section>
