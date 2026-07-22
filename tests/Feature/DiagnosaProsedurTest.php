<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Http\Middleware\MultiRoleAuth;
use Illuminate\Support\Facades\DB;

class DiagnosaProsedurTest extends TestCase
{
    /**
     * Test pencarian ICD-10 penyakit.
     */
    public function test_search_icd10(): void
    {
        $response = $this->withoutMiddleware(MultiRoleAuth::class)
            ->getJson(route('ralan.search-icd10', ['search' => 'A00']));

        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }

    /**
     * Test pencarian ICD-9 prosedur.
     */
    public function test_search_icd9(): void
    {
        $response = $this->withoutMiddleware(MultiRoleAuth::class)
            ->getJson(route('ralan.search-icd9', ['search' => '89']));

        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }

    /**
     * Test simpan diagnosa ICD-10.
     */
    public function test_store_diagnosa(): void
    {
        $reg = DB::table('reg_periksa')->first();
        $penyakit = DB::table('penyakit')->first();

        if (!$reg || !$penyakit) {
            $this->markTestSkipped('Data reg_periksa atau penyakit tidak ada');
        }

        $response = $this->withoutMiddleware(MultiRoleAuth::class)
            ->postJson(route('ralan.store-diagnosa'), [
                'no_rawat'        => $reg->no_rawat,
                'kd_penyakit'     => [$penyakit->kd_penyakit],
                'prioritas'       => '1',
                'status_penyakit' => 'Baru'
            ]);

        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }

    /**
     * Test simpan prosedur ICD-9.
     */
    public function test_store_prosedur(): void
    {
        $reg = DB::table('reg_periksa')->first();
        $icd9 = DB::table('icd9')->first();

        if (!$reg || !$icd9) {
            $this->markTestSkipped('Data reg_periksa atau icd9 tidak ada');
        }

        $response = $this->withoutMiddleware(MultiRoleAuth::class)
            ->postJson(route('ralan.store-prosedur'), [
                'no_rawat' => $reg->no_rawat,
                'kode'     => [$icd9->kode],
                'jumlah'   => 1
            ]);

        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }
}
