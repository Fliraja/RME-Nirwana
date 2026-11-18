-- Dijalankan manual oleh administrator DB di prod (192.168.10.20).
-- Isian formulir Rehab Medik (KFR) yang tidak punya kolom di tabel Khanza
-- layanan_kedokteran_fisik_rehabilitasi / uji_fungsi_kfr.
CREATE TABLE IF NOT EXISTS rme_kfr (
  no_rawat      VARCHAR(17)  NOT NULL,
  penunjang     VARCHAR(500) NOT NULL DEFAULT '',
  target        VARCHAR(300) NOT NULL DEFAULT '',
  edukasi       VARCHAR(300) NOT NULL DEFAULT '',
  disimpan_oleh VARCHAR(20)  NOT NULL,
  disimpan_pada DATETIME     NOT NULL,
  PRIMARY KEY (no_rawat),
  CONSTRAINT fk_rme_kfr_reg FOREIGN KEY (no_rawat)
    REFERENCES reg_periksa (no_rawat) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
