{{-- Kartu resep IGD: satu resep untuk obat/cairan yang sudah dipakai di IGD, BHP, dan obat pulang. --}}
@php
    $statusKelas = ['menunggu apotek' => 'igd-st-tunggu', 'diterima apotek' => 'igd-st-proses', 'diserahkan' => 'igd-st-hasil'];
    $angka = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
@endphp
<section class="ralan-card igd-kartu" aria-labelledby="igd-judul-resep">
    <div class="igd-kartu-kepala">
        <span class="igd-ikon" aria-hidden="true"><i class="fas fa-prescription-bottle-medical"></i></span>
        <h3 class="igd-kartu-judul" id="igd-judul-resep">Resep, obat &amp; BHP <small>{{ $resep->count() }} resep</small></h3>
        @unless($pesanTerkunci)
            <button type="button" class="btn btn-sm igd-btn-garis" data-bs-toggle="collapse" data-bs-target="#igd-form-resep"
                    aria-expanded="false" aria-controls="igd-form-resep">
                <i class="fas fa-plus" aria-hidden="true"></i> Tambah item
            </button>
            <button type="button" class="btn btn-sm igd-btn-garis" data-bs-toggle="collapse" data-bs-target="#igd-form-racikan"
                    aria-expanded="false" aria-controls="igd-form-racikan">
                <i class="fas fa-mortar-pestle" aria-hidden="true"></i> Racikan
            </button>
        @endunless
    </div>

    @unless($pesanTerkunci)
        <datalist id="igd-aturan-daftar">
            @foreach($aturanPakai as $a)<option value="{{ $a }}">@endforeach
        </datalist>

        <form class="collapse igd-form" id="igd-form-resep" novalidate>
            <div class="igd-form-isi">
                <p class="igd-bantu">Obat/cairan yang sudah dipakai di IGD, BHP, dan obat pulang masuk satu resep. Tulis "di IGD" bila meragukan.</p>
                <div class="igd-resep-baris">
                    <div class="igd-resep-obat">
                        <label class="igd-label" for="igd-select-obat">Obat / BHP</label>
                        <select id="igd-select-obat" placeholder="Ketik nama obat atau BHP..."></select>
                    </div>
                    <div class="igd-resep-jumlah">
                        <label class="igd-label" for="igd-jumlah-obat">Jumlah</label>
                        <input type="number" id="igd-jumlah-obat" min="0.1" step="any" value="1" class="form-control form-control-sm" inputmode="decimal">
                    </div>
                    <div class="igd-resep-aturan">
                        <label class="igd-label" for="igd-aturan-obat">Aturan pakai</label>
                        <input type="text" id="igd-aturan-obat" maxlength="150" list="igd-aturan-daftar" class="form-control form-control-sm" placeholder="Contoh: 3x1 sesudah makan">
                    </div>
                </div>
                <div class="igd-chip-baris" role="group" aria-label="Isi cepat aturan pakai">
                    <button type="button" class="igd-chip-isi" data-aturan="di IGD">+ di IGD</button>
                    <button type="button" class="igd-chip-isi" data-aturan="BHP">+ BHP</button>
                    <button type="button" class="btn btn-sm igd-btn-garis ms-auto" data-tambah-item>Tambah ke daftar</button>
                </div>
                <div class="igd-stok-peringatan" aria-live="polite"></div>

                <div class="igd-daftar-tunggu" aria-live="polite">
                    <p class="igd-kosong mb-0" data-tunggu-kosong>Belum ada item. Pilih obat lalu tekan "Tambah ke daftar" atau Enter.</p>
                    <ul class="igd-tunggu-list"></ul>
                </div>
                <div class="igd-form-aksi">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#igd-form-resep">Tutup</button>
                    <button type="submit" class="btn btn-sm igd-btn-utama" disabled>Kirim resep</button>
                </div>
            </div>
        </form>

        <form class="collapse igd-form" id="igd-form-racikan" novalidate>
            <div class="igd-form-isi">
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="igd-label" for="igd-racik-nama">Nama racikan</label>
                        <input type="text" id="igd-racik-nama" maxlength="100" class="form-control form-control-sm" placeholder="Contoh: puyer batuk">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="igd-label" for="igd-racik-metode">Bentuk</label>
                        <select id="igd-racik-metode" class="form-select form-select-sm">
                            @foreach($metodeRacik as $m)<option value="{{ $m->kd_racik }}">{{ $m->nm_racik }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="igd-label" for="igd-racik-jumlah">Jumlah (bungkus/botol)</label>
                        <input type="number" id="igd-racik-jumlah" min="1" step="1" value="10" class="form-control form-control-sm" inputmode="numeric">
                    </div>
                    <div class="col-12">
                        <label class="igd-label" for="igd-racik-aturan">Aturan pakai</label>
                        <input type="text" id="igd-racik-aturan" maxlength="150" list="igd-aturan-daftar" class="form-control form-control-sm" placeholder="Contoh: 3x1 bungkus">
                    </div>
                </div>
                <div class="igd-label mt-2">Bahan (dosis per bungkus, contoh 1/3 atau 0,5)</div>
                <ul class="igd-bahan-list"></ul>
                <button type="button" class="btn btn-sm igd-btn-garis" data-tambah-bahan><i class="fas fa-plus" aria-hidden="true"></i> Tambah bahan</button>
                <div class="igd-form-aksi">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#igd-form-racikan">Tutup</button>
                    <button type="submit" class="btn btn-sm igd-btn-utama">Kirim racikan</button>
                </div>
            </div>
        </form>
    @endunless

    <div class="igd-kartu-badan">
        @forelse($resep as $r)
            <div class="igd-order">
                <div class="igd-order-kepala">
                    <span class="igd-order-meta">
                        {{ \Carbon\Carbon::parse($r->tgl_peresepan . ' ' . $r->jam_peresepan)->format('d/m H.i') }}
                        · {{ $r->nm_dokter ?? $r->kd_dokter }} · No. resep {{ $r->no_resep }}
                    </span>
                    <span class="igd-st {{ $statusKelas[$r->status_teks] ?? 'igd-st-tunggu' }}">{{ $r->status_teks }}</span>
                </div>

                @if($r->divalidasi && $r->diberikan->isNotEmpty())
                    <div class="igd-resep-sumber">diberikan apotek</div>
                    <ul class="igd-item-list">
                        @foreach($r->diberikan->groupBy(fn ($d) => $d['racikan'] ? 'r' . $d['no_racik'] : 'o' . $d['kode_brng'] . $d['jam']) as $grup)
                            @php $d = $grup->first(); @endphp
                            <li class="igd-item">
                                @if($d['racikan'])
                                    <span class="igd-item-nama">{{ $d['nama_racik'] }} <small>(racikan: {{ $grup->pluck('nama_brng')->implode(', ') }})</small></span>
                                @else
                                    <span class="igd-item-nama">{{ $d['nama_brng'] }}</span>
                                    <span class="igd-item-jml">{{ $angka($d['jml']) }} {{ $d['satuan'] }}</span>
                                @endif
                                <span class="igd-item-aturan">{{ $d['aturan'] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if($r->tidak_diberikan->isNotEmpty())
                        <div class="igd-resep-sumber igd-resep-tidak"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> tidak diberikan apotek, tanyakan ke apotek atau minta ulang</div>
                        <ul class="igd-item-list">
                            @foreach($r->tidak_diberikan as $t)
                                <li class="igd-item">
                                    <span class="igd-item-nama">{{ $t->nama }}</span>
                                    <span class="igd-item-jml">{{ $angka($t->jml) }} {{ $t->satuan }}</span>
                                    <span class="igd-item-aturan">{{ $t->aturan }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <ul class="igd-item-list">
                        @foreach($r->items as $it)
                            <li class="igd-item">
                                <span class="igd-item-nama">{{ trim($it->nama_brng) }}</span>
                                <span class="igd-item-jml">{{ $angka($it->jml) }} {{ $it->kode_sat }}</span>
                                <span class="igd-item-aturan">{{ $it->aturan_pakai }}</span>
                                @if($r->boleh_hapus && !$pesanTerkunci)
                                    <button type="button" class="btn btn-sm igd-btn-hapus" data-hapus-resep="obat" data-no-resep="{{ $r->no_resep }}"
                                            data-kode="{{ $it->kode_brng }}" aria-label="Hapus {{ trim($it->nama_brng) }} dari resep">
                                        <i class="fas fa-trash-can" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </li>
                        @endforeach
                        @foreach($r->racikan as $rk)
                            <li class="igd-item">
                                <span class="igd-item-nama">{{ $rk->nama_racik }}
                                    <small>({{ $rk->nm_racik ?? $rk->kd_racik }}: {{ $rk->detail->map(fn ($b) => trim($b->nama_brng) . ' ' . $b->kandungan)->implode(', ') }})</small></span>
                                <span class="igd-item-jml">{{ $rk->jml_dr }}</span>
                                <span class="igd-item-aturan">{{ $rk->aturan_pakai }}</span>
                                @if($r->boleh_hapus && !$pesanTerkunci)
                                    <button type="button" class="btn btn-sm igd-btn-hapus" data-hapus-resep="racikan" data-no-resep="{{ $r->no_resep }}"
                                            data-kode="{{ $rk->no_racik }}" aria-label="Hapus racikan {{ $rk->nama_racik }}">
                                        <i class="fas fa-trash-can" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <p class="igd-kosong mb-0">Belum ada resep. Obat, cairan, dan BHP yang dipakai di IGD juga ditulis di sini supaya apotek bisa mencatatnya.</p>
        @endforelse
    </div>
</section>
