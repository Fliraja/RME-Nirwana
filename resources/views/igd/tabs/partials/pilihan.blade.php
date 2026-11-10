{{-- Tombol pilihan berjajar untuk kolom enum Khanza. Kosong saat asesmen baru; radio asli supaya panah dan spasi bekerja. --}}
@php
    $teksOpsi = $tampil ?? [];
    $kelasOpsi = $kelasPilihan ?? [];
@endphp
<fieldset class="igd-pilih {{ $kelasGrup ?? '' }}" data-kolom="{{ $nama }}" aria-describedby="galat-as-{{ $nama }}">
    <legend class="igd-label">{{ $label }}@if($wajib ?? true) <span class="igd-wajib">wajib</span>@endif</legend>
    <div class="igd-pilih-daftar">
        @foreach($opsi as $o)
            <label class="igd-pilih-opsi {{ $kelasOpsi[$o] ?? '' }}">
                <input type="radio" name="{{ $nama }}" value="{{ $o }}" @checked($nilai === $o)>
                <i class="fas fa-check igd-pilih-centang" aria-hidden="true"></i>
                <span>{{ $teksOpsi[$o] ?? $o }}</span>
            </label>
        @endforeach
    </div>
    <div class="igd-galat" id="galat-as-{{ $nama }}" data-galat="{{ $nama }}"></div>
</fieldset>
