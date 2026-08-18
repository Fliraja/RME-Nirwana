<?php

// Load Composer autoload
require __DIR__ . '/vendor/autoload.php';

// Manual DB connection
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=db_test_rev',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Database connected\n\n";
    
    // Test 1: Setup data
    echo "=== TEST 1: Setup test data ===\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("TRUNCATE TABLE antrol_bpjs");
    $pdo->exec("TRUNCATE TABLE reg_periksa");
    $pdo->exec("TRUNCATE TABLE penjab");
    $pdo->exec("TRUNCATE TABLE pasien");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // Insert pasien first
    $pdo->exec("INSERT INTO pasien (no_rkm_medis, nm_pasien, tgl_lahir, alamat) VALUES ('RM001', 'Test Pasien', '2000-01-01', 'Test Address')");
    
    $pdo->exec("INSERT INTO penjab (kd_pj, png_jawab) VALUES ('BPJ', 'BPJS')");
    $pdo->exec("INSERT INTO reg_periksa (no_rawat, no_rkm_medis, kd_pj, tgl_registrasi) VALUES ('TEST001', 'RM001', 'BPJ', NOW())");
    $pdo->exec("INSERT INTO antrol_bpjs (no_rawat, jam_periksa_perawat, jam_periksa_dokter) VALUES ('TEST001', '00:00:00', '00:00:00')");
    
    echo "✓ Data ready\n\n";
    
    // Test 2: Check jam sebelum
    echo "=== TEST 2: Check initial state ===\n";
    $stmt = $pdo->query("SELECT jam_periksa_perawat, jam_periksa_dokter FROM antrol_bpjs WHERE no_rawat = 'TEST001'");
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    echo "Jam perawat: " . $row->jam_periksa_perawat . "\n";
    echo "Jam dokter: " . $row->jam_periksa_dokter . "\n\n";
    
    // Test 3: Manual update jam perawat
    echo "=== TEST 3: Manual update jam_periksa_perawat ===\n";
    $pdo->exec("UPDATE antrol_bpjs SET jam_periksa_perawat = '10:00:00' WHERE no_rawat = 'TEST001' AND jam_periksa_perawat = '00:00:00'");
    $stmt = $pdo->query("SELECT jam_periksa_perawat FROM antrol_bpjs WHERE no_rawat = 'TEST001'");
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    echo "Jam perawat after update: " . $row->jam_periksa_perawat . "\n";
    echo ($row->jam_periksa_perawat === '10:00:00' ? "✓ PASS\n" : "✗ FAIL\n") . "\n";
    
    // Test 4: Try update again (should skip)
    echo "=== TEST 4: Try update jam perawat again (should skip) ===\n";
    $pdo->exec("UPDATE antrol_bpjs SET jam_periksa_perawat = '10:00:30' WHERE no_rawat = 'TEST001' AND jam_periksa_perawat = '00:00:00'");
    $stmt = $pdo->query("SELECT jam_periksa_perawat FROM antrol_bpjs WHERE no_rawat = 'TEST001'");
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    echo "Jam perawat after 2nd update attempt: " . $row->jam_periksa_perawat . "\n";
    echo ($row->jam_periksa_perawat === '10:00:00' ? "✓ PASS (tidak berubah)\n" : "✗ FAIL\n") . "\n";
    
    // Test 5: CEPAT logic (submit < 61 sec dari jam perawat)
    echo "=== TEST 5: CEPAT logic - force to minimal ===\n";
    $jam_perawat = '10:00:00';
    $jam_submit = '10:00:05'; // 5 detik kemudian
    
    $unix_perawat = strtotime($jam_perawat);
    $unix_submit = strtotime($jam_submit);
    $batas_minimal = $unix_perawat + 61;
    
    if ($unix_submit < $batas_minimal) {
        $jam_final = date('H:i:s', $batas_minimal); // 10:01:01
    } else {
        $jam_final = $jam_submit;
    }
    
    echo "Jam perawat: $jam_perawat\n";
    echo "Jam submit: $jam_submit\n";
    echo "Jam final (CEPAT): $jam_final\n";
    echo "Expected: 10:01:01\n";
    echo ($jam_final === '10:01:01' ? "✓ PASS\n" : "✗ FAIL\n") . "\n";
    
    // Test 6: LAMA logic (submit >= 61 sec dari jam perawat)
    echo "=== TEST 6: LAMA logic - use realtime ===\n";
    $jam_perawat = '10:00:00';
    $jam_submit = '10:04:00'; // 4 menit kemudian
    
    $unix_perawat = strtotime($jam_perawat);
    $unix_submit = strtotime($jam_submit);
    $batas_minimal = $unix_perawat + 61;
    
    if ($unix_submit < $batas_minimal) {
        $jam_final = date('H:i:s', $batas_minimal);
    } else {
        $jam_final = $jam_submit;
    }
    
    echo "Jam perawat: $jam_perawat\n";
    echo "Jam submit: $jam_submit\n";
    echo "Jam final (LAMA): $jam_final\n";
    echo "Expected: 10:04:00\n";
    echo ($jam_final === '10:04:00' ? "✓ PASS\n" : "✗ FAIL\n") . "\n";
    
    // Test 7: Multiple tabs scenario
    echo "=== TEST 7: Multiple tabs protection ===\n";
    
    // Reset data
    $pdo->exec("UPDATE antrol_bpjs SET jam_periksa_perawat = '00:00:00' WHERE no_rawat = 'TEST001'");
    
    // Tab 1 set jam
    $pdo->exec("UPDATE antrol_bpjs SET jam_periksa_perawat = '10:00:00' WHERE no_rawat = 'TEST001' AND (jam_periksa_perawat = '00:00:00' OR jam_periksa_perawat IS NULL)");
    
    $stmt = $pdo->query("SELECT jam_periksa_perawat FROM antrol_bpjs WHERE no_rawat = 'TEST001'");
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    $tab1_result = $row->jam_periksa_perawat;
    echo "Tab 1 set jam: $tab1_result\n";
    
    // Tab 2 try set jam (should skip)
    $pdo->exec("UPDATE antrol_bpjs SET jam_periksa_perawat = '10:00:30' WHERE no_rawat = 'TEST001' AND (jam_periksa_perawat = '00:00:00' OR jam_periksa_perawat IS NULL)");
    
    $stmt = $pdo->query("SELECT jam_periksa_perawat FROM antrol_bpjs WHERE no_rawat = 'TEST001'");
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    $tab2_result = $row->jam_periksa_perawat;
    echo "Tab 2 try update: $tab2_result (should still 10:00:00)\n";
    echo (($tab1_result === '10:00:00' && $tab2_result === '10:00:00') ? "✓ PASS\n" : "✗ FAIL\n") . "\n";
    
    echo "\n=== ✓ ALL TESTS DONE ===\n";
    
} catch (PDOException $e) {
    echo "DATABASE ERROR: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
