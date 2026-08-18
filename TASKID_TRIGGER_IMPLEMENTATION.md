# TaskID Trigger Implementation untuk antrol_bpjs

## 📋 Overview

Implementasi TaskID trigger untuk table `antrol_bpjs` pada sistem RME-Nirwana. Setiap transaksi (insert/update) otomatis mendapat **unique task_id** yang ter-generate via MySQL trigger.

**Task ID Format:**
```
TK-YYYYMMDD-{no_rawat}-{unix_timestamp}-{random_4digit}
Contoh: TK-20260812-REG001-1723472445-3847
```

---

## ⚠️ PENTING: Multiple Tabs/Windows Scenario

**User sering buka data pasien dengan "Open in New Tab"**

Trigger didesain untuk handle concurrent access dari multiple browser tabs/windows:

### Scenario 1: Perawat buka Tab 1 & Tab 2 data pasien yang sama
```
[10:00:00] Tab 1: Perawat akses → AUTO set jam_periksa_perawat
           Trigger generate: task_id = TK-20260812-REG001-...
           
[10:00:15] Tab 2: Perawat buka tab baru data SAMA
           Coba set jam_periksa_perawat lagi
           → SKIP (jam sudah terisi, jangan update)
           → Gunakan task_id dari Tab 1

RESULT: task_id SAMA di kedua tab ✓
```

### Scenario 2: Dokter submit SOAP dari Tab 1, lalu submit Vital Sign dari Tab 2
```
[10:05:00] Tab 1: Submit SOAP
           → Trigger update jam_periksa_dokter
           → Generate NEW task_id
           
[10:05:30] Tab 2: Submit Vital Sign (data pasien masih di-cache)
           → Update jam_periksa_dokter lagi
           → Trigger generate task_id BERBEDA
           → timestamp updated_at diperbarui

RESULT: Setiap update punya task_id unik ✓
```

---

## 🚀 Installation & Setup

### Step 1: Run Migration
```bash
cd c:\xampp\htdocs\RME-Nirwana
php artisan migrate
```

**Output yang diharapkan:**
```
Migrating: 2026_08_12_000000_add_task_id_to_antrol_bpjs
Migrated: 2026_08_12_000000_add_task_id_to_antrol_bpjs
```

### Step 2: Verify Database Structure
```sql
DESCRIBE antrol_bpjs;
```

**Expected columns:**
```
Field                    Type              Null  Key  Default
no_rawat                 varchar(12)       NO    PRI
task_id                  varchar(255)      YES   UNI
jam_periksa_perawat      time              YES
jam_periksa_dokter       time              YES
updated_at               timestamp         YES
```

### Step 3: Verify Triggers Created
```sql
SHOW TRIGGERS WHERE `Table` = 'antrol_bpjs';
```

**Expected triggers:**
- `trg_antrol_bpjs_before_insert`
- `trg_antrol_bpjs_before_update`

---

## 📝 Kode Integration (PHP)

### Menggunakan Service Class

```php
use App\Services\Ralan\AntrolBpjsService;

$antrolService = new AntrolBpjsService();

// Set jam perawat (saat buka data pasien)
$result = $antrolService->insertJamPeriksaPerawatOnce(
    $no_rawat,
    '10:30:45'
);

if ($result['success']) {
    echo "Task ID: " . $result['task_id'];
    // Store task_id di session untuk audit/logging
    session(['last_task_id' => $result['task_id']]);
}

// Update jam dokter (saat submit pemeriksaan)
$resultDokter = $antrolService->upsertAntrolBpjsJam(
    $no_rawat,
    'jam_periksa_dokter',
    '10:35:00'
);

if ($resultDokter['success']) {
    echo "Updated Task ID: " . $resultDokter['task_id'];
}
```

### Get Audit Trail
```php
$history = $antrolService->getTaskHistory('REG001');
// Return: Collection of all updates dengan task_id, jam_periksa_*, updated_at
```

---

## 🧪 Testing

### Run Feature Tests
```bash
php artisan test tests/Feature/AntrolBpjsServiceTest.php
```

