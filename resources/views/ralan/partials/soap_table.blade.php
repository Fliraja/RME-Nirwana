<div class="table-responsive">
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Tgl/Jam Update</th>
                <th>Subjek</th>
                <th>Objek</th>
                <th>Assesmen</th>
                <th>Plan</th>
                <th>Instruksi</th>
            </tr>
        </thead>
        <tbody>
            @if($pasien->pemeriksaanRalan)
                <tr>
                    <td>{{ $pasien->pemeriksaanRalan->tgl_perawatan }} {{ $pasien->pemeriksaanRalan->jam_rawat }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->keluhan }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->pemeriksaan }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->penilaian }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->rtl }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->instruksi }}</td>
                </tr>
            @else
                <tr><td colspan="6" class="text-center">Belum ada data SOAP.</td></tr>
            @endif
        </tbody>
    </table>
</div>
