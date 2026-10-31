<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HospitalSeeder extends Seeder
{
    public function run()
    {
        // Urutan penting! Ikuti foreign key dependency
        $this->seedDokters();
        $this->seedJadwalDokters();
        $this->seedPasiens();
        $this->seedPendaftarans();
        $this->seedRekamMedis();
    }

    private function seedDokters()
    {
        DB::table('dokters')->insertOrIgnore([
            [
                'nama'               => 'dr. Andi Susanto, Sp.PD',
                'no_identitas'       => '3201010101800001',
                'spesialisasi'       => 'Penyakit Dalam',
                'no_lisensi'         => 'STR-001-2020',
                'no_telepon'         => '081234567801',
                'email'              => 'andi.susanto@rumahsakit.com',
                'alamat'             => 'Jl. Sudirman No. 10, Jakarta',
                'jam_praktek_mulai'  => '08:00:00',
                'jam_praktek_selesai'=> '14:00:00',
                'hari_libur'         => 'Minggu',
                'status'             => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'nama'               => 'dr. Sari Dewi, Sp.A',
                'no_identitas'       => '3201010101850002',
                'spesialisasi'       => 'Anak',
                'no_lisensi'         => 'STR-002-2020',
                'no_telepon'         => '081234567802',
                'email'              => 'sari.dewi@rumahsakit.com',
                'alamat'             => 'Jl. Gatot Subroto No. 25, Jakarta',
                'jam_praktek_mulai'  => '09:00:00',
                'jam_praktek_selesai'=> '15:00:00',
                'hari_libur'         => 'Sabtu,Minggu',
                'status'             => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'nama'               => 'dr. Budi Hartono, Sp.JP',
                'no_identitas'       => '3201010101750003',
                'spesialisasi'       => 'Jantung dan Pembuluh Darah',
                'no_lisensi'         => 'STR-003-2020',
                'no_telepon'         => '081234567803',
                'email'              => 'budi.hartono@rumahsakit.com',
                'alamat'             => 'Jl. Thamrin No. 5, Jakarta',
                'jam_praktek_mulai'  => '10:00:00',
                'jam_praktek_selesai'=> '16:00:00',
                'hari_libur'         => 'Minggu',
                'status'             => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ]);
    }

    private function seedJadwalDokters()
    {
        DB::table('jadwal_dokters')->insertOrIgnore([
            // Dokter 1 - Senin, Rabu, Jumat
            ['dokter_id' => 1, 'hari' => 'Senin',  'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kapasitas' => 10, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['dokter_id' => 1, 'hari' => 'Rabu',   'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kapasitas' => 10, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['dokter_id' => 1, 'hari' => 'Jumat',  'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kapasitas' => 8,  'status' => true, 'created_at' => now(), 'updated_at' => now()],
            // Dokter 2 - Selasa, Kamis
            ['dokter_id' => 2, 'hari' => 'Selasa', 'jam_mulai' => '09:00', 'jam_selesai' => '13:00', 'kapasitas' => 12, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['dokter_id' => 2, 'hari' => 'Kamis',  'jam_mulai' => '09:00', 'jam_selesai' => '13:00', 'kapasitas' => 12, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            // Dokter 3 - Senin, Rabu
            ['dokter_id' => 3, 'hari' => 'Senin',  'jam_mulai' => '10:00', 'jam_selesai' => '14:00', 'kapasitas' => 8,  'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['dokter_id' => 3, 'hari' => 'Rabu',   'jam_mulai' => '10:00', 'jam_selesai' => '14:00', 'kapasitas' => 8,  'status' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedPasiens()
    {
        DB::table('pasiens')->insertOrIgnore([
            [
                'no_pendaftaran'   => 'P-2024-001',
                'nama'             => 'Budi Santoso',
                'no_identitas'     => '3201011501850001',
                'jenis_kelamin'    => 'Laki-laki',
                'tanggal_lahir'    => '1985-01-15',
                'alamat'           => 'Jl. Mawar No. 12, Bandung',
                'no_telepon'       => '081298765401',
                'email'            => 'budi.santoso@email.com',
                'status_pernikahan'=> 'Menikah',
                'pekerjaan'        => 'Karyawan Swasta',
                'agama'            => 'Islam',
                'berat_badan'      => 70.5,
                'tinggi_badan'     => 170.0,
                'golongan_darah'   => 'O',
                'alergi'           => 'Penisilin',
                'riwayat_penyakit' => 'Hipertensi',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'no_pendaftaran'   => 'P-2024-002',
                'nama'             => 'Siti Rahayu',
                'no_identitas'     => '3201014504900002',
                'jenis_kelamin'    => 'Perempuan',
                'tanggal_lahir'    => '1990-04-05',
                'alamat'           => 'Jl. Melati No. 8, Surabaya',
                'no_telepon'       => '081298765402',
                'email'            => 'siti.rahayu@email.com',
                'status_pernikahan'=> 'Menikah',
                'pekerjaan'        => 'Guru',
                'agama'            => 'Islam',
                'berat_badan'      => 55.0,
                'tinggi_badan'     => 158.0,
                'golongan_darah'   => 'A',
                'alergi'           => null,
                'riwayat_penyakit' => 'Asma',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'no_pendaftaran'   => 'P-2024-003',
                'nama'             => 'Ahmad Fauzi',
                'no_identitas'     => '3201012003780003',
                'jenis_kelamin'    => 'Laki-laki',
                'tanggal_lahir'    => '1978-03-20',
                'alamat'           => 'Jl. Kenanga No. 5, Yogyakarta',
                'no_telepon'       => '081298765403',
                'email'            => 'ahmad.fauzi@email.com',
                'status_pernikahan'=> 'Menikah',
                'pekerjaan'        => 'Wiraswasta',
                'agama'            => 'Islam',
                'berat_badan'      => 80.0,
                'tinggi_badan'     => 175.0,
                'golongan_darah'   => 'B',
                'alergi'           => 'Sulfa',
                'riwayat_penyakit' => 'Diabetes Tipe 2',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'no_pendaftaran'   => 'P-2024-004',
                'nama'             => 'Dewi Lestari',
                'no_identitas'     => '3201012510950004',
                'jenis_kelamin'    => 'Perempuan',
                'tanggal_lahir'    => '1995-10-25',
                'alamat'           => 'Jl. Anggrek No. 3, Semarang',
                'no_telepon'       => '081298765404',
                'email'            => 'dewi.lestari@email.com',
                'status_pernikahan'=> 'Belum Menikah',
                'pekerjaan'        => 'Mahasiswa',
                'agama'            => 'Kristen',
                'berat_badan'      => 50.0,
                'tinggi_badan'     => 160.0,
                'golongan_darah'   => 'AB',
                'alergi'           => null,
                'riwayat_penyakit' => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'no_pendaftaran'   => 'P-2024-005',
                'nama'             => 'Rudi Hermawan',
                'no_identitas'     => '3201010807700005',
                'jenis_kelamin'    => 'Laki-laki',
                'tanggal_lahir'    => '1970-07-08',
                'alamat'           => 'Jl. Dahlia No. 17, Medan',
                'no_telepon'       => '081298765405',
                'email'            => 'rudi.hermawan@email.com',
                'status_pernikahan'=> 'Menikah',
                'pekerjaan'        => 'PNS',
                'agama'            => 'Islam',
                'berat_badan'      => 75.0,
                'tinggi_badan'     => 168.0,
                'golongan_darah'   => 'O',
                'alergi'           => 'Aspirin',
                'riwayat_penyakit' => 'Kolesterol Tinggi, Hipertensi',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);
    }

    private function seedPendaftarans()
    {
        DB::table('pendaftarans')->insertOrIgnore([
            [
                'pasien_id'         => 1,
                'dokter_id'         => 1,
                'jadwal_dokter_id'  => 1,
                'tanggal_pendaftaran'=> Carbon::now()->subDays(10)->toDateString(),
                'jam_kunjungan'     => '08:30:00',
                'keluhan'           => 'Demam dan batuk sudah 3 hari',
                'status'            => 'completed',
                'no_antrian'        => 'A001',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'pasien_id'         => 2,
                'dokter_id'         => 2,
                'jadwal_dokter_id'  => 4,
                'tanggal_pendaftaran'=> Carbon::now()->subDays(7)->toDateString(),
                'jam_kunjungan'     => '09:30:00',
                'keluhan'           => 'Anak susah makan dan demam',
                'status'            => 'completed',
                'no_antrian'        => 'B001',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'pasien_id'         => 3,
                'dokter_id'         => 3,
                'jadwal_dokter_id'  => 6,
                'tanggal_pendaftaran'=> Carbon::now()->subDays(5)->toDateString(),
                'jam_kunjungan'     => '10:30:00',
                'keluhan'           => 'Nyeri dada dan sesak napas',
                'status'            => 'completed',
                'no_antrian'        => 'C001',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'pasien_id'         => 4,
                'dokter_id'         => 1,
                'jadwal_dokter_id'  => 2,
                'tanggal_pendaftaran'=> Carbon::now()->subDays(3)->toDateString(),
                'jam_kunjungan'     => '08:00:00',
                'keluhan'           => 'Sakit kepala dan pusing berputar',
                'status'            => 'completed',
                'no_antrian'        => 'A002',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'pasien_id'         => 5,
                'dokter_id'         => 1,
                'jadwal_dokter_id'  => 3,
                'tanggal_pendaftaran'=> Carbon::now()->subDays(1)->toDateString(),
                'jam_kunjungan'     => '08:00:00',
                'keluhan'           => 'Kontrol tekanan darah rutin',
                'status'            => 'confirmed',
                'no_antrian'        => 'A003',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);
    }

    private function seedRekamMedis()
    {
        DB::table('rekam_medis')->insertOrIgnore([
            [
                'pasien_id'          => 1,
                'dokter_id'          => 1,
                'pendaftaran_id'     => 1,
                'tanggal_kunjungan'  => Carbon::now()->subDays(10)->toDateString(),
                'keluhan_utama'      => 'Demam dan batuk sudah 3 hari',
                'diagnosis'          => 'Infeksi Saluran Pernapasan Atas (ISPA)',
                'anamnesis'          => 'Pasien mengeluh demam 38.5°C, batuk berdahak warna putih, tidak ada sesak napas',
                'pemeriksaan_fisik'  => 'Suhu 38.5°C, TD 120/80, Nadi 88x/mnt, RR 20x/mnt. Tenggorokan hiperemis.',
                'hasil_laboratorium' => 'Leukosit 11.000/uL (sedikit meningkat)',
                'resep'              => 'Paracetamol 500mg 3x1, Amoxicillin 500mg 3x1, Bromhexine 8mg 3x1',
                'tindakan'           => 'Pemberian obat oral, edukasi istirahat cukup dan minum air putih',
                'catatan_dokter'     => 'Kontrol ulang 3 hari jika demam tidak turun',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'pasien_id'          => 2,
                'dokter_id'          => 2,
                'pendaftaran_id'     => 2,
                'tanggal_kunjungan'  => Carbon::now()->subDays(7)->toDateString(),
                'keluhan_utama'      => 'Anak susah makan dan demam',
                'diagnosis'          => 'Faringitis Akut',
                'anamnesis'          => 'Ibu pasien mengeluh anak demam sejak 2 hari, susah makan, rewel',
                'pemeriksaan_fisik'  => 'Suhu 37.8°C, faring hiperemis, tonsil T1-T1',
                'hasil_laboratorium' => null,
                'resep'              => 'Paracetamol syr 3x1 cth, Amoxicillin syr 3x1 cth',
                'tindakan'           => 'Pemberian obat, edukasi orang tua',
                'catatan_dokter'     => 'Anjurkan makan makanan lunak dan minum cukup',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'pasien_id'          => 3,
                'dokter_id'          => 3,
                'pendaftaran_id'     => 3,
                'tanggal_kunjungan'  => Carbon::now()->subDays(5)->toDateString(),
                'keluhan_utama'      => 'Nyeri dada dan sesak napas',
                'diagnosis'          => 'Angina Pektoris Stabil',
                'anamnesis'          => 'Pasien mengeluh nyeri dada kiri menjalar ke lengan kiri saat aktivitas berat',
                'pemeriksaan_fisik'  => 'TD 150/95, Nadi 90x/mnt, EKG: ST depresi ringan',
                'hasil_laboratorium' => 'Troponin I: negatif, Kolesterol total: 245 mg/dL',
                'resep'              => 'Aspirin 80mg 1x1, Isosorbide dinitrate 5mg sublingual k/p, Atorvastatin 20mg 1x1',
                'tindakan'           => 'EKG, pemeriksaan lab, edukasi faktor risiko',
                'catatan_dokter'     => 'Hindari aktivitas berat, diet rendah lemak, kontrol 1 minggu',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'pasien_id'          => 4,
                'dokter_id'          => 1,
                'pendaftaran_id'     => 4,
                'tanggal_kunjungan'  => Carbon::now()->subDays(3)->toDateString(),
                'keluhan_utama'      => 'Sakit kepala dan pusing berputar',
                'diagnosis'          => 'Vertigo Perifer',
                'anamnesis'          => 'Pasien mengeluh pusing berputar tiba-tiba, mual, tidak muntah',
                'pemeriksaan_fisik'  => 'TD 110/70, Nadi 78x/mnt, Dix-Hallpike test positif kanan',
                'hasil_laboratorium' => null,
                'resep'              => 'Betahistine 24mg 2x1, Domperidone 10mg 3x1',
                'tindakan'           => 'Epley maneuver, edukasi posisi tidur',
                'catatan_dokter'     => 'Hindari gerakan kepala tiba-tiba, kontrol 1 minggu',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'pasien_id'          => 5,
                'dokter_id'          => 1,
                'pendaftaran_id'     => 5,
                'tanggal_kunjungan'  => Carbon::now()->subDays(1)->toDateString(),
                'keluhan_utama'      => 'Kontrol tekanan darah rutin',
                'diagnosis'          => 'Hipertensi Grade II terkontrol',
                'anamnesis'          => 'Pasien rutin kontrol, tidak ada keluhan berarti, obat rutin diminum',
                'pemeriksaan_fisik'  => 'TD 145/90, Nadi 80x/mnt, tidak ada edema',
                'hasil_laboratorium' => 'Kreatinin: 1.1 mg/dL, GDS: 105 mg/dL',
                'resep'              => 'Amlodipine 10mg 1x1, Ramipril 5mg 1x1',
                'tindakan'           => 'Pemeriksaan lab rutin, cek tekanan darah',
                'catatan_dokter'     => 'Tekanan darah belum optimal, dosis ditingkatkan. Kontrol 2 minggu.',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ]);
    }
}