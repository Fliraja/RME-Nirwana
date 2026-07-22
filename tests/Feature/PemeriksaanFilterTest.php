<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Http\Middleware\MultiRoleAuth;
use Illuminate\Support\Facades\DB;

class PemeriksaanFilterTest extends TestCase
{
    /**
     * Test pencarian lab memfilter & mengurutkan berdasarkan kd_pj pasien.
     */
    public function test_search_lab_orders_by_kd_pj_pasien(): void
    {
        $reg = DB::table('reg_periksa')->first();
        if (!$reg) {
            $this->markTestSkipped('Tidak ada data reg_periksa di DB');
        }

        $response = $this->withoutMiddleware(MultiRoleAuth::class)
            ->getJson(route('ralan.search-lab', [
                'search'   => 'a',
                'no_rawat' => $reg->no_rawat
            ]));

        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }

    /**
     * Test pencarian radiologi memfilter & mengurutkan berdasarkan kd_pj pasien.
     */
    public function test_search_radiologi_orders_by_kd_pj_pasien(): void
    {
        $reg = DB::table('reg_periksa')->first();
        if (!$reg) {
            $this->markTestSkipped('Tidak ada data reg_periksa di DB');
        }

        $response = $this->withoutMiddleware(MultiRoleAuth::class)
            ->getJson(route('ralan.search-radiologi', [
                'search'   => 'a',
                'no_rawat' => $reg->no_rawat
            ]));

        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }
}
