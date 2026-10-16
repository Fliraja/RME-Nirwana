<?php

namespace App\Services\Ranap;

use App\Support\DiagnosaSplitter;
use App\Support\ResumeFormat;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Referensi per isian resume ranap, disusun dari data yang sudah ada.
 * Hanya membaca: dokter yang memutuskan apa yang dipakai.
 */
class ResumeRanapReferensi
{
    public const KUNCI = ['keluhan', 'perjalanan', 'pemeriksaan', 'lab', 'penunjang', 'diagnosis',
        'tindakan', 'terapi', 'kondisi', 'obat_pulang', 'instruksi', 'kontrol'];

    public const BUKAN_TINDAKAN = '/visite|konsul|jasa|askep|administrasi|observasi|bakhp/i';

    // Kategori/golongan databarang di prod tidak konsisten (paracetamol "-", apron "Formularium"),
    // jadi BHP disaring dari namanya. Hanya referensi; dokter tetap memilih.
    public const BUKAN_OBAT = '/\b(apron|kass?a|infu(?:s ?)?set|perfusor|spuit|syringe|hands?c|glove|masker|plester|hypafix|abbocath|iv ?cath|venflon|kateter|catheter|urine ?bag|underpad|swab|dermafix|tegaderm|nasal ?ca?nul|selang|blood ?set|transfusi ?set|three ?way|elektroda|electrode|kapas|perban|verban|jarum|needle|ngt|suction|lidi|kantong)/i';

    /** @return array<string, list<array{teks:string, sumber:string, peran:string, tanggal:?string}>> */
    public function untuk(string $noRawat): array
    {
        $ref = array_fill_keys(self::KUNCI, []);
        $cppt = $this->cppt($noRawat);
        $igd = DB::table('penilaian_medis_igd')->where('no_rawat', $noRawat)->orderByDesc('tanggal')->first();
        $kamar = DB::table('kamar_inap')->where('no_rawat', $noRawat)
            ->orderBy('tgl_masuk')->orderBy('jam_masuk')->get(['diagnosa_awal', 'stts_pulang']);

        $ref['keluhan'] = $this->keluhan($cppt, $igd, $kamar->first()?->diagnosa_awal);
        $ref['perjalanan'] = $this->perjalanan($cppt);
        $ref['pemeriksaan'] = $this->pemeriksaan($cppt, $igd);
        $ref['lab'] = $this->lab($noRawat, $igd);
        $ref['penunjang'] = $this->penunjang($noRawat, $igd);
        $ref['diagnosis'] = $this->diagnosis($cppt, $igd);
        $ref['tindakan'] = $this->tindakan($noRawat);
        [$ref['terapi'], $ref['obat_pulang']] = $this->obat($noRawat);
        $ref['kondisi'] = $this->kondisi($kamar->last()?->stts_pulang);
        $ref['instruksi'] = $this->instruksi($cppt, $igd);
        $ref['kontrol'] = $this->kontrol($noRawat);

        return $ref;
    }

