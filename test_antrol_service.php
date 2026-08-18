<?php

require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Services\Ralan\AntrolBpjsService;
use Illuminate\Support\Facades\DB;

// Setup database
$dbHost = 'localhost';
$dbName = 'db_test_rev';
$dbUser = 'root';
$dbPass = '';

try {
    DB::connection('mysql')->setPdo(new PDO(
        "mysql:host=$dbHost;dbname=$dbName",
        $dbUser,
        $dbPass
    ));
    
    echo "✓ Database connected\n\n";
    
    // Initialize service
    $service = new AntrolBpjsService();
    
    // Test 1: Setup test data
    echo "TEST 1: Setup data...\n";
    DB::table('penjab')->truncate();
    DB::table('reg_periksa')->truncate();
    DB::table('antrol_bpjs')->truncate();
    
    DB::table('penjab')->insert(['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS']);
    DB::table('reg_periksa')->insert([
        'no_rawat' => 'TEST001',
        'no_rkm_medis' => 'RM001',
        'kd_pj' => 'BPJ'
    ]);
    DB::table('antrol_bpjs')->insert([
        'no_rawat' => 'TEST001',
        'jam_periksa_perawat' => '00:00:00',
        'jam_periksa_dokter' => '00:00:00'
    ]);
    echo "✓ Data ready\n\n";
    
    // Test 2: Insert jam perawat
    echo "TEST 2: Insert jam perawat (first time)...\n";
    $result = $service->insertJamPeriksaPerawatOnce('TEST001', '10:00:00');
    echo "Result: " . json_encode($result) . "\n";
    echo "Expected: success=true\n";
    echo ($result['success'] ? "✓ PASS\n" : "✗ FAIL\n") . "\n";
    
    // Test 3: Insert jam perawat (second time - should skip)
    echo "TEST 3: Insert jam perawat (second time - should skip)...\n";
    $result = $service->insertJamPeriksaPerawatOnce('TEST001', '10:00:30');
    echo "Result: " . json_encode($result) . "\n";
    echo "Expected: success=false\n";
    echo (!$result['success'] ? "✓ PASS\n" : "✗ FAIL\n") . "\n";
    
    // Test 4: Verify jam not changed
    echo "TEST 4: Verify jam perawat not changed...\n";
    $antrol = DB::table('antrol_bpjs')->where('no_rawat', 'TEST001')->first();
    echo "Jam perawat: " . $antrol->jam_periksa_perawat . "\n";
    echo "Expected: 10:00:00\n";
    echo ($antrol->jam_periksa_perawat === '10:00:00' ? "✓ PASS\n" : "✗ FAIL\n") . "\n";
    
    // Test 5: Upsert jam dokter CEPAT (< 1 min)
    echo "TEST 5: Upsert jam dokter CEPAT (5 sec after perawat)...\n";
    $result = $service->upsertAntrolBpjsJam('TEST001', 'jam_periksa_dokter', '10:00:05');
    echo "Result: " . json_encode($result) . "\n";
    echo "Expected: success=true, jam=10:01:01 (forced to minimal)\n";
    $antrol = DB::table('antrol_bpjs')->where('no_rawat', 'TEST001')->first();
    echo "DB jam dokter: " . $antrol->jam_periksa_dokter . "\n";
    echo ($result['success'] && $antrol->jam_periksa_dokter === '10:01:01' ? "✓ PASS\n" : "✗ FAIL\n") . "\n";
    
    // Test 6: Upsert jam dokter LAMA (>= 1 min)
    echo "TEST 6: Upsert jam dokter LAMA (4 min after perawat)...\n";
    $result = $service->upsertAntrolBpjsJam('TEST001', 'jam_periksa_dokter', '10:04:00');
    echo "Result: " . json_encode($result) . "\n";
    echo "Expected: success=true, jam=10:04:00 (use realtime)\n";
    $antrol = DB::table('antrol_bpjs')->where('no_rawat', 'TEST001')->first();
    echo "DB jam dokter: " . $antrol->jam_periksa_dokter . "\n";
    echo ($result['success'] && $antrol->jam_periksa_dokter === '10:04:00' ? "✓ PASS\n" : "✗ FAIL\n") . "\n";
    
    echo "\n=== ALL TESTS DONE ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