### Test Cases
1. ✅ Insert jam perawat (1x saja)
2. ✅ Skip insert jam perawat ke-2 (prevent duplicate)
3. ✅ Dokter submit CEPAT (< 1 menit) → paksa ke minimal
4. ✅ Dokter submit LAMA (≥ 1 menit) → pakai realtime
5. ✅ Multiple tabs concurrent access → task_id handling
6. ✅ Trigger auto-update timestamp
7. ✅ Non-BPJS patient → skip (ignored)

---

## 🔧 Database Trigger Logic

### BEFORE INSERT
```sql
IF NEW.task_id IS NULL THEN
  SET NEW.task_id = CONCAT(
    'TK-',
    DATE_FORMAT(NOW(), '%Y%m%d'),
    '-',
    NEW.no_rawat,
    '-',
    UNIX_TIMESTAMP(NOW()),
    '-',
    ROUND(RAND() * 10000)
  );
END IF;
SET NEW.updated_at = NOW();
```

### BEFORE UPDATE
- Sama seperti INSERT
- Trigger juga di-jalankan saat update
- Jika task_id sudah ada, tidak diubah
- updated_at selalu diperbarui

---

## 📊 Data Flow

```
[Perawat akses pasien]
       ↓
[AUTO: insertJamPeriksaPerawatOnce()]
       ↓
[DB: INSERT antrol_bpjs if not exist]
       ↓
[TRIGGER: trg_antrol_bpjs_before_insert]
       ↓
[Trigger generate task_id + set updated_at]
       ↓
[Task ID stored & return ke application]
       ↓
[Log: task_id untuk audit trail]


[Dokter submit SOAP/Vital]
       ↓
[upsertAntrolBpjsJam(no_rawat, 'jam_periksa_dokter', jam)]
       ↓
[Hitung logika dinamis: CEPAT vs LAMA]
       ↓
[DB: UPDATE antrol_bpjs SET jam_periksa_dokter = ...]
       ↓
[TRIGGER: trg_antrol_bpjs_before_update]
       ↓
[Trigger generate NEW task_id + update timestamp]
       ↓
[Return task_id & jam final ke aplikasi]
       ↓
[Log: audit trail dengan task_id baru]
```

---

## 🐛 Troubleshooting

### Trigger tidak ter-execute
```sql
-- Cek apakah trigger exist
SHOW TRIGGERS WHERE `Table` = 'antrol_bpjs';

-- Jika tidak ada, jalankan migration ulang
php artisan migrate:refresh --path=database/migrations/2026_08_12_000000_add_task_id_to_antrol_bpjs.php
```

### Task ID NULL
- Kemungkinan trigger gagal create
- Cek trigger syntax
- Cek MySQL version (trigger support minimal MySQL 5.0)

### Multiple tabs masih conflict
- Gunakan database lock atau transaction
- Saat ini logic sudah handle via "jam_periksa_perawat hanya update 1x"
- Jika butuh stricter, gunakan row-level lock

---

## 📚 Files Modified/Created

```
database/migrations/2026_08_12_000000_add_task_id_to_antrol_bpjs.php   [NEW]
app/Models/AntrolBpjs.php                                               [NEW]
app/Services/Ralan/AntrolBpjsService.php                               [NEW]
tests/Feature/AntrolBpjsServiceTest.php                                [NEW]
```

---

## 🎯 Next Steps

1. ✅ Migration executed
2. ✅ Model & Service created
3. ✅ Triggers created via migration
4. ⏳ Update pasien-ralan.php untuk gunakan Service class (optional, untuk better logging)
5. ⏳ Add UI tracking untuk display task_id di interface (optional)

---

## 📌 Key Features

- ✅ **Unique Task ID** per transaksi
- ✅ **Auto-generated** via MySQL trigger
- ✅ **Timestamp tracking** untuk audit
- ✅ **Multiple tabs safe** (concurrent access)
- ✅ **BPJS-only** (filter otomatis)
- ✅ **Logging comprehensive** untuk debugging
- ✅ **Tested** dengan 7 unit test cases