    /** Semua baris CPPT dalam satu query, berurutan waktu, dengan peran penulisnya. */
    private function cppt(string $noRawat): Collection
    {
        return DB::table('pemeriksaan_ranap as x')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'x.nip')
            ->leftJoin('petugas as p', 'p.nip', '=', 'x.nip')
            ->leftJoin('jabatan as j', 'j.kd_jbtn', '=', 'p.kd_jbtn')
            ->where('x.no_rawat', $noRawat)
            ->orderBy('x.tgl_perawatan')->orderBy('x.jam_rawat')
            ->get(['x.*', 'd.kd_dokter', 'd.nm_dokter', 'j.nm_jbtn'])
            ->map(function ($r) {
                $r->peran = $r->kd_dokter ? 'dokter' : (stripos((string) $r->nm_jbtn, 'BIDAN') !== false ? 'bidan' : 'perawat');
                return $r;
            });
    }

    /** Dokter dulu, lalu bidan/perawat; urutan di dalam tiap kelompok dipertahankan. */
    private static function dokterDulu(array $item): array
    {
        usort($item, fn ($a, $b) => ($a['peran'] !== 'dokter') <=> ($b['peran'] !== 'dokter'));

        return $item;
    }

    private static function isi($nilai): string
    {
        $nilai = trim((string) $nilai);

        return $nilai === '-' ? '' : $nilai;
    }

    private static function item(string $teks, string $sumber, string $peran = 'sistem', ?string $tanggal = null): array
    {
        return ['teks' => $teks, 'sumber' => $sumber, 'peran' => $peran, 'tanggal' => $tanggal];
    }

    private static function tgl(?string $tanggal): string
    {
        return $tanggal ? Carbon::parse($tanggal)->format('d/m') : '';
    }

    private static function sumberCppt(object $r, string $bagian): string
    {
        $siapa = $r->peran === 'dokter' ? 'Visite ' . $r->nm_dokter : ucfirst($r->peran);

        return "{$siapa} · " . self::tgl($r->tgl_perawatan) . " · {$bagian}";
    }

    /** Baris pertama (atau terakhir) yang kolomnya terisi, satu per kelompok peran. */
    private static function perPeran(Collection $cppt, string $kolom, bool $terakhir): array
    {
        $item = [];
        foreach ($cppt->filter(fn ($r) => self::isi($r->{$kolom}) !== '')->groupBy('peran') as $baris) {
            $item[] = $terakhir ? $baris->last() : $baris->first();
        }

        return $item;
    }

    private function keluhan(Collection $cppt, ?object $igd, ?string $diagnosaAwal): array
    {
        $item = [];
        if ($igd && self::isi($igd->keluhan_utama) !== '') {
            $item[] = self::item(self::isi($igd->keluhan_utama), 'Asesmen IGD · keluhan utama', 'dokter', substr($igd->tanggal, 0, 10));
        }
        if ($igd && self::isi($igd->rps) !== '') {
            $item[] = self::item(self::isi($igd->rps), 'Asesmen IGD · riwayat penyakit sekarang', 'dokter', substr($igd->tanggal, 0, 10));
        }
        foreach (self::perPeran($cppt, 'keluhan', false) as $r) {
            $item[] = self::item(self::isi($r->keluhan), self::sumberCppt($r, 'S'), $r->peran, $r->tgl_perawatan);
        }
        if (self::isi($diagnosaAwal) !== '') {
            $item[] = self::item(self::isi($diagnosaAwal), 'Diagnosa awal masuk');
        }

        return self::dokterDulu($item);
    }

    private function perjalanan(Collection $cppt): array
    {
        $item = [];
        $terisi = $cppt->filter(fn ($r) => self::isi($r->penilaian) !== '' || self::isi($r->rtl) !== '');
        foreach ($terisi->groupBy(fn ($r) => $r->peran . '|' . $r->tgl_perawatan) as $baris) {
            $r = $baris->last();
            $teks = implode(' · ', array_filter([
                self::isi($r->penilaian) !== '' ? 'A: ' . self::isi($r->penilaian) : '',
                self::isi($r->rtl) !== '' ? 'P: ' . self::isi($r->rtl) : '',
            ]));
            $item[] = self::item($teks, self::sumberCppt($r, 'A/P'), $r->peran, $r->tgl_perawatan);
        }

        return self::dokterDulu($item);
    }

    private function pemeriksaan(Collection $cppt, ?object $igd): array
    {
        $item = [];
        foreach (self::perPeran($cppt, 'pemeriksaan', true) as $r) {
            $item[] = self::item(self::isi($r->pemeriksaan), self::sumberCppt($r, 'O'), $r->peran, $r->tgl_perawatan);
        }
        $ttv = $cppt->last(fn ($r) => ResumeFormat::ttv($r) !== '');
        if ($ttv) {
            $item[] = self::item(ResumeFormat::ttv($ttv), self::sumberCppt($ttv, 'TTV terakhir'), $ttv->peran, $ttv->tgl_perawatan);
        }
        if ($igd) {
            $fisik = implode("\n", array_filter([self::isi($igd->ket_fisik), self::isi($igd->ket_lokalis)]));
            if ($fisik !== '') {
                $item[] = self::item($fisik, 'Asesmen IGD · pemeriksaan saat masuk', 'dokter', substr($igd->tanggal, 0, 10));
            }
        }

        return self::dokterDulu($item);
    }

    /** Lab per paket, hanya nilai di luar rujukan. */
    private function lab(string $noRawat, ?object $igd): array
    {
        $item = DB::table('detail_periksa_lab as d')
            ->join('template_laboratorium as t', function ($j) {
                $j->on('t.id_template', '=', 'd.id_template')->on('t.kd_jenis_prw', '=', 'd.kd_jenis_prw');
            })
            ->join('jns_perawatan_lab as j', 'j.kd_jenis_prw', '=', 'd.kd_jenis_prw')
            ->where('d.no_rawat', $noRawat)
            ->orderBy('d.tgl_periksa')->orderBy('d.jam')->orderBy('t.urut')
            ->get(['d.kd_jenis_prw', 'd.tgl_periksa', 'd.jam', 'j.nm_perawatan', 't.Pemeriksaan', 'd.nilai', 't.satuan', 'd.nilai_rujukan'])
            ->map(function ($r) {
                $r->flag = ResumeFormat::flagLab((string) $r->nilai, (string) $r->nilai_rujukan);
                return $r;
            })
            ->filter(fn ($r) => $r->flag !== '')
            ->groupBy(fn ($r) => "{$r->kd_jenis_prw}|{$r->tgl_periksa}|{$r->jam}")
            ->map(fn ($baris) => self::item(
                $baris->map(fn ($r) => ResumeFormat::barisLab((string) $r->Pemeriksaan, (string) $r->nilai, (string) $r->satuan, (string) $r->nilai_rujukan))->implode("\n"),
                'Lab ' . trim($baris->first()->nm_perawatan) . ' · ' . self::tgl($baris->first()->tgl_periksa),
                'sistem',
                $baris->first()->tgl_periksa
            ))
            ->values()->all();

        if ($igd && self::isi($igd->lab) !== '') {
            $item[] = self::item(self::isi($igd->lab), 'Asesmen IGD · lab', 'dokter', substr($igd->tanggal, 0, 10));
        }

        return $item;
    }

    private function penunjang(string $noRawat, ?object $igd): array
    {
        $item = DB::table('hasil_radiologi')->where('no_rawat', $noRawat)
            ->orderBy('tgl_periksa')->orderBy('jam')->get(['tgl_periksa', 'hasil'])
            ->filter(fn ($r) => self::isi($r->hasil) !== '')
            ->map(fn ($r) => self::item(self::isi($r->hasil), 'Radiologi · ' . self::tgl($r->tgl_periksa), 'sistem', $r->tgl_periksa))
            ->values()->all();

        foreach (['ekg' => 'EKG', 'rad' => 'radiologi'] as $kolom => $label) {
            if ($igd && self::isi($igd->{$kolom}) !== '') {
                $item[] = self::item(self::isi($igd->{$kolom}), "Asesmen IGD · {$label}", 'dokter', substr($igd->tanggal, 0, 10));
            }
        }

        return $item;
    }

    /** Chip diagnosis dari A: dokter terbaru dulu, lalu asesmen IGD, lalu bidan/perawat; tanpa duplikat. */
    private function diagnosis(Collection $cppt, ?object $igd): array
    {
        $sumber = [];
        foreach ($cppt->where('peran', 'dokter')->reverse() as $r) {
            $sumber[] = [$r->penilaian, self::sumberCppt($r, 'A'), 'dokter', $r->tgl_perawatan];
        }
        if ($igd) {
            $sumber[] = [$igd->diagnosis, 'Asesmen IGD · diagnosis', 'dokter', substr($igd->tanggal, 0, 10)];
        }
        foreach ($cppt->where('peran', '!=', 'dokter')->reverse() as $r) {
            $sumber[] = [$r->penilaian, self::sumberCppt($r, 'A'), $r->peran, $r->tgl_perawatan];
        }

        $item = [];
        $sudah = [];
        foreach ($sumber as [$teks, $label, $peran, $tanggal]) {
            $pecah = DiagnosaSplitter::pecah($teks);
            foreach (array_filter(array_merge([$pecah['utama']], $pecah['sekunder'])) as $dx) {
                $kunci = mb_strtolower($dx);
                if (!isset($sudah[$kunci])) {
                    $sudah[$kunci] = true;
                    $item[] = self::item($dx, $label, $peran, $tanggal);
                }
            }
        }

        return $item;
    }

    private function tindakan(string $noRawat): array
    {
        $tagihan = DB::table('rawat_inap_dr')->where('no_rawat', $noRawat)->select('kd_jenis_prw', 'tgl_perawatan')
            ->unionAll(DB::table('rawat_inap_drpr')->where('no_rawat', $noRawat)->select('kd_jenis_prw', 'tgl_perawatan'));

        $operasi = DB::table('operasi as o')->join('paket_operasi as p', 'p.kode_paket', '=', 'o.kode_paket')
            ->where('o.no_rawat', $noRawat)->orderBy('o.tgl_operasi')
            ->get(['p.nm_perawatan as nama', 'o.tgl_operasi as tanggal'])
            ->map(fn ($r) => self::item(mb_substr(trim($r->nama), 0, DiagnosaSplitter::MAKS_KARAKTER), 'Operasi · ' . self::tgl($r->tanggal), 'sistem', substr($r->tanggal, 0, 10)));

        $ditagih = DB::table('jns_perawatan_inap as j')
            ->joinSub($tagihan, 't', 't.kd_jenis_prw', '=', 'j.kd_jenis_prw')
            ->orderBy('t.tgl_perawatan')
            ->get(['j.nm_perawatan as nama', 't.tgl_perawatan as tanggal'])
            ->reject(fn ($r) => preg_match(self::BUKAN_TINDAKAN, (string) $r->nama))
            ->map(fn ($r) => self::item(mb_substr(trim($r->nama), 0, DiagnosaSplitter::MAKS_KARAKTER), 'Tindakan ditagih · ' . self::tgl($r->tanggal), 'sistem', $r->tanggal));

        return $operasi->merge($ditagih)->unique(fn ($i) => mb_strtolower($i['teks']))->values()->all();
    }

    /** @return array{0: list<array>, 1: list<array>} [obat selama dirawat, obat pada tanggal pemberian terakhir] */
    private function obat(string $noRawat): array
    {
        $diberikan = DB::table('detail_pemberian_obat as d')->join('databarang as b', 'b.kode_brng', '=', 'd.kode_brng')
            ->leftJoin('golongan_barang as g', 'g.kode', '=', 'b.kode_golongan')
            ->where('d.no_rawat', $noRawat)->where(fn ($q) => $q->whereNull('g.nama')->orWhere('g.nama', '<>', 'ALKES'))
            ->select('b.nama_brng as nama', 'd.tgl_perawatan as tanggal')
            ->unionAll(DB::table('obat_racikan')->where('no_rawat', $noRawat)->select('nama_racik as nama', 'tgl_perawatan as tanggal'))
            ->get()
            ->filter(fn ($r) => self::isi($r->nama) !== '' && !preg_match(self::BUKAN_OBAT, $r->nama));

        if ($diberikan->isEmpty()) {
            return [[], []];
        }

        $semua = $diberikan->pluck('nama')->map(fn ($n) => trim($n))->unique()->sort()->values();
        $terakhir = $diberikan->max('tanggal');
        $hariTerakhir = $diberikan->where('tanggal', $terakhir)->pluck('nama')->map(fn ($n) => trim($n))->unique()->sort()->values();

        return [
            [self::item($semua->implode(', '), 'Obat yang diberikan apotek selama dirawat')],
            [self::item($hariTerakhir->implode(', '), 'Obat yang diberikan ' . self::tgl($terakhir) . ' (bukan resep pulang)', 'sistem', $terakhir)],
        ];
    }

    private function kondisi(?string $sttsPulang): array
    {
        $stts = self::isi($sttsPulang);
        if ($stts === '' || $stts === 'Pindah Kamar') {
            return [];
        }

        return [self::item($stts, 'Status pulang dari perawat (Khanza)')];
    }

    private function instruksi(Collection $cppt, ?object $igd): array
    {
        $item = [];
        $terisi = $cppt->filter(fn ($r) => self::isi($r->rtl) !== '' || self::isi($r->instruksi) !== '');
        foreach ($terisi->groupBy('peran') as $baris) {
            $r = $baris->last();
            $teks = implode("\n", array_filter([self::isi($r->rtl), self::isi($r->instruksi)]));
            $item[] = self::item($teks, self::sumberCppt($r, 'P / instruksi'), $r->peran, $r->tgl_perawatan);
        }
        if ($igd && self::isi($igd->tata) !== '') {
            $item[] = self::item(self::isi($igd->tata), 'Asesmen IGD · tata laksana', 'dokter', substr($igd->tanggal, 0, 10));
        }

        return self::dokterDulu($item);
    }

    private function kontrol(string $noRawat): array
    {
        return DB::table('bridging_sep as s')
            ->join('bridging_surat_kontrol_bpjs as k', 'k.no_sep', '=', 's.no_sep')
            ->where('s.no_rawat', $noRawat)->whereNotNull('k.tgl_rencana')
            ->orderByDesc('k.tgl_surat')
            ->get(['k.tgl_rencana', 'k.nm_poli_bpjs', 'k.nm_dokter_bpjs'])
            ->map(fn ($r) => self::item(
                implode(', ', array_filter([Carbon::parse($r->tgl_rencana)->format('d/m/Y'), self::isi($r->nm_poli_bpjs), self::isi($r->nm_dokter_bpjs)])),
                'Surat kontrol BPJS',
                'sistem',
                $r->tgl_rencana
            ))
            ->values()->all();
    }
}
