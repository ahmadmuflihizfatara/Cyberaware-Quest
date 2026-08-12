-- =============================================================================
-- CYBERAWARE QUEST x PKM IMPACTLAB — DDL TERINTEGRASI (REVISI v2)
-- PostgreSQL 16
--
-- v2 menambah keselarasan literal dengan "kandidat objek data" pada dokumen
-- Soal Proyek UAS Terpadu PkM ImpactLab yang belum ada di revisi v1:
--   K1: + lokasi (venue di dalam sekolah, terpisah dari sekolah sendiri)
--   K2: + afiliasi (dipindah dari kolom peserta.id_sekolah; kini dicatat per
--        PENDAFTARAN sesuai aturan bisnis wajib #2: "dapat berbeda antarprogram")
--   K4: aktivitas_gamifikasi & partisipasi_gamifikasi dipisah dari
--        aktivitas_pembelajaran & partisipasi_aktivitas (dahulu digabung satu
--        tabel) — mengikuti daftar kandidat objek K4 yang eksplisit memisah
--        keduanya, dan memperkuat aturan bisnis wajib #8 (poin HANYA dari
--        aktivitas gamifikasi, bukan tugas artefak/materi biasa).
--   K3: + view v_leaderboard untuk fitur pengayaan "Leaderboard" (§7.1 K3).
--
-- Total: 42 tabel + 5 view. Diuji dari skema kosong s.d. COMMIT di PostgreSQL 16.
-- =============================================================================

DROP SCHEMA IF EXISTS cyberaware CASCADE;
CREATE SCHEMA cyberaware;
SET search_path TO cyberaware;

-- =============================================================================
-- KELOMPOK 1 — PROGRAM, KEGIATAN, DAN PENGGUNA
-- =============================================================================

CREATE TABLE pengguna (
    id_pengguna     INTEGER GENERATED ALWAYS AS IDENTITY,
    nama_pengguna   VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    kata_sandi_hash VARCHAR(255) NOT NULL,
    status_akun     VARCHAR(20)  NOT NULL DEFAULT 'aktif',
    dibuat_pada     TIMESTAMP    NOT NULL DEFAULT now(),
    CONSTRAINT pk_pengguna PRIMARY KEY (id_pengguna),
    CONSTRAINT uq_pengguna_email UNIQUE (email),
    CONSTRAINT ck_pengguna_status CHECK (status_akun IN ('aktif','nonaktif'))
);

CREATE TABLE peran (
    id_peran     INTEGER GENERATED ALWAYS AS IDENTITY,
    kode_peran   VARCHAR(30)  NOT NULL,
    nama_peran   VARCHAR(50)  NOT NULL,
    CONSTRAINT pk_peran PRIMARY KEY (id_peran),
    CONSTRAINT uq_peran_kode UNIQUE (kode_peran),
    CONSTRAINT ck_peran_kode CHECK (kode_peran IN ('admin','penyelenggara','fasilitator','peserta'))
);

