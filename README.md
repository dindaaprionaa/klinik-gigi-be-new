# Sistem Manajemen Rumah Sakit - Backend API

Backend Laravel untuk Sistem Manajemen Rumah Sakit dengan fitur lengkap manajemen pasien, dokter, jadwal, pendaftaran, dan rekam medis.

## 📋 Fitur Utama

### Authentication
- ✅ Register & Login dengan role-based access
- ✅ Token-based authentication (Sanctum)
- ✅ Profile management

### Admin Features
- ✅ Dashboard dengan statistik lengkap
- ✅ Manajemen Dokter (CRUD)
- ✅ Manajemen Jadwal Dokter
- ✅ Manajemen Pasien
- ✅ Verifikasi Pendaftaran
- ✅ Manajemen Rekam Medis
- ✅ Laporan

### Pasien Features
- ✅ Dashboard Pasien
- ✅ Daftar Berobat
- ✅ Lihat Riwayat Pendaftaran
- ✅ Lihat Antrian Aktif

### Dokter Features
- ✅ Dashboard Dokter
- ✅ Manajemen Rekam Medis
- ✅ Lihat Pasien

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js (optional, untuk frontend)

### Installation

1. **Clone repository**
```bash
cd d:\aplikasi\backend-laravel
```

2. **Install dependencies**
```bash
composer install
```

3. **Generate application key**
```bash
php artisan key:generate
```

4. **Create database**
```bash
# Di MySQL
CREATE DATABASE rumah_sakit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

5. **Run migrations**
```bash
php artisan migrate
```

6. **Seed database dengan data dummy**
```bash
php artisan db:seed
```

7. **Start server**
```bash
php artisan serve
```

Server akan berjalan di: `http://localhost:8000`

API akan accessible di: `http://localhost:8000/api`

---

## 📁 Project Structure

```
backend-laravel/
├── app/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Pasien.php
│   │   ├── Dokter.php
│   │   ├── JadwalDokter.php
│   │   ├── Pendaftaran.php
│   │   ├── RekamMedis.php
│   │   └── Notifikasi.php
│   │
│   ├── Http/
│   │   ├── Controllers/API/
│   │   │   ├── AuthController.php
│   │   │   ├── PasienController.php
│   │   │   ├── DokterController.php
│   │   │   ├── JadwalController.php
│   │   │   ├── PendaftaranController.php
│   │   │   ├── RekamMedisController.php
│   │   │   ├── DashboardController.php
│   │   │   └── LaporanController.php
│   │   │
│   │   └── Middleware/
│   │       └── RoleMiddleware.php
│
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_pasiens_table.php
│   │   ├── create_dokters_table.php
│   │   ├── create_jadwal_dokters_table.php
│   │   ├── create_pendaftarans_table.php
│   │   ├── create_rekam_medis_table.php
│   │   └── create_notifikasis_table.php
│   │
│   └── seeders/
│       └── DatabaseSeeder.php
│
├── routes/
│   ├── api.php (API Routes)
│   └── web.php
│
├── .env
├── API_DOCUMENTATION.md
└── README.md
```

---

## 🔐 Authentication

### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@rumahsakit.com",
    "password": "password123"
  }'
```

Response:
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "Admin Rumah Sakit",
        "email": "admin@rumahsakit.com",
        "role": "admin"
    },
    "token": "token_value"
}
```

---

## 👥 Test Credentials

### Admin
- Email: `admin@rumahsakit.com`
- Password: `password123`

### Dokter (1-5)
- Email: `dokter1@rumahsakit.com` ... `dokter5@rumahsakit.com`
- Password: `password123`

### Pasien (1-10)
- Email: `pasien1@rumahsakit.com` ... `pasien10@rumahsakit.com`
- Password: `password123`

---

## 📚 API Documentation

Untuk dokumentasi lengkap API endpoints, lihat [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

### Main Endpoints Structure

```
PUBLIC:
  POST   /api/login
  POST   /api/register

AUTHENTICATED:
  GET    /api/me
  POST   /api/logout

  ADMIN:
    GET    /api/dashboard
    CRUD   /api/dokter
    CRUD   /api/jadwal
    CRUD   /api/pasien
    POST   /api/pendaftaran/{id}/verifikasi
    CRUD   /api/rekam-medis
    GET    /api/laporan

  PASIEN:
    GET    /api/dashboard-pasien
    POST   /api/daftar-berobat
    GET    /api/riwayat
    GET    /api/antrian

  DOKTER:
    GET    /api/dashboard-dokter
    CRUD   /api/rekam-medis
    GET    /api/pasien-saya
```

---

## 🗄️ Database Schema

### Users Table
```sql
- id
- name
- email (unique)
- password
- role (admin, dokter, pasien)
- timestamps
```

### Pasiens Table
```sql
- id
- no_pendaftaran (unique)
- nama
- no_identitas (unique)
- jenis_kelamin
- tanggal_lahir
- alamat
- no_telepon
- email
- status_pernikahan
- pekerjaan
- agama
- berat_badan
- tinggi_badan
- golongan_darah
- alergi
- riwayat_penyakit
- timestamps
```

### Dokters Table
```sql
- id
- nama
- no_identitas (unique)
- spesialisasi
- no_lisensi (unique)
- no_telepon
- email (unique)
- alamat
- jam_praktek_mulai
- jam_praktek_selesai
- hari_libur
- status (boolean)
- timestamps
```

### Jadwal_Dokters Table
```sql
- id
- dokter_id (FK)
- hari
- jam_mulai
- jam_selesai
- kapasitas
- status (boolean)
- timestamps
```

### Pendaftarans Table
```sql
- id
- pasien_id (FK)
- dokter_id (FK)
- jadwal_dokter_id (FK)
- tanggal_pendaftaran
- jam_kunjungan
- keluhan
- status (pending, confirmed, checked_in, completed, cancelled)
- no_antrian
- timestamps
```

### Rekam_Medis Table
```sql
- id
- pasien_id (FK)
- dokter_id (FK)
- pendaftaran_id (FK)
- tanggal_kunjungan
- keluhan_utama
- diagnosis
- anamnesis
- pemeriksaan_fisik
- hasil_laboratorium
- resep
- tindakan
- catatan_dokter
- timestamps
```

### Notifikasis Table
```sql
- id
- pendaftaran_id (FK)
- user_id (FK)
- judul
- pesan
- tipe
- status_baca (boolean)
- tanggal_baca
- timestamps
```

---

## 🔧 Useful Commands

```bash
# Generate key
php artisan key:generate

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Seed database
php artisan db:seed

# Create fresh database
php artisan migrate:fresh --seed

# Tinker (interactive shell)
php artisan tinker

# Clear cache
php artisan cache:clear
php artisan config:clear

# Start development server
php artisan serve

# Run tests
php artisan test
```

---

## 📝 Response Format

### Success Response
```json
{
    "message": "Success message",
    "data": { ... }
}
```

### Error Response
```json
{
    "message": "Error message",
    "errors": {
        "field": ["Error description"]
    }
}
```

---

## 🔒 Security Features

- ✅ Sanctum Token Authentication
- ✅ Role-based Access Control (RBAC)
- ✅ Password Hashing (bcrypt)
- ✅ CSRF Protection
- ✅ SQL Injection Prevention (Query Builder)
- ✅ Input Validation

---

## 📞 Support

Untuk pertanyaan atau masalah, silakan buat issue atau hubungi tim development.

---

## 📄 License

Sistem Manajemen Rumah Sakit © 2024. All rights reserved.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
