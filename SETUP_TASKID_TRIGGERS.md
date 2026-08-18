# TASKID Trigger Setup - Quick Reference

## Status Persiapan ✅ 

Files created/updated:
- ✅ `app/Services/Ralan/AntrolBpjsService.php` - Service untuk manage antrol_bpjs (use taskid_4, taskid_5)
- ✅ `app/Models/AntrolBpjs.php` - Model updated
- ✅ `app/Console/Commands/SetupAntrolBpjsTriggers.php` - Command untuk setup triggers
- ✅ `database/sql/setup_antrol_bpjs_triggers.sql` - SQL script triggers
- ✅ `tests/Feature/AntrolBpjsServiceTest.php` - Unit test service
- ✅ `tests/Feature/AntrolBpjsTriggersTest.php` - Trigger-specific tests

## Column Mapping
- **taskid_4** → Track `jam_periksa_perawat` updates (Nurse/Perawat action)
- **taskid_5** → Track `jam_periksa_dokter` updates (Doctor/Dokter action)

## Trigger Format
```
TASK-{YYYYMMDD}-{ROLE}-{no_rawat}-{unix_timestamp}-{random_4digit}

Contoh:
- taskid_4: TASK-20260812-PERAWAT-REG001-1723472445-3847
- taskid_5: TASK-20260812-DOKTER-REG001-1723472450-2156
```

---

## ⚠️ Multiple Tabs Scenario (PENTING!)

**Problem:** Perawat/Dokter sering buka data pasien di multiple tabs

**Solution:** 
1. **jam_periksa_perawat**: Set HANYA 1x (logic di service, skip jika sudah ada)
   - Tab 1 set jam → taskid_4 generate
   - Tab 2 coba set jam → SKIP (jam sudah terisi)
   - Result: taskid_4 SAMA untuk semua tabs ✓

2. **jam_periksa_dokter**: Bisa update berkali-kali (logika CEPAT vs LAMA)
   - Tab 1 submit SOAP → taskid_5 generate (timestamp_1)
   - Tab 2 submit Vital → taskid_5 BARU (timestamp_2, timestamp berbeda)
   - Result: Setiap update punya taskid_5 unik ✓

---

## 🚀 NEXT STEPS - Execution Plan

### Step 1: Setup Triggers (5 menit)
```bash
cd c:\xampp\htdocs\RME-Nirwana
php artisan antrol:setup-triggers
```

Expected output:
```
🔧 Setting up antrol_bpjs triggers...
✓ Cleaned up old triggers
✓ Created trigger: trg_antrol_bpjs_before_insert_task4
✓ Created trigger: trg_antrol_bpjs_before_update_task4
✓ Created trigger: trg_antrol_bpjs_before_update_task5

✅ All triggers setup successfully!

| Trigger Name                          | Event  | Timing |
|---------------------------------------|--------|--------|
| trg_antrol_bpjs_before_insert_task4   | INSERT | BEFORE |
| trg_antrol_bpjs_before_update_task4   | UPDATE | BEFORE |
| trg_antrol_bpjs_before_update_task5   | UPDATE | BEFORE |
```

### Step 2: Verify Triggers Created (2 menit)
```bash
mysql -h localhost -u root db_test_rev -e "SHOW TRIGGERS WHERE \`Table\` = 'antrol_bpjs';"
```

Expected: 3 triggers listed

### Step 3: Run Tests (5 menit)
```bash
# Run all tests
php artisan test

# Or specific test suites
php artisan test tests/Feature/AntrolBpjsServiceTest.php
php artisan test tests/Feature/AntrolBpjsTriggersTest.php
```

Expected: ✅ All tests pass

### Step 4: Manual Verification (5 menit)
```bash
# Test via tinker
php artisan tinker
```

```php
// Test case 1: Perawat set jam
$service = new \App\Services\Ralan\AntrolBpjsService();
$result = $service->insertJamPeriksaPerawatOnce('TEST001', '10:30:00');
echo $result['success'] ? "✓ Jam set, taskid_4: " . $result['taskid_4'] : "✗ Gagal";

// Test case 2: Dokter submit (fast)
$result2 = $service->upsertAntrolBpjsJam('TEST001', 'jam_periksa_dokter', '10:30:05');
echo $result2['success'] ? "✓ Jam dokter update, taskid_5: " . $result2['taskid_5'] . ", Jam final: " . $result2['jam'] : "✗ Gagal";

// Check database
\DB::table('antrol_bpjs')->where('no_rawat', 'TEST001')->first();
```

---

## 📋 Checklist

- [ ] Run: `php artisan antrol:setup-triggers`
- [ ] Verify: `SHOW TRIGGERS WHERE Table='antrol_bpjs'`
- [ ] Run tests: `php artisan test`
- [ ] Manual verify via tinker
- [ ] Check logs: `tail -f storage/logs/laravel.log`

---

## 🔍 Troubleshooting

### Command not found
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
```

### Triggers failed to create
```bash
# Check MySQL error
mysql -h localhost -u root db_test_rev -e "SELECT * FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_SCHEMA='db_test_rev';"
```

### Tests fail
```bash
# Run with verbose
php artisan test --verbose
```

---

## 📚 Service Methods Reference

```php
use App\Services\Ralan\AntrolBpjsService;
$service = new AntrolBpjsService();

// 1. Set jam perawat (hanya 1x)
$result = $service->insertJamPeriksaPerawatOnce($no_rawat, $jam_perawat);
// Returns: ['success' => bool, 'taskid_4' => string, 'message' => string]

// 2. Update jam dokter (logika CEPAT/LAMA)
$result = $service->upsertAntrolBpjsJam($no_rawat, 'jam_periksa_dokter', $jam_submit);
// Returns: ['success' => bool, 'taskid_5' => string, 'jam' => string, 'message' => string]

// 3. Get audit trail
$history = $service->getTaskHistory($no_rawat);
// Returns: Collection with taskid_4, taskid_5, jam timestamps

// 4. Get current data
$antrol = $service->getAntrolData($no_rawat);
// Returns: stdClass with all columns
```

---

## ✅ Selesai!

Setelah semua langkah di atas selesai:
1. TaskID tracking fully operational ✓
2. Multiple tabs handling safe ✓
3. Audit trail complete ✓
4. All tests passing ✓
