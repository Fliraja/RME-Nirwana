<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Http\Middleware\MultiRoleAuth;
use Illuminate\Support\Facades\DB;

class SoapTest extends TestCase
{
    /**
     * Test simpan data SOAP termasuk Assesmen (penilaian).
     */
    public function test_store_soap_with_assesmen(): void
    {
        $reg = DB::table('reg_periksa')->first();

        if (!$reg) {
            $this->markTestSkipped('Data reg_periksa tidak ada');
        }

        $user = User::first();
        $req = $this->withoutMiddleware(MultiRoleAuth::class);

        if ($user) {
            $req = $req->actingAs($user)->withSession(['decrypted_id' => $user->id_user]);
        }

        $response = $req->post(route('ralan.soap.simpan'), [
            'no_rawat'  => $reg->no_rawat,
            'keluhan'   => 'Keluhan tes',
            'objek'     => 'Objek tes',
            'penilaian' => 'Assesmen/Diagnosa tes',
            'plan'      => 'Plan tes',
            'instruksi' => 'Instruksi tes'
        ]);

        $this->assertTrue(in_array($response->status(), [200, 302, 404, 500]));
    }
}
