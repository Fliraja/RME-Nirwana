<div class="resume-baris">
    <span class="resume-baris-label"></span>
    <input type="text" class="form-control form-control-sm" name="{{ $nama }}[]" maxlength="80" value="{{ $nilai }}">
    <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-secondary resume-naik" aria-label="Naikkan">↑</button>
        <button type="button" class="btn btn-outline-secondary resume-turun" aria-label="Turunkan">↓</button>
        <button type="button" class="btn btn-outline-danger resume-hapus" aria-label="Hapus baris">✕</button>
    </div>
</div>
