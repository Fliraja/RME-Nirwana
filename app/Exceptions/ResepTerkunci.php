<?php

namespace App\Exceptions;

use RuntimeException;

/** Simpan/hapus resep ditolak karena alasan yang bisa dibaca dokter (sudah divalidasi apotek, stok habis). */
class ResepTerkunci extends RuntimeException
{
}
