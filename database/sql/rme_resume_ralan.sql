-- Dijalankan manual oleh administrator DB di prod (192.168.10.20).
CREATE TABLE IF NOT EXISTS rme_resume_ralan (
  no_rawat          VARCHAR(17)  NOT NULL,
  status            ENUM('otomatis','diverifikasi') NOT NULL DEFAULT 'otomatis',
  tindak_lanjut     VARCHAR(20)  NOT NULL DEFAULT 'pulang',
  tgl_kontrol       DATE         NULL,
  ket_tindak_lanjut VARCHAR(100) NULL,
  diverifikasi_oleh VARCHAR(20)  NULL,
  diverifikasi_pada DATETIME     NULL,
  dibuat_pada       DATETIME     NOT NULL,
  sidik_sumber      CHAR(40)     NOT NULL,
  PRIMARY KEY (no_rawat),
  CONSTRAINT fk_rme_resume_ralan_reg FOREIGN KEY (no_rawat)
    REFERENCES reg_periksa (no_rawat) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
