-- Dijalankan manual oleh administrator DB di prod (192.168.10.20).
CREATE TABLE IF NOT EXISTS rme_resume_ranap (
  no_rawat      VARCHAR(17) NOT NULL,
  status        ENUM('draf','selesai') NOT NULL DEFAULT 'draf',
  diubah_oleh   VARCHAR(20) NOT NULL,
  diubah_pada   DATETIME    NOT NULL,
  selesai_oleh  VARCHAR(20) NULL,
  selesai_pada  DATETIME    NULL,
  PRIMARY KEY (no_rawat),
  CONSTRAINT fk_rme_resume_ranap_reg FOREIGN KEY (no_rawat)
    REFERENCES reg_periksa (no_rawat) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
