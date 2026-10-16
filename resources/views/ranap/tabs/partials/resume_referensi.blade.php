{{--
    Fragmen referensi resume ranap, dimuat terpisah dari form. Satu blok per kunci ResumeRanapReferensi;
    JS memindahkan isi tiap blok ke kotak .resume-ref[data-ref] yang sama kuncinya.
--}}
@php
    $chip = ['diagnosis', 'tindakan'];
    $infoSaja = ['kondisi'];
    $terbaruDulu = ['perjalanan'];
@endphp
@foreach($ref as $kunci => $item)
    @php
        $utama = array_values(array_filter($item, fn ($i) => in_array($i['peran'], ['dokter', 'sistem'], true)));
        $lain = array_values(array_filter($item, fn ($i) => !in_array($i['peran'], ['dokter', 'sistem'], true)));
        if ($utama === []) {
            [$utama, $lain] = [$lain, []];
        }
        // Perjalanan penyakit: tiga hari terakhir terbuka, hari sebelumnya dilipat.
        $tampil = in_array($kunci, $terbaruDulu, true) ? array_slice($utama, -3) : array_slice($utama, 0, 3);
        $lipat = in_array($kunci, $terbaruDulu, true) ? array_slice($utama, 0, max(count($utama) - 3, 0)) : array_slice($utama, 3);
    @endphp
    <div data-ref-isi="{{ $kunci }}">
        @if($item === [])
            <div class="resume-ref-kosong">Belum ada catatan untuk bagian ini.</div>
        @elseif(in_array($kunci, $chip, true))
            <div class="resume-ref-chips">
                @foreach($utama as $i)
                    <button type="button" class="resume-chip" data-teks="{{ $i['teks'] }}" title="{{ $i['sumber'] }}">+ {{ $i['teks'] }}</button>
                @endforeach
            </div>
            @if($lain !== [])
                <div class="resume-ref-chips mt-1">
                    <span class="resume-ref-sumber non-dokter">Bidan/Perawat</span>
                    @foreach($lain as $i)
                        <button type="button" class="resume-chip" data-teks="{{ $i['teks'] }}" title="{{ $i['sumber'] }}">+ {{ $i['teks'] }}</button>
                    @endforeach
                </div>
            @endif
        @else
            @foreach([$tampil, $lipat, $lain] as $kelompok => $daftar)
                @continue($daftar === [])
                @if($kelompok > 0)
                    <details class="resume-ref-lain">
                        <summary>{{ $kelompok === 1 ? '+ ' . count($daftar) . ' catatan lain' : 'Catatan bidan/perawat (' . count($daftar) . ')' }}</summary>
                @endif
                @foreach($daftar as $i)
                    <div class="resume-ref-item">
                        <span class="resume-ref-sumber {{ in_array($i['peran'], ['dokter', 'sistem'], true) ? '' : 'non-dokter' }}">{{ $i['sumber'] }}</span>
                        <div class="resume-ref-teks">{{ $i['teks'] }}</div>
                        @unless(in_array($kunci, $infoSaja, true))
                            {{-- Diagnosa awal dari kamar inap masuk ke kolom diagnosa awal, bukan keluhan. --}}
                            <button type="button" class="btn btn-sm btn-outline-secondary resume-pakai"
                                    data-teks="{{ $i['teks'] }}" data-tanggal="{{ $i['tanggal'] }}"
                                    @if($i['sumber'] === 'Diagnosa awal masuk') data-target="diagnosa_awal" @endif
                                    aria-label="Pakai: {{ \Illuminate\Support\Str::limit($i['teks'], 60) }}">{{ $kunci === 'terapi' ? 'Pakai semua' : 'Pakai' }}</button>
                        @endunless
                    </div>
                @endforeach
                @if($kelompok > 0)
                    </details>
                @endif
            @endforeach
        @endif
    </div>
@endforeach
