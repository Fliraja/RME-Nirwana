<?php

namespace App\Exceptions;

use RuntimeException;

/** Permintaan dari halaman IGD ditolak; status HTTP ikut dibawa (404 bukan pasien IGD, 409 pasien sudah rawat inap). */
class OrderIgdTerkunci extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 409)
    {
        parent::__construct($message);
    }
}
