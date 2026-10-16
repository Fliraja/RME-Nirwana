{{-- Kartu satu isian resume ranap. Dipakai lewat @component; $slot berisi kolom isian. --}}
@php $sudahTerisi = in_array($kunci, $terisi, true); @endphp
<section class="resume-isian" data-isian="{{ $kunci }}" data-kolom="{{ $kolom }}" aria-labelledby="judul-isian-{{ $kunci }}">
    <div class="resume-isian-kepala">
        <span class="resume-isian-ikon" aria-hidden="true"><i class="fas {{ $ikon }}"></i></span>
        <h3 class="resume-isian-judul" id="judul-isian-{{ $kunci }}">{{ $nomor }}. {{ $judul }}</h3>
        <span class="resume-isian-tanda {{ $sudahTerisi ? 'terisi' : 'kosong' }}">
            @if($sudahTerisi)<i class="fas fa-check me-1" aria-hidden="true"></i>terisi @else belum diisi @endif
        </span>
    </div>
    <div class="resume-isian-badan">
        {{ $slot }}
    </div>
</section>
