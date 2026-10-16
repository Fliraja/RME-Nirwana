<div class="resume-baris">
    <span class="resume-baris-label"></span>
    <input type="text" class="form-control form-control-sm" name="{{ $nama }}[]" maxlength="80" value="{{ $nilai }}" placeholder="{{ $nama === 'diagnosa' ? 'Contoh: Dispepsia, GEA, Hipertensi...' : 'Contoh: Pasang Infus, EKG, Debridement...' }}">
    <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-secondary resume-naik" title="Naikkan urutan" aria-label="Naikkan">↑</button>
        <button type="button" class="btn btn-outline-secondary resume-turun" title="Turunkan urutan" aria-label="Turunkan">↓</button>
        <button type="button" class="btn btn-outline-danger resume-hapus" title="Hapus baris" aria-label="Hapus baris">✕</button>
    </div>
</div>
