<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\JadwalDokter;
use Illuminate\Support\Facades\Hash;    

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =====================
        // ADMIN
        // =====================
        User::updateOrCreate(
            ['email' => 'admin@rumahsakit.com'],
            [
                'name' => 'Admin Rumah Sakit',
                'password' => Hash::make('password123'), // ❗ JANGAN bcrypt
                'role' => 'admin',
            ]
        );

        // =====================
        // DOKTER USERS
        // =====================
        for ($i = 1; $i <= 5; $i++) {
            User::updateOrCreate(
                ['email' => "dokter{$i}@rumahsakit.com"],
                [
                    'name' => "Dokter {$i}",
                    'password' => Hash::make('password123'),
                    'role' => 'dokter',
                ]
            );
        }

        // =====================
        // PASIEN USERS
        // =====================
        for ($i = 1; $i <= 10; $i++) {
            User::updateOrCreate(
                ['email' => "pasien{$i}@rumahsakit.com"],
                [
                    'name' => "Pasien {$i}",
                    'password' => Hash::make('password123'),
                    'role' => 'pasien',
                ]
            );
        }

        // =====================
        // DATA DOKTER
        // =====================
        $spesialisasi = ['Umum', 'Jantung', 'Orthopedi', 'Gigi', 'Mata'];

        for ($i = 0; $i < 5; $i++) {
            $dokter = Dokter::create([
                'nama' => "Dr. " . fake()->name(),
                'no_identitas' => '130000' . str_pad($i + 1, 7, '0', STR_PAD_LEFT),
                'spesialisasi' => $spesialisasi[$i],
                'no_lisensi' => 'SIP-2024-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'no_telepon' => '0812' . rand(10000000, 99999999),
                'email' => "dokter" . ($i + 1) . "@rumahsakit.com",
                'alamat' => fake()->address(),
                'jam_praktek_mulai' => '08:00',
                'jam_praktek_selesai' => '16:00',
                'status' => true,
            ]);

            $hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            foreach ($hari as $day) {
                JadwalDokter::create([
                    'dokter_id' => $dokter->id,
                    'hari' => $day,
                    'jam_mulai' => '08:00',
                    'jam_selesai' => '16:00',
                    'kapasitas' => 10,
                    'status' => true,
                ]);
            }
        }

        // =====================
        // DATA PASIEN
        // =====================
        for ($i = 1; $i <= 10; $i++) {
            Pasien::create([
                'no_pendaftaran' => 'PDT-' . date('Y') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nama' => fake()->name(),
                'no_identitas' => '350000' . str_pad($i + 1, 7, '0', STR_PAD_LEFT),
                'jenis_kelamin' => fake()->randomElement(['Laki-laki', 'Perempuan']),
                'tanggal_lahir' => fake()->date(),
                'alamat' => fake()->address(),
                'no_telepon' => '0812' . rand(10000000, 99999999),
                'email' => "pasien{$i}@rumahsakit.com",
                'status_pernikahan' => fake()->randomElement(['Belum', 'Sudah']),
                'pekerjaan' => fake()->jobTitle(),
                'agama' => fake()->randomElement(['Islam', 'Kristen']),
                'berat_badan' => rand(50, 90),
                'tinggi_badan' => rand(150, 190),
                'golongan_darah' => fake()->randomElement(['A', 'B', 'AB', 'O']),
            ]);
        }
    }
}
