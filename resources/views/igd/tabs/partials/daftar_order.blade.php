{{-- Daftar permintaan lab/radiologi. $daftar: hasil IgdOrderService::daftarLab/daftarRadiologi; $jenis: lab|radiologi --}}
@forelse($daftar as $o)
    <div class="igd-order">
        <div class="igd-order-kepala">
            <span class="igd-order-meta">
                {{ \Carbon\Carbon::parse($o->tgl_permintaan . ' ' . $o->jam_permintaan)->format('d/m H.i') }}
                · {{ $o->nm_dokter ?? $o->dokter_perujuk }}
                @if(trim($o->diagnosa_klinis) !== '' && $o->diagnosa_klinis !== '-')
                    · Dx klinis: {{ $o->diagnosa_klinis }}
                @endif
            </span>
            @php
                $kelas = str_starts_with($o->status, 'hasil keluar') ? (str_contains($o->status, 'rujukan') ? 'igd-st-flag' : 'igd-st-hasil')
                    : ($o->status === 'sampel diambil' ? 'igd-st-proses' : 'igd-st-tunggu');
            @endphp
            <span class="igd-st {{ $kelas }}">{{ $o->status }}</span>
        </div>
        <div class="igd-order-isi">
            <span>{{ $o->pemeriksaan ?: '-' }}</span>
            @if($o->boleh_hapus && !$pesanTerkunci)
                <button type="button" class="btn btn-sm igd-btn-hapus" data-hapus-order="{{ $jenis }}" data-noorder="{{ $o->noorder }}"
                        aria-label="Batalkan permintaan {{ $jenis }} {{ $o->noorder }}">
                    <i class="fas fa-trash-can" aria-hidden="true"></i> Batalkan
                </button>
            @endif
        </div>
    </div>
@empty
    <p class="igd-kosong mb-0">Belum ada permintaan {{ $jenis === 'lab' ? 'laboratorium' : 'radiologi' }}.</p>
@endforelse
