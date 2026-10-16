@php
    $teks = ['belum' => 'Resume belum dibuat', 'draf' => 'Resume draf', 'selesai' => 'Resume selesai'][$status] ?? null;
@endphp
@if($teks)
    <span class="resume-status {{ $status === 'selesai' ? 'resume-status-ok' : 'resume-status-tunggu' }} d-inline-block text-nowrap mt-1">{{ $teks }}</span>
@endif