CREATE TABLE pengguna_peran (
    id_pengguna INTEGER NOT NULL,
    id_peran    INTEGER NOT NULL,
    diberikan_pada TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT pk_pengguna_peran PRIMARY KEY (id_pengguna, id_peran),
    CONSTRAINT fk_pp_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pp_peran FOREIGN KEY (id_peran) REFERENCES peran(id_peran)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE mitra (
    id_mitra      INTEGER GENERATED ALWAYS AS IDENTITY,
    nama_mitra    VARCHAR(150) NOT NULL,
    jenis_mitra   VARCHAR(20)  NOT NULL,
    kontak_email  VARCHAR(150),
    kontak_telepon VARCHAR(30),
    alamat        VARCHAR(255),
    status_mitra  VARCHAR(20)  NOT NULL DEFAULT 'aktif',
    CONSTRAINT pk_mitra PRIMARY KEY (id_mitra),
    CONSTRAINT ck_mitra_jenis CHECK (jenis_mitra IN ('sekolah','instansi','komunitas','lainnya')),
    CONSTRAINT ck_mitra_status CHECK (status_mitra IN ('aktif','nonaktif'))
);

CREATE TABLE sekolah (
    id_sekolah  INTEGER GENERATED ALWAYS AS IDENTITY,
    id_mitra    INTEGER NOT NULL,
    npsn        VARCHAR(20),
    jenjang     VARCHAR(20),
    kota        VARCHAR(100),
    CONSTRAINT pk_sekolah PRIMARY KEY (id_sekolah),
    CONSTRAINT uq_sekolah_mitra UNIQUE (id_mitra),
    CONSTRAINT uq_sekolah_npsn UNIQUE (npsn),
    CONSTRAINT fk_sekolah_mitra FOREIGN KEY (id_mitra) REFERENCES mitra(id_mitra)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_sekolah_jenjang CHECK (jenjang IN ('SD','SMP','SMA','SMK','lainnya'))
);

-- BARU (v2): lokasi/ruangan spesifik di dalam satu sekolah. Terpisah dari
-- sekolah karena satu sekolah bisa punya beberapa venue (aula, lab komputer,
-- dsb.) yang masing-masing punya kapasitas berbeda — memenuhi kandidat objek
-- "lokasi" pada §5.1 dokumen Soal UAS.
CREATE TABLE lokasi (
    id_lokasi       INTEGER GENERATED ALWAYS AS IDENTITY,
    id_sekolah      INTEGER NOT NULL,
    nama_lokasi     VARCHAR(150) NOT NULL,
    kapasitas_ruang INTEGER,
    CONSTRAINT pk_lokasi PRIMARY KEY (id_lokasi),
    CONSTRAINT fk_lokasi_sekolah FOREIGN KEY (id_sekolah) REFERENCES sekolah(id_sekolah)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_lokasi_kapasitas CHECK (kapasitas_ruang IS NULL OR kapasitas_ruang > 0)
);

CREATE TABLE program_pkm (
    id_program   INTEGER GENERATED ALWAYS AS IDENTITY,
    nama_program VARCHAR(150) NOT NULL,
    deskripsi    TEXT,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    status_program VARCHAR(20) NOT NULL DEFAULT 'berjalan',
    CONSTRAINT pk_program_pkm PRIMARY KEY (id_program),
    CONSTRAINT ck_program_status CHECK (status_program IN ('berjalan','selesai','dibatalkan')),
    CONSTRAINT ck_program_tanggal CHECK (tanggal_selesai IS NULL OR tanggal_selesai >= tanggal_mulai)
);

CREATE TABLE program_mitra (
    id_program INTEGER NOT NULL,
    id_mitra   INTEGER NOT NULL,
    peran_mitra VARCHAR(50),
    CONSTRAINT pk_program_mitra PRIMARY KEY (id_program, id_mitra),
    CONSTRAINT fk_pm_program FOREIGN KEY (id_program) REFERENCES program_pkm(id_program)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pm_mitra FOREIGN KEY (id_mitra) REFERENCES mitra(id_mitra)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE fasilitator (
    id_fasilitator  INTEGER GENERATED ALWAYS AS IDENTITY,
    id_pengguna     INTEGER,
    nama_fasilitator VARCHAR(150) NOT NULL,
    email           VARCHAR(150),
    bidang_keahlian VARCHAR(150),
    CONSTRAINT pk_fasilitator PRIMARY KEY (id_fasilitator),
    CONSTRAINT uq_fasilitator_pengguna UNIQUE (id_pengguna),
    CONSTRAINT uq_fasilitator_email UNIQUE (email),
    CONSTRAINT fk_fasilitator_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- Satu kegiatan tepat satu program dan tepat satu sekolah (aturan bisnis
-- wajib CyberAware Quest, ditegakkan FK NOT NULL). id_lokasi menambahkan
-- detail venue di dalam sekolah itu; wajib diisi untuk kegiatan luring/hybrid,
-- boleh kosong untuk kegiatan daring (CHECK ck_kegiatan_lokasi_mode).
CREATE TABLE kegiatan (
    id_kegiatan   INTEGER GENERATED ALWAYS AS IDENTITY,
    id_program    INTEGER NOT NULL,
    id_sekolah    INTEGER NOT NULL,
    id_lokasi     INTEGER,
    tema          VARCHAR(150) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE,
    kapasitas     INTEGER NOT NULL,
    mode_pelaksanaan VARCHAR(20) NOT NULL DEFAULT 'luring',
    status_kegiatan VARCHAR(20) NOT NULL DEFAULT 'terjadwal',
    CONSTRAINT pk_kegiatan PRIMARY KEY (id_kegiatan),
    CONSTRAINT fk_kegiatan_program FOREIGN KEY (id_program) REFERENCES program_pkm(id_program)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_kegiatan_sekolah FOREIGN KEY (id_sekolah) REFERENCES sekolah(id_sekolah)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_kegiatan_lokasi FOREIGN KEY (id_lokasi) REFERENCES lokasi(id_lokasi)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT ck_kegiatan_kapasitas CHECK (kapasitas > 0),
    CONSTRAINT ck_kegiatan_mode CHECK (mode_pelaksanaan IN ('luring','daring','hybrid')),
    CONSTRAINT ck_kegiatan_status CHECK (status_kegiatan IN ('terjadwal','berlangsung','selesai','dibatalkan')),
    CONSTRAINT ck_kegiatan_tanggal CHECK (tanggal_selesai IS NULL OR tanggal_selesai >= tanggal_mulai),
    CONSTRAINT ck_kegiatan_lokasi_mode CHECK (mode_pelaksanaan = 'daring' OR id_lokasi IS NOT NULL)
);

CREATE TABLE penugasan_fasilitator (
    id_kegiatan    INTEGER NOT NULL,
    id_fasilitator INTEGER NOT NULL,
    peran_penugasan VARCHAR(50) NOT NULL DEFAULT 'pemateri',
    CONSTRAINT pk_penugasan_fasilitator PRIMARY KEY (id_kegiatan, id_fasilitator),
    CONSTRAINT fk_pf_kegiatan FOREIGN KEY (id_kegiatan) REFERENCES kegiatan(id_kegiatan)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pf_fasilitator FOREIGN KEY (id_fasilitator) REFERENCES fasilitator(id_fasilitator)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- =============================================================================
-- KELOMPOK 4 (bagian sesi & materi) — dibuat lebih awal karena K2 dan K3
-- membutuhkan id_sesi.
-- =============================================================================

CREATE TABLE materi (
    id_materi    INTEGER GENERATED ALWAYS AS IDENTITY,
    judul_materi VARCHAR(150) NOT NULL,
    kategori     VARCHAR(30) NOT NULL,
    deskripsi    TEXT,
    CONSTRAINT pk_materi PRIMARY KEY (id_materi),
    CONSTRAINT ck_materi_kategori CHECK (kategori IN
        ('phishing','kata_sandi','privasi_digital','keamanan_perangkat','etika_medsos','lainnya'))
);

CREATE TABLE sesi (
    id_sesi        INTEGER GENERATED ALWAYS AS IDENTITY,
    id_kegiatan    INTEGER NOT NULL,
    id_fasilitator INTEGER NOT NULL,
    judul_sesi     VARCHAR(150) NOT NULL,
    tanggal_sesi   DATE NOT NULL,
    jam_mulai      TIME NOT NULL,
    jam_selesai    TIME NOT NULL,
    urutan_sesi    INTEGER NOT NULL,
    CONSTRAINT pk_sesi PRIMARY KEY (id_sesi),
    CONSTRAINT fk_sesi_kegiatan FOREIGN KEY (id_kegiatan) REFERENCES kegiatan(id_kegiatan)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sesi_fasilitator FOREIGN KEY (id_fasilitator) REFERENCES fasilitator(id_fasilitator)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT uq_sesi_urutan UNIQUE (id_kegiatan, urutan_sesi),
    CONSTRAINT ck_sesi_jam CHECK (jam_selesai > jam_mulai)
);

CREATE TABLE sesi_materi (
    id_sesi   INTEGER NOT NULL,
    id_materi INTEGER NOT NULL,
    CONSTRAINT pk_sesi_materi PRIMARY KEY (id_sesi, id_materi),
    CONSTRAINT fk_sm_sesi FOREIGN KEY (id_sesi) REFERENCES sesi(id_sesi)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sm_materi FOREIGN KEY (id_materi) REFERENCES materi(id_materi)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- =============================================================================
-- KELOMPOK 2 — PESERTA, PENDAFTARAN, AFILIASI, DAN KEHADIRAN
-- =============================================================================

-- Peserta kini HANYA identitas dasar. Tidak ada kolom demografi (lewat
-- instrumen K3) maupun kolom sekolah tetap (lewat afiliasi per pendaftaran).
CREATE TABLE peserta (
    id_peserta  INTEGER GENERATED ALWAYS AS IDENTITY,
    nama_peserta VARCHAR(150) NOT NULL,
    email        VARCHAR(150),
    no_hp        VARCHAR(30),
    npm             VARCHAR(50),
    asal_sekolah    VARCHAR(150),
    alamat_domisili TEXT,
    no_ktp          VARCHAR(50),
    foto_profil     VARCHAR(255),
    dibuat_pada  TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT pk_peserta PRIMARY KEY (id_peserta),
    CONSTRAINT uq_peserta_email UNIQUE (email)
);

CREATE TABLE pendaftaran (
    id_pendaftaran  INTEGER GENERATED ALWAYS AS IDENTITY,
    id_peserta      INTEGER NOT NULL,
    id_kegiatan     INTEGER NOT NULL,
    tanggal_daftar  TIMESTAMP NOT NULL DEFAULT now(),
    status_pendaftaran VARCHAR(20) NOT NULL DEFAULT 'terdaftar',
    CONSTRAINT pk_pendaftaran PRIMARY KEY (id_pendaftaran),
    CONSTRAINT uq_pendaftaran_peserta_kegiatan UNIQUE (id_peserta, id_kegiatan),
    CONSTRAINT fk_pendaftaran_peserta FOREIGN KEY (id_peserta) REFERENCES peserta(id_peserta)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pendaftaran_kegiatan FOREIGN KEY (id_kegiatan) REFERENCES kegiatan(id_kegiatan)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_pendaftaran_status CHECK (status_pendaftaran IN
        ('terdaftar','hadir','tidak_hadir','dibatalkan'))
);

-- BARU (v2): afiliasi dipindah ke sini, dicatat PER PENDAFTARAN (bukan
-- atribut tetap peserta) — sesuai aturan bisnis wajib #2: "Data demografi
-- [dan afiliasi] dicatat dalam konteks pendaftaran kegiatan dan dapat
-- berbeda antarprogram." Memenuhi kandidat objek "afiliasi" pada §5.2.
CREATE TABLE afiliasi (
    id_afiliasi    INTEGER GENERATED ALWAYS AS IDENTITY,
    id_pendaftaran INTEGER NOT NULL,
    id_mitra       INTEGER NOT NULL,
    peran_afiliasi VARCHAR(20) NOT NULL DEFAULT 'siswa',
    CONSTRAINT pk_afiliasi PRIMARY KEY (id_afiliasi),
    CONSTRAINT uq_afiliasi_pendaftaran UNIQUE (id_pendaftaran),
    CONSTRAINT fk_afiliasi_pendaftaran FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_afiliasi_mitra FOREIGN KEY (id_mitra) REFERENCES mitra(id_mitra)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_afiliasi_peran CHECK (peran_afiliasi IN ('siswa','guru','staf','umum'))
);

CREATE TABLE persetujuan_peserta (
    id_persetujuan   INTEGER GENERATED ALWAYS AS IDENTITY,
    id_pendaftaran   INTEGER NOT NULL,
    versi_kebijakan  VARCHAR(20) NOT NULL,
    disetujui        BOOLEAN NOT NULL DEFAULT false,
    waktu_persetujuan TIMESTAMP,
    CONSTRAINT pk_persetujuan_peserta PRIMARY KEY (id_persetujuan),
    CONSTRAINT uq_persetujuan_pendaftaran UNIQUE (id_pendaftaran),
    CONSTRAINT fk_persetujuan_pendaftaran FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_persetujuan_waktu CHECK (disetujui = false OR waktu_persetujuan IS NOT NULL)
);

CREATE TABLE token_qr_sesi (
    id_token      INTEGER GENERATED ALWAYS AS IDENTITY,
    id_sesi       INTEGER NOT NULL,
    dibuka_oleh   INTEGER NOT NULL,
    token         VARCHAR(64) NOT NULL,
    berlaku_hingga TIMESTAMP NOT NULL,
    CONSTRAINT pk_token_qr_sesi PRIMARY KEY (id_token),
    CONSTRAINT uq_token_qr UNIQUE (token),
    CONSTRAINT fk_tqs_sesi FOREIGN KEY (id_sesi) REFERENCES sesi(id_sesi)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tqs_pengguna FOREIGN KEY (dibuka_oleh) REFERENCES pengguna(id_pengguna)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE kehadiran (
    id_kehadiran  INTEGER GENERATED ALWAYS AS IDENTITY,
    id_pendaftaran INTEGER NOT NULL,
    id_sesi       INTEGER NOT NULL,
    id_token      INTEGER,
    waktu_hadir   TIMESTAMP NOT NULL DEFAULT now(),
    metode_presensi VARCHAR(10) NOT NULL DEFAULT 'manual',
    CONSTRAINT pk_kehadiran PRIMARY KEY (id_kehadiran),
    CONSTRAINT uq_kehadiran_pendaftaran_sesi UNIQUE (id_pendaftaran, id_sesi),
    CONSTRAINT fk_kehadiran_pendaftaran FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_kehadiran_sesi FOREIGN KEY (id_sesi) REFERENCES sesi(id_sesi)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_kehadiran_token FOREIGN KEY (id_token) REFERENCES token_qr_sesi(id_token)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT ck_kehadiran_metode CHECK (metode_presensi IN ('manual','qr'))
);

-- =============================================================================
-- KELOMPOK 3 — MESIN INSTRUMEN GENERIK, PRE-TEST/POST-TEST, DAN PENILAIAN
-- (tidak berubah dari revisi v1 — sudah generik dan diapresiasi reviewer)
-- =============================================================================

CREATE TABLE instrumen (
    id_instrumen  INTEGER GENERATED ALWAYS AS IDENTITY,
    kode_instrumen VARCHAR(30) NOT NULL,
    nama_instrumen VARCHAR(150) NOT NULL,
    tipe_instrumen VARCHAR(20) NOT NULL,
    deskripsi     TEXT,
    CONSTRAINT pk_instrumen PRIMARY KEY (id_instrumen),
    CONSTRAINT uq_instrumen_kode UNIQUE (kode_instrumen),
    CONSTRAINT ck_instrumen_tipe CHECK (tipe_instrumen IN ('demografi','tes','kuesioner'))
);

CREATE TABLE versi_instrumen (
    id_versi     INTEGER GENERATED ALWAYS AS IDENTITY,
    id_instrumen INTEGER NOT NULL,
    nomor_versi  INTEGER NOT NULL,
    status_versi VARCHAR(20) NOT NULL DEFAULT 'draft',
    dikunci_pada TIMESTAMP,
    CONSTRAINT pk_versi_instrumen PRIMARY KEY (id_versi),
    CONSTRAINT uq_versi_instrumen UNIQUE (id_instrumen, nomor_versi),
    CONSTRAINT fk_versi_instrumen FOREIGN KEY (id_instrumen) REFERENCES instrumen(id_instrumen)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_versi_status CHECK (status_versi IN ('draft','terkunci','arsip'))
);

CREATE TABLE butir_instrumen (
    id_butir     INTEGER GENERATED ALWAYS AS IDENTITY,
    id_versi     INTEGER NOT NULL,
    nomor_urut   INTEGER NOT NULL,
    teks_butir   TEXT NOT NULL,
    tipe_butir   VARCHAR(20) NOT NULL,
    bobot_skor   NUMERIC(5,2) NOT NULL DEFAULT 0,
    wajib_diisi  BOOLEAN NOT NULL DEFAULT true,
    CONSTRAINT pk_butir_instrumen PRIMARY KEY (id_butir),
    CONSTRAINT uq_butir_urutan UNIQUE (id_versi, nomor_urut),
    CONSTRAINT fk_butir_versi FOREIGN KEY (id_versi) REFERENCES versi_instrumen(id_versi)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_butir_tipe CHECK (tipe_butir IN
        ('pilihan_ganda','esai','skala_likert','isian_singkat')),
    CONSTRAINT ck_butir_bobot CHECK (bobot_skor >= 0)
);

CREATE TABLE opsi_butir (
    id_opsi      INTEGER GENERATED ALWAYS AS IDENTITY,
    id_butir     INTEGER NOT NULL,
    teks_opsi    VARCHAR(255) NOT NULL,
    nilai_skor   NUMERIC(5,2) NOT NULL DEFAULT 0,
    kunci_jawaban BOOLEAN NOT NULL DEFAULT false,
    urutan_opsi  INTEGER NOT NULL,
    CONSTRAINT pk_opsi_butir PRIMARY KEY (id_opsi),
    CONSTRAINT uq_opsi_urutan UNIQUE (id_butir, urutan_opsi),
    CONSTRAINT fk_opsi_butir FOREIGN KEY (id_butir) REFERENCES butir_instrumen(id_butir)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE pelaksanaan_instrumen (
    id_pelaksanaan INTEGER GENERATED ALWAYS AS IDENTITY,
    id_kegiatan    INTEGER NOT NULL,
    id_versi       INTEGER NOT NULL,
    fase           VARCHAR(20) NOT NULL,
    dibuka_pada    TIMESTAMP,
    ditutup_pada   TIMESTAMP,
    -- Gerbang opsional: admin bisa menunda tampilnya hasil pretest/posttest ke
    -- peserta sampai sengaja dibuka. Default TRUE (langsung tampil) supaya
    -- kegiatan lama/yang sudah berjalan tidak mendadak terkunci.
    tampilkan_hasil BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT pk_pelaksanaan_instrumen PRIMARY KEY (id_pelaksanaan),
    CONSTRAINT uq_pelaksanaan UNIQUE (id_kegiatan, fase),
    CONSTRAINT fk_pelaksanaan_kegiatan FOREIGN KEY (id_kegiatan) REFERENCES kegiatan(id_kegiatan)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pelaksanaan_versi FOREIGN KEY (id_versi) REFERENCES versi_instrumen(id_versi)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_pelaksanaan_fase CHECK (fase IN ('demografi','pretest','posttest','kuesioner')),
    CONSTRAINT ck_pelaksanaan_waktu CHECK (ditutup_pada IS NULL OR dibuka_pada IS NULL OR ditutup_pada >= dibuka_pada)
);

CREATE TABLE respons_instrumen (
    id_respons      INTEGER GENERATED ALWAYS AS IDENTITY,
    id_pelaksanaan  INTEGER NOT NULL,
    id_pendaftaran  INTEGER NOT NULL,
    percobaan_ke    INTEGER NOT NULL DEFAULT 1,
    status_respons  VARCHAR(20) NOT NULL DEFAULT 'berlangsung',
    is_final        BOOLEAN NOT NULL DEFAULT false,
    mulai_pada      TIMESTAMP NOT NULL DEFAULT now(),
    selesai_pada    TIMESTAMP,
    CONSTRAINT pk_respons_instrumen PRIMARY KEY (id_respons),
    CONSTRAINT uq_respons_percobaan UNIQUE (id_pelaksanaan, id_pendaftaran, percobaan_ke),
    CONSTRAINT fk_respons_pelaksanaan FOREIGN KEY (id_pelaksanaan) REFERENCES pelaksanaan_instrumen(id_pelaksanaan)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_respons_pendaftaran FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_respons_percobaan CHECK (percobaan_ke BETWEEN 1 AND 3),
    CONSTRAINT ck_respons_status CHECK (status_respons IN ('berlangsung','selesai'))
);

CREATE UNIQUE INDEX uq_respons_final
    ON respons_instrumen (id_pelaksanaan, id_pendaftaran)
    WHERE is_final = true;

CREATE TABLE jawaban_butir (
    id_jawaban  INTEGER GENERATED ALWAYS AS IDENTITY,
    id_respons  INTEGER NOT NULL,
    id_butir    INTEGER NOT NULL,
    id_opsi     INTEGER,
    teks_jawaban TEXT,
    CONSTRAINT pk_jawaban_butir PRIMARY KEY (id_jawaban),
    CONSTRAINT uq_jawaban_respons_butir UNIQUE (id_respons, id_butir),
    CONSTRAINT fk_jawaban_respons FOREIGN KEY (id_respons) REFERENCES respons_instrumen(id_respons)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_jawaban_butir FOREIGN KEY (id_butir) REFERENCES butir_instrumen(id_butir)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_jawaban_opsi FOREIGN KEY (id_opsi) REFERENCES opsi_butir(id_opsi)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_jawaban_isi CHECK (id_opsi IS NOT NULL OR teks_jawaban IS NOT NULL)
);

CREATE TABLE hasil_penilaian (
    id_penilaian INTEGER GENERATED ALWAYS AS IDENTITY,
    id_respons   INTEGER NOT NULL,
    skor         NUMERIC(5,2) NOT NULL,
    nilai_lulus  NUMERIC(5,2),
    status_lulus BOOLEAN,
    dinilai_pada TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT pk_hasil_penilaian PRIMARY KEY (id_penilaian),
    CONSTRAINT uq_hasil_respons UNIQUE (id_respons),
    CONSTRAINT fk_hasil_respons FOREIGN KEY (id_respons) REFERENCES respons_instrumen(id_respons)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_hasil_skor CHECK (skor BETWEEN 0 AND 100),
    CONSTRAINT ck_hasil_nilai_lulus CHECK (nilai_lulus IS NULL OR nilai_lulus BETWEEN 0 AND 100)
);

-- =============================================================================
-- KELOMPOK 4 — AKTIVITAS PEMBELAJARAN vs AKTIVITAS GAMIFIKASI (DIPISAH, v2)
-- =============================================================================

-- Aktivitas TANPA skor/poin: materi bacaan, diskusi, tugas artefak.
CREATE TABLE aktivitas_pembelajaran (
    id_aktivitas   INTEGER GENERATED ALWAYS AS IDENTITY,
    id_sesi        INTEGER NOT NULL,
    judul_aktivitas VARCHAR(150) NOT NULL,
    jenis_aktivitas VARCHAR(20) NOT NULL,
    tool_ai        VARCHAR(20),
    deskripsi      TEXT,
    CONSTRAINT pk_aktivitas_pembelajaran PRIMARY KEY (id_aktivitas),
    CONSTRAINT fk_aktivitas_sesi FOREIGN KEY (id_sesi) REFERENCES sesi(id_sesi)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_aktivitas_jenis CHECK (jenis_aktivitas IN ('materi_bacaan','diskusi','tugas_artefak')),
    CONSTRAINT ck_aktivitas_tool CHECK (tool_ai IS NULL OR tool_ai IN
        ('canva','napkin','gamma','notebooklm','capcut','lainnya'))
);

CREATE TABLE partisipasi_aktivitas (
    id_partisipasi INTEGER GENERATED ALWAYS AS IDENTITY,
    id_aktivitas   INTEGER NOT NULL,
    id_pendaftaran INTEGER NOT NULL,
    status_partisipasi VARCHAR(20) NOT NULL DEFAULT 'belum',
    waktu_selesai  TIMESTAMP,
    CONSTRAINT pk_partisipasi_aktivitas PRIMARY KEY (id_partisipasi),
    CONSTRAINT uq_partisipasi UNIQUE (id_aktivitas, id_pendaftaran),
    CONSTRAINT fk_partisipasi_aktivitas FOREIGN KEY (id_aktivitas) REFERENCES aktivitas_pembelajaran(id_aktivitas)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_partisipasi_pendaftaran FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_partisipasi_status CHECK (status_partisipasi IN ('belum','berlangsung','selesai'))
);

CREATE TABLE artefak_peserta (
    id_artefak     INTEGER GENERATED ALWAYS AS IDENTITY,
    id_partisipasi INTEGER NOT NULL,
    judul_artefak  VARCHAR(150) NOT NULL,
    tautan_atau_file VARCHAR(255) NOT NULL,
    tipe_file      VARCHAR(20) NOT NULL,
    ukuran_file_kb INTEGER,
    status_verifikasi VARCHAR(20) NOT NULL DEFAULT 'menunggu',
    catatan_revisi TEXT,
    diunggah_pada  TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT pk_artefak_peserta PRIMARY KEY (id_artefak),
    CONSTRAINT uq_artefak_partisipasi UNIQUE (id_partisipasi),
    CONSTRAINT fk_artefak_partisipasi FOREIGN KEY (id_partisipasi) REFERENCES partisipasi_aktivitas(id_partisipasi)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_artefak_tipe CHECK (tipe_file IN ('image','pdf','video','link','document')),
    CONSTRAINT ck_artefak_ukuran CHECK (ukuran_file_kb IS NULL OR (ukuran_file_kb > 0 AND ukuran_file_kb <= 20480)),
    CONSTRAINT ck_artefak_status CHECK (status_verifikasi IN ('menunggu','terverifikasi','ditolak'))
);

-- BARU (v2): aktivitas BERSKOR/berpoin — kuis praktik, game, tantangan.
-- Dipisah dari aktivitas_pembelajaran agar aturan bisnis wajib #8 ("poin
-- gamifikasi tidak boleh menggantikan skor pre/post-test, dan sumber poin
-- harus jelas") terlihat langsung dari struktur tabel, bukan hanya dari
-- disiplin pemrogram.
CREATE TABLE aktivitas_gamifikasi (
    id_gamifikasi    INTEGER GENERATED ALWAYS AS IDENTITY,
    id_sesi          INTEGER NOT NULL,
    judul_gamifikasi VARCHAR(150) NOT NULL,
    jenis_gamifikasi VARCHAR(20) NOT NULL,
    poin_maksimal    INTEGER NOT NULL DEFAULT 0,
    CONSTRAINT pk_aktivitas_gamifikasi PRIMARY KEY (id_gamifikasi),
    CONSTRAINT fk_gamifikasi_sesi FOREIGN KEY (id_sesi) REFERENCES sesi(id_sesi)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_gamifikasi_jenis CHECK (jenis_gamifikasi IN ('kuis_praktik','game','tantangan')),
    CONSTRAINT ck_gamifikasi_poin CHECK (poin_maksimal >= 0)
);

CREATE TABLE partisipasi_gamifikasi (
    id_partisipasi_g INTEGER GENERATED ALWAYS AS IDENTITY,
    id_gamifikasi    INTEGER NOT NULL,
    id_pendaftaran   INTEGER NOT NULL,
    skor_permainan   INTEGER NOT NULL DEFAULT 0,
    waktu_selesai    TIMESTAMP,
    CONSTRAINT pk_partisipasi_gamifikasi PRIMARY KEY (id_partisipasi_g),
    CONSTRAINT uq_partisipasi_gamifikasi UNIQUE (id_gamifikasi, id_pendaftaran),
    CONSTRAINT fk_pg_gamifikasi FOREIGN KEY (id_gamifikasi) REFERENCES aktivitas_gamifikasi(id_gamifikasi)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pg_pendaftaran FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_pg_skor CHECK (skor_permainan >= 0)
);

CREATE TABLE badge (
    id_badge    INTEGER GENERATED ALWAYS AS IDENTITY,
    nama_badge  VARCHAR(100) NOT NULL,
    deskripsi   TEXT,
    kriteria    TEXT,
    CONSTRAINT pk_badge PRIMARY KEY (id_badge),
    CONSTRAINT uq_badge_nama UNIQUE (nama_badge)
);

CREATE TABLE badge_peserta (
    id_badge       INTEGER NOT NULL,
    id_pendaftaran INTEGER NOT NULL,
    diperoleh_pada TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT pk_badge_peserta PRIMARY KEY (id_badge, id_pendaftaran),
    CONSTRAINT fk_bp_badge FOREIGN KEY (id_badge) REFERENCES badge(id_badge)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bp_pendaftaran FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE reward (
    id_reward    INTEGER GENERATED ALWAYS AS IDENTITY,
    nama_reward  VARCHAR(150) NOT NULL,
    biaya_poin   INTEGER NOT NULL,
    stok         INTEGER NOT NULL,
    status_aktif BOOLEAN NOT NULL DEFAULT true,
    CONSTRAINT pk_reward PRIMARY KEY (id_reward),
    CONSTRAINT ck_reward_biaya CHECK (biaya_poin > 0),
    CONSTRAINT ck_reward_stok CHECK (stok >= 0)
);

CREATE TABLE penukaran_reward (
    id_penukaran   INTEGER GENERATED ALWAYS AS IDENTITY,
    id_pendaftaran INTEGER NOT NULL,
    id_reward      INTEGER NOT NULL,
    biaya_poin_saat_itu INTEGER NOT NULL,
    waktu_penukaran TIMESTAMP NOT NULL DEFAULT now(),
    status_penukaran VARCHAR(20) NOT NULL DEFAULT 'diproses',
    CONSTRAINT pk_penukaran_reward PRIMARY KEY (id_penukaran),
    CONSTRAINT fk_penukaran_pendaftaran FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_penukaran_reward FOREIGN KEY (id_reward) REFERENCES reward(id_reward)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_penukaran_biaya CHECK (biaya_poin_saat_itu > 0),
    CONSTRAINT ck_penukaran_status CHECK (status_penukaran IN ('diproses','selesai','dibatalkan'))
);

-- Poin HANYA berasal dari aktivitas gamifikasi (id_partisipasi_gamifikasi)
-- atau penukaran reward (id_penukaran) — bukan dari aktivitas_pembelajaran.
CREATE TABLE transaksi_poin (
    id_transaksi   INTEGER GENERATED ALWAYS AS IDENTITY,
    id_pendaftaran INTEGER NOT NULL,
    id_partisipasi_gamifikasi INTEGER,
    id_penukaran   INTEGER,
    jenis_transaksi VARCHAR(20) NOT NULL,
    jumlah_poin    INTEGER NOT NULL,
    keterangan     VARCHAR(255),
    dibuat_pada    TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT pk_transaksi_poin PRIMARY KEY (id_transaksi),
    CONSTRAINT fk_tp_pendaftaran FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tp_partisipasi_g FOREIGN KEY (id_partisipasi_gamifikasi) REFERENCES partisipasi_gamifikasi(id_partisipasi_g)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tp_penukaran FOREIGN KEY (id_penukaran) REFERENCES penukaran_reward(id_penukaran)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT ck_tp_jenis CHECK (jenis_transaksi IN ('perolehan','penukaran','koreksi')),
    CONSTRAINT ck_tp_jumlah_tidak_nol CHECK (jumlah_poin <> 0),
    CONSTRAINT ck_tp_arah CHECK (
        (jenis_transaksi = 'perolehan' AND jumlah_poin > 0) OR
        (jenis_transaksi = 'penukaran' AND jumlah_poin < 0) OR
        (jenis_transaksi = 'koreksi')
    )
);

-- =============================================================================
-- KELOMPOK 5 — KUESIONER, SERTIFIKAT, DAN INTEGRASI
-- =============================================================================

CREATE TABLE konfigurasi_evaluasi_kegiatan (
    id_konfigurasi INTEGER GENERATED ALWAYS AS IDENTITY,
    id_kegiatan    INTEGER NOT NULL,
    id_versi       INTEGER NOT NULL,
    mode_evaluasi  VARCHAR(20) NOT NULL DEFAULT 'identitas',
    dibuka_pada    TIMESTAMP,
    ditutup_pada   TIMESTAMP,
    CONSTRAINT pk_konfigurasi_evaluasi PRIMARY KEY (id_konfigurasi),
    CONSTRAINT uq_konfigurasi_kegiatan UNIQUE (id_kegiatan),
    CONSTRAINT fk_ke_kegiatan FOREIGN KEY (id_kegiatan) REFERENCES kegiatan(id_kegiatan)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ke_versi FOREIGN KEY (id_versi) REFERENCES versi_instrumen(id_versi)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_ke_mode CHECK (mode_evaluasi IN ('identitas','anonim'))
);

CREATE TABLE indikator_evaluasi (
    id_indikator  INTEGER GENERATED ALWAYS AS IDENTITY,
    id_butir      INTEGER NOT NULL,
    aspek_dinilai VARCHAR(30) NOT NULL,
    CONSTRAINT pk_indikator_evaluasi PRIMARY KEY (id_indikator),
    CONSTRAINT uq_indikator_butir UNIQUE (id_butir),
    CONSTRAINT fk_indikator_butir FOREIGN KEY (id_butir) REFERENCES butir_instrumen(id_butir)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_indikator_aspek CHECK (aspek_dinilai IN
        ('materi','fasilitator','metode','fasilitas_platform','manfaat','kepuasan','saran'))
);

CREATE TABLE sertifikat (
    id_sertifikat   INTEGER GENERATED ALWAYS AS IDENTITY,
    id_pendaftaran  INTEGER NOT NULL,
    nomor_sertifikat VARCHAR(50) NOT NULL,
    kode_verifikasi VARCHAR(50) NOT NULL,
    diterbitkan_pada TIMESTAMP NOT NULL DEFAULT now(),
    status_sertifikat VARCHAR(20) NOT NULL DEFAULT 'terbit',
    CONSTRAINT pk_sertifikat PRIMARY KEY (id_sertifikat),
    CONSTRAINT uq_sertifikat_pendaftaran UNIQUE (id_pendaftaran),
    CONSTRAINT uq_sertifikat_nomor UNIQUE (nomor_sertifikat),
    CONSTRAINT uq_sertifikat_kode UNIQUE (kode_verifikasi),
    CONSTRAINT fk_sertifikat_pendaftaran FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_sertifikat_status CHECK (status_sertifikat IN ('terbit','dicabut'))
);

CREATE TABLE log_integrasi (
    id_log       INTEGER GENERATED ALWAYS AS IDENTITY,
    nama_modul   VARCHAR(30) NOT NULL,
    jenis_kejadian VARCHAR(50) NOT NULL,
    keterangan   TEXT,
    dibuat_oleh  INTEGER,
    dibuat_pada  TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT pk_log_integrasi PRIMARY KEY (id_log),
    CONSTRAINT fk_log_pengguna FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- =============================================================================
-- VIEW DASHBOARD / LAPORAN (K5, K3, K1)
-- =============================================================================

CREATE VIEW v_saldo_poin AS
SELECT id_pendaftaran, COALESCE(SUM(jumlah_poin), 0) AS saldo_poin
FROM transaksi_poin
GROUP BY id_pendaftaran;

CREATE VIEW v_hasil_belajar AS
SELECT
    r.id_pendaftaran,
    MAX(CASE WHEN pi.fase = 'pretest'  THEN hp.skor END) AS skor_pretest,
    MAX(CASE WHEN pi.fase = 'posttest' THEN hp.skor END) AS skor_posttest,
    MAX(CASE WHEN pi.fase = 'posttest' THEN hp.skor END)
        - MAX(CASE WHEN pi.fase = 'pretest' THEN hp.skor END) AS selisih_skor
FROM respons_instrumen r
JOIN pelaksanaan_instrumen pi ON pi.id_pelaksanaan = r.id_pelaksanaan
JOIN hasil_penilaian hp ON hp.id_respons = r.id_respons
WHERE r.is_final = true AND pi.fase IN ('pretest','posttest')
GROUP BY r.id_pendaftaran;

CREATE VIEW v_rekap_kegiatan AS
SELECT
    k.id_kegiatan,
    k.tema,
    COUNT(DISTINCT p.id_pendaftaran) AS jumlah_pendaftar,
    COUNT(DISTINCT kh.id_pendaftaran) AS jumlah_hadir
FROM kegiatan k
LEFT JOIN pendaftaran p ON p.id_kegiatan = k.id_kegiatan
LEFT JOIN kehadiran kh ON kh.id_pendaftaran = p.id_pendaftaran
GROUP BY k.id_kegiatan, k.tema;

CREATE VIEW v_evaluasi_kegiatan AS
SELECT
    ke.id_kegiatan,
    ie.aspek_dinilai,
    ROUND(AVG(ob.nilai_skor), 2) AS rata_rata_skala,
    COUNT(DISTINCT jb.id_respons) AS jumlah_respons
FROM konfigurasi_evaluasi_kegiatan ke
JOIN pelaksanaan_instrumen pi ON pi.id_kegiatan = ke.id_kegiatan AND pi.fase = 'kuesioner'
JOIN respons_instrumen r ON r.id_pelaksanaan = pi.id_pelaksanaan AND r.is_final = true
JOIN jawaban_butir jb ON jb.id_respons = r.id_respons
JOIN indikator_evaluasi ie ON ie.id_butir = jb.id_butir
LEFT JOIN opsi_butir ob ON ob.id_opsi = jb.id_opsi
GROUP BY ke.id_kegiatan, ie.aspek_dinilai;

-- BARU (v2): leaderboard untuk fitur pengayaan K3 "Leaderboard mini
-- task/quiz ... beserta poin dan peringkat" (§7.1). Peringkat memakai POIN
-- YANG DIPEROLEH (perolehan+koreksi), bukan saldo bersih, agar penukaran
-- reward tidak menurunkan peringkat peserta secara tidak adil.
CREATE VIEW v_leaderboard AS
SELECT
    pd.id_pendaftaran,
    ps.nama_peserta,
    COALESCE(SUM(tp.jumlah_poin) FILTER (WHERE tp.jenis_transaksi IN ('perolehan','koreksi')), 0) AS total_poin_diperoleh,
    RANK() OVER (
        ORDER BY COALESCE(SUM(tp.jumlah_poin) FILTER (WHERE tp.jenis_transaksi IN ('perolehan','koreksi')), 0) DESC
    ) AS peringkat
FROM pendaftaran pd
JOIN peserta ps ON ps.id_peserta = pd.id_peserta
LEFT JOIN transaksi_poin tp ON tp.id_pendaftaran = pd.id_pendaftaran
GROUP BY pd.id_pendaftaran, ps.nama_peserta;

-- =============================================================================
-- Akhir skrip. Lihat 02_uji_constraint_revisi_v2.sql untuk uji valid/tidak valid.
-- =============================================================================
