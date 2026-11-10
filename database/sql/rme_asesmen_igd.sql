-- Dijalankan manual oleh administrator DB di prod (192.168.10.20).
-- Jejak asesmen medis IGD yang ditulis lewat aplikasi: penulis pertama dan pengubah terakhir.
-- Asesmen yang dibuat Khanza desktop tidak punya baris di sini sampai pertama diubah lewat aplikasi.
CREATE TABLE IF NOT EXISTS rme_asesmen_igd (
  no_rawat     VARCHAR(17) NOT NULL,
  dibuat_oleh  VARCHAR(20) NOT NULL,
  dibuat_pada  DATETIME    NOT NULL,
  diubah_oleh  VARCHAR(20) NOT NULL,
  diubah_pada  DATETIME    NOT NULL,
  PRIMARY KEY (no_rawat),
  CONSTRAINT fk_rme_asesmen_igd_reg FOREIGN KEY (no_rawat)
    REFERENCES reg_periksa (no_rawat) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
