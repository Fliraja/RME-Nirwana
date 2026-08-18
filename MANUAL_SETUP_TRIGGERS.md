# ⚡ Manual Setup Instructions - TaskID Triggers

Terminal sedang bermasalah. Berikut cara setup manual:

---

## Option 1: Via phpMyAdmin (Recommended untuk saat ini)

1. Buka: http://localhost/phpmyadmin/
2. Login dengan user `root`, password kosong
3. Pilih database `db_test_rev`
4. Klik tab "SQL"
5. Copy-paste isi file ini ke query window: `database/sql/setup_antrol_bpjs_triggers.sql`
6. Klik "Go"
7. Verify: Klik tab "Triggers", harusnya ada 3 triggers

---

## Option 2: Via Command Prompt / PowerShell (Direct)

```batch
cd c:\xampp\htdocs\RME-Nirwana

REM Method A: Direct MySQL command
C:\xampp\mysql\bin\mysql -h localhost -u root db_test_rev < database\sql\setup_antrol_bpjs_triggers.sql

REM Method B: Interactive MySQL  
C:\xampp\mysql\bin\mysql -h localhost -u root db_test_rev

REM Then paste triggers one by one
```

---

## Option 3: Via Laravel Command (Ketika Terminal Stabil)

```bash
cd c:\xampp\htdocs\RME-Nirwana
php artisan antrol:setup-triggers
```

---

## ✅ Verification (After Setup)

```bash
# Check triggers exist
C:\xampp\mysql\bin\mysql -h localhost -u root db_test_rev -e "SHOW TRIGGERS WHERE \`Table\`='antrol_bpjs';"
```

**Expected Output:**
```
+----------------------------------+--------+-------+---+---+---+-+---+-+-+---+---+---+-+-+---+-----+
| Trigger                          | Event  | Table | Statement
|--
| trg_antrol_bpjs_before_insert_task4   | INSERT | antrol_bpjs
|--
| trg_antrol_bpjs_before_update_task4   | UPDATE | antrol_bpjs
|--
| trg_antrol_bpjs_before_update_task5   | UPDATE | antrol_bpjs
|--
```

---

## 🧪 Run Tests (After Triggers Created)

```bash
cd c:\xampp\htdocs\RME-Nirwana
php artisan test tests/Feature/AntrolBpjsServiceTest.php
```

Expected: ✅ All tests pass

---

## 📋 SQL Script Content

File `database/sql/setup_antrol_bpjs_triggers.sql` contains:

```sql
-- DROP TRIGGERS
DROP TRIGGER IF EXISTS trg_antrol_bpjs_before_insert_task4;
DROP TRIGGER IF EXISTS trg_antrol_bpjs_before_update_task4;
DROP TRIGGER IF EXISTS trg_antrol_bpjs_before_update_task5;

-- TRIGGER 1: INSERT
CREATE TRIGGER trg_antrol_bpjs_before_insert_task4
BEFORE INSERT ON antrol_bpjs
FOR EACH ROW
BEGIN
    IF NEW.taskid_4 IS NULL OR NEW.taskid_4 = '' THEN
        SET NEW.taskid_4 = CONCAT(
            'TASK-',
            DATE_FORMAT(NOW(), '%Y%m%d'),
            '-PERAWAT-',
            NEW.no_rawat,
            '-',
            UNIX_TIMESTAMP(NOW()),
            '-',
            LPAD(FLOOR(RAND() * 10000), 4, '0')
        );
    END IF;
END;

-- TRIGGER 2: UPDATE jam_periksa_perawat
CREATE TRIGGER trg_antrol_bpjs_before_update_task4
BEFORE UPDATE ON antrol_bpjs
FOR EACH ROW
BEGIN
    IF NEW.jam_periksa_perawat != OLD.jam_periksa_perawat 
       AND NEW.jam_periksa_perawat != '00:00:00' 
    THEN
        SET NEW.taskid_4 = CONCAT(...);
    END IF;
END;

-- TRIGGER 3: UPDATE jam_periksa_dokter
CREATE TRIGGER trg_antrol_bpjs_before_update_task5
BEFORE UPDATE ON antrol_bpjs
FOR EACH ROW
BEGIN
    IF NEW.jam_periksa_dokter != OLD.jam_periksa_dokter 
       AND NEW.jam_periksa_dokter != '00:00:00' 
    THEN
        SET NEW.taskid_5 = CONCAT(...);
    END IF;
END;
```

---

## ⏳ Setelah Setup Selesai

1. Triggers sudah exist di database ✓
2. Service class siap pakai ✓
3. Tests siap di-run ✓
4. Documentation lengkap ✓

Lanjutkan ke step testing!
