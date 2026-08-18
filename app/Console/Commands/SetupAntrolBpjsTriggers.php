<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupAntrolBpjsTriggers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'antrol:setup-triggers';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Setup MySQL triggers untuk antrol_bpjs table (taskid_4 & taskid_5)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Setting up antrol_bpjs triggers...');

        try {
            // Drop existing triggers
            DB::statement("DROP TRIGGER IF EXISTS trg_antrol_bpjs_before_insert_task4;");
            DB::statement("DROP TRIGGER IF EXISTS trg_antrol_bpjs_before_update_task4;");
            DB::statement("DROP TRIGGER IF EXISTS trg_antrol_bpjs_before_update_task5;");
            $this->info('✓ Cleaned up old triggers');

            // Create TRIGGER 1: INSERT
            DB::statement("
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
            ");
            $this->info('✓ Created trigger: trg_antrol_bpjs_before_insert_task4');

            // Create TRIGGER 2: UPDATE jam_periksa_perawat
            DB::statement("
                CREATE TRIGGER trg_antrol_bpjs_before_update_task4
                BEFORE UPDATE ON antrol_bpjs
                FOR EACH ROW
                BEGIN
                    IF NEW.jam_periksa_perawat != OLD.jam_periksa_perawat 
                       AND NEW.jam_periksa_perawat != '00:00:00' 
                    THEN
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
            ");
            $this->info('✓ Created trigger: trg_antrol_bpjs_before_update_task4');

            // Create TRIGGER 3: UPDATE jam_periksa_dokter
            DB::statement("
                CREATE TRIGGER trg_antrol_bpjs_before_update_task5
                BEFORE UPDATE ON antrol_bpjs
                FOR EACH ROW
                BEGIN
                    IF NEW.jam_periksa_dokter != OLD.jam_periksa_dokter 
                       AND NEW.jam_periksa_dokter != '00:00:00' 
                    THEN
                        SET NEW.taskid_5 = CONCAT(
                            'TASK-',
                            DATE_FORMAT(NOW(), '%Y%m%d'),
                            '-DOKTER-',
                            NEW.no_rawat,
                            '-',
                            UNIX_TIMESTAMP(NOW()),
                            '-',
                            LPAD(FLOOR(RAND() * 10000), 4, '0')
                        );
                    END IF;
                END;
            ");
            $this->info('✓ Created trigger: trg_antrol_bpjs_before_update_task5');

            // Verify triggers
            $triggers = DB::select("SHOW TRIGGERS WHERE `Table` = 'antrol_bpjs'");
            
            $this->info('');
            $this->info('✅ All triggers setup successfully!');
            $this->info('');
            $this->table(['Trigger Name', 'Event', 'Timing'], 
                array_map(function($t) {
                    return [$t->Trigger, $t->Event, $t->Timing];
                }, $triggers)
            );

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
