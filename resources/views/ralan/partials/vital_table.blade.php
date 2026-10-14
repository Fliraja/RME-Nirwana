<div class="table-responsive">
    <table class="table table-bordered table-sm table-hover" id="tableVitalRalan">
        <thead class="bg-light text-center small">
            <tr>
                <th>Tgl/Jam</th>
                <th>Tensi</th>
                <th>Suhu</th>
                <th>Nadi</th>
                <th>Respi</th>
                <th>SpO2</th>
                <th>TB/BB</th>
                <th>GCS</th>
                <th>Alergi</th>
            </tr>
        </thead>
        <tbody class="small text-center">
            @if($pasien->pemeriksaanRalan)
            <tr class="vital-row-item" data-vital="{{ json_encode($pasien->pemeriksaanRalan) }}" style="cursor: pointer;" title="Klik untuk menyalin data Vital Sign ke form">
                <td>{{ $pasien->pemeriksaanRalan->tgl_perawatan }} <br> {{ $pasien->pemeriksaanRalan->jam_rawat }}</td>
                <td>{{ $pasien->pemeriksaanRalan->tensi }}</td>
                <td>{{ $pasien->pemeriksaanRalan->suhu_tubuh }} °C</td>
                <td>{{ $pasien->pemeriksaanRalan->nadi }}</td>
                <td>{{ $pasien->pemeriksaanRalan->respirasi }}</td>
                <td>{{ $pasien->pemeriksaanRalan->spo2 ? $pasien->pemeriksaanRalan->spo2 . ' %' : '-' }}</td>
                <td>{{ $pasien->pemeriksaanRalan->tinggi }} / {{ $pasien->pemeriksaanRalan->berat }}</td>
                <td>{{ $pasien->pemeriksaanRalan->gcs }} ({{ $pasien->pemeriksaanRalan->kesadaran }})</td>
                <td class="text-start text-danger fw-bold">{{ $pasien->pemeriksaanRalan->alergi }}</td>
            </tr>
            @else
            <tr>
                <td colspan="9" class="text-muted p-3">Belum ada riwayat pemeriksaan hari ini.</td>
            </tr>
            @endif
        </tbody>
    </table>
</div>
