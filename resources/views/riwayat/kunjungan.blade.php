{{--
    Tab Riwayat bersama IGD, ralan, dan ranap. Data dari App\Services\Kunjungan\RiwayatKunjungan.
    $riwayat: paginator kunjungan, $noRawatAktif: kunjungan yang sedang dibuka (boleh null), $noRkmMedis.
--}}
@php
    use App\Services\Kunjungan\RiwayatKunjungan as RK;
    $jam = fn ($waktu) => $waktu ? \Carbon\Carbon::parse($waktu)->format('d/m H.i') : '';
@endphp
<div id="riwayatmedis" class="riwayat">
    @forelse($riwayat as $k)
        @php($aktif = isset($noRawatAktif) && $noRawatAktif === $k->no_rawat)
        <article class="riwayat-kartu" aria-labelledby="riwayat-{{ $loop->index }}">
            <header class="riwayat-kepala">
                <h3 class="riwayat-tanggal" id="riwayat-{{ $loop->index }}">{{ RK::tanggal($k->tanggal) }}</h3>
                <span class="riwayat-jenis">
                    @if($k->jenis === 'ranap')
                        @if($k->ranap['masih_dirawat'])
                            Rawat inap · sejak {{ RK::tanggal($k->ranap['masuk'], 'd/m') }}
                        @elseif($k->ranap['lama'])
                            Rawat inap · {{ $k->ranap['lama'] }} hari ({{ RK::tanggal($k->ranap['masuk'], 'd/m') }} s.d. {{ RK::tanggal($k->ranap['keluar'], 'd/m') }})
                        @else
                            Rawat inap
                        @endif
                    @else
                        {{ $k->unit }}
                    @endif
                </span>
                @if($aktif)
                    <span class="resume-status resume-status-ok">kunjungan ini</span>
                @endif
                @if($k->jenis === 'ranap' && $k->ranap['pulang'])
                    <span class="riwayat-jenis">pulang: {{ $k->ranap['pulang'] }}</span>
                @endif
                <span class="riwayat-meta">
                    @if($k->jenis === 'ranap')
                        {{ $k->lewat_igd ? 'masuk lewat IGD · ' : '' }}DPJP {{ $k->ranap['dpjp'] ?: ($k->dokter ?? '-') }}
                    @else
                        {{ $k->dokter ?? '-' }}
                    @endif
                    · {{ $k->no_rawat }}
                </span>
            </header>

            <div class="riwayat-badan">
                <dl class="riwayat-isi">
                    @if($k->jenis === 'ranap')
                        <dt>Diagnosis</dt>
                        @if($k->ranap['dx_awal'] === '' && $k->ranap['dx_akhir'] === '')
                            <dd class="riwayat-redup">Belum tercatat.</dd>
                        @else
                            <dd>{{ 'Masuk: ' . ($k->ranap['dx_awal'] ?: '-') . ($k->ranap['dx_akhir'] !== '' ? ' → akhir: ' . $k->ranap['dx_akhir'] : '') }}</dd>
                        @endif
                        @if($k->igd)
                            <dt>Asesmen IGD ({{ $k->igd['dokter'] ?? '-' }} · {{ $jam($k->igd['waktu']) }})</dt>
                            <dd>{{ $k->igd['keluhan'] ?: '-' }}@if($k->igd['diagnosis'] !== ''). Dx: {{ $k->igd['diagnosis'] }}@endif</dd>
                        @elseif($k->soap)
                            <dt>Pemeriksaan poli</dt>
                            <dd>{{ $k->soap['A'] ?: $k->soap['S'] }}</dd>
                        @endif
                        @php($cppt = $k->ranap['cppt'])
                        @if($cppt)
                            @php($akhir = $cppt[0])
                            <dt>CPPT dokter terakhir ({{ $akhir['dokter'] }} · {{ $jam($akhir['waktu']) }})</dt>
                            @php($ap = array_filter([$akhir['A'] !== '' ? 'A: ' . $akhir['A'] : '', $akhir['P'] !== '' ? 'P: ' . $akhir['P'] : '']))
                            <dd>{{ $ap ? implode("\n", $ap) : ($akhir['S'] ?: $akhir['O']) }}</dd>
                            <dd class="riwayat-blok">
                                <details class="riwayat-lengkap">
                                    <summary>Lihat {{ count($cppt) }} catatan CPPT dokter</summary>
                                    <ol class="riwayat-daftar">
                                        @foreach($cppt as $c)
                                            <li>
                                                <span class="riwayat-meta">{{ $jam($c['waktu']) }} · {{ $c['dokter'] }}</span>
                                                @foreach(['S', 'O', 'A', 'P'] as $b)
                                                    @if($c[$b] !== '')<span class="riwayat-baris"><b>{{ $b }}:</b> {{ $c[$b] }}</span>@endif
                                                @endforeach
                                            </li>
                                        @endforeach
                                    </ol>
                                </details>
                            </dd>
                        @else
                            <dt>CPPT dokter</dt>
                            <dd class="riwayat-redup">Belum ada CPPT dokter.</dd>
                        @endif
                    @elseif($k->jenis === 'igd')
                        @if($k->igd)
                            <dt>Keluhan utama</dt><dd>{{ $k->igd['keluhan'] ?: '-' }}</dd>
                            <dt>Pemeriksaan</dt><dd>{{ $k->igd['pemeriksaan'] ?: '-' }}</dd>
                            <dt>Diagnosis</dt><dd>{{ $k->igd['diagnosis'] ?: '-' }}</dd>
                            <dt>Tatalaksana</dt><dd>{{ $k->igd['tatalaksana'] ?: '-' }}</dd>
                            <dd class="riwayat-meta">Asesmen oleh {{ $k->igd['dokter'] ?? '-' }} · {{ $jam($k->igd['waktu']) }}</dd>
                        @else
                            <dt>Asesmen IGD</dt>
                            <dd class="riwayat-redup">Asesmen IGD belum ditulis.</dd>
                        @endif
                    @else
                        @if($k->soap)
                            @foreach(['S' => 'Subjektif', 'O' => 'Objektif', 'A' => 'Asesmen', 'P' => 'Plan'] as $b => $label)
                                <dt>{{ $b }} · {{ $label }}</dt><dd>{{ $k->soap[$b] ?: '-' }}</dd>
                            @endforeach
                        @else
                            <dt>SOAP</dt>
                            <dd class="riwayat-redup">SOAP belum ditulis.</dd>
                        @endif
                    @endif
                </dl>

                <dl class="riwayat-isi">
                    <dt>Laboratorium{{ $k->lab['jumlah'] ? ' · ' . $k->lab['jumlah'] . ' pemeriksaan' : '' }}</dt>
                    @if(!$k->lab['jumlah'])
                        <dd class="riwayat-redup">Tidak ada hasil lab.</dd>
                    @else
                        @if($k->lab['luar'])
                            <dd><span class="resume-status resume-status-tunggu">{{ count($k->lab['luar']) }} di luar rujukan</span></dd>
                            <dd>{{ implode("\n", $k->lab['luar']) }}</dd>
                        @else
                            <dd class="riwayat-redup">Semua dalam rujukan.</dd>
                        @endif
                        <dd class="riwayat-blok">
                            <details class="riwayat-lengkap">
                                <summary>Lihat semua hasil</summary>
                                <div class="riwayat-daftar">
                                    @foreach($k->lab['per_tanggal'] as $tgl => $paket)
                                        @foreach($paket as $nama => $baris)
                                            <p class="riwayat-meta">{{ RK::tanggal($tgl, 'd/m') }} · {{ $nama }}</p>
                                            <p class="riwayat-baris">{{ implode("\n", $baris) }}</p>
                                        @endforeach
                                    @endforeach
                                </div>
                            </details>
                        </dd>
                    @endif

                    <dt>Radiologi</dt>
                    @forelse($k->radiologi as $r)
                        <dd class="riwayat-blok">
                            <span class="riwayat-meta">{{ $r['pemeriksaan'] }} · {{ RK::tanggal($r['tanggal'], 'd/m') }}</span>
                            @if(mb_strlen($r['hasil']) > 220)
                                <span class="riwayat-baris">{{ \Illuminate\Support\Str::limit($r['hasil'], 220) }}</span>
                                <details class="riwayat-lengkap">
                                    <summary>Baca hasil lengkap</summary>
                                    <p class="riwayat-baris riwayat-daftar">{{ $r['hasil'] }}</p>
                                </details>
                            @else
                                <span class="riwayat-baris">{{ $r['hasil'] }}</span>
                            @endif
                        </dd>
                    @empty
                        @unless($k->gambar)
                            <dd class="riwayat-redup">Tidak ada hasil radiologi.</dd>
                        @endunless
                    @endforelse
                    @if($k->gambar)
                        <dd class="riwayat-blok riwayat-gambar">
                            @foreach($k->gambar as $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener">Gambar {{ $loop->iteration }}</a>
                            @endforeach
                        </dd>
                    @endif

                    <dt>Obat{{ $k->obat ? ' · ' . count($k->obat) . ' jenis' : '' }}</dt>
                    @if(!$k->obat)
                        <dd class="riwayat-redup">Tidak ada obat dari apotek.</dd>
                    @else
                        @php($ringkas = array_slice($k->obat, 0, 5))
                        <dd>{{ collect($ringkas)->map(fn ($o) => "{$o['nama']} ({$o['jumlah']} {$o['satuan']})")->implode("\n") }}</dd>
                        @if(count($k->obat) > 5)
                            <dd class="riwayat-blok">
                                <details class="riwayat-lengkap">
                                    <summary>Lihat {{ count($k->obat) - 5 }} obat lainnya</summary>
                                    <p class="riwayat-baris riwayat-daftar">{{ collect(array_slice($k->obat, 5))->map(fn ($o) => "{$o['nama']} ({$o['jumlah']} {$o['satuan']})")->implode("\n") }}</p>
                                </details>
                            </dd>
                        @endif
                    @endif
                </dl>
            </div>
        </article>
    @empty
        <p class="riwayat-kosong">Belum ada kunjungan untuk pasien ini.</p>
    @endforelse

    <div class="riwayat-kaki">
        @if(!empty($noRkmMedis) && $riwayat->total())
            <a href="{{ route('report.riwayat-pdf', $noRkmMedis) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Cetak 5 riwayat terakhir</a>
        @endif
        {{ $riwayat->links() }}
    </div>
</div>
