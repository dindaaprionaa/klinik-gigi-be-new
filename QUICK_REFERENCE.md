# QUICK REFERENCE GUIDE - SISTEM MANAJEMEN RUMAH SAKIT

## 🚀 QUICK START (5 MENIT)

```bash
# 1. Navigate to project
cd d:\aplikasi\backend-laravel

# 2. Install dependencies
composer install

# 3. Generate key
php artisan key:generate

# 4. Create database
# MySQL: CREATE DATABASE rumah_sakit_db;

# 5. Run migrations & seed
php artisan migrate:fresh --seed

# 6. Start server
php artisan serve

# API siap di: http://localhost:8000/api
```

---

## 🔐 LOGIN TEST ACCOUNTS

### 1. Admin Login
```
POST /api/login
{
  "email": "admin@rumahsakit.com",
  "password": "password123"
}
```

### 2. Dokter Login
```
POST /api/login
{
  "email": "dokter1@rumahsakit.com",
  "password": "password123"
}
```

### 3. Pasien Login
```
POST /api/login
{
  "email": "pasien1@rumahsakit.com",
  "password": "password123"
}
```

---

## 📌 ADMIN API ENDPOINTS

### Dashboard
```
GET /api/dashboard
Response: {totalPasien, totalDokter, totalPendaftaran, pendaftaranHariIni, pendaftaranPending}
```

### Dokter Management
```
GET    /api/dokter              - List all dokter
POST   /api/dokter              - Create dokter
GET    /api/dokter/1            - Get dokter detail
PUT    /api/dokter/1            - Update dokter
DELETE /api/dokter/1            - Delete dokter
```

### Pasien Management
```
GET    /api/pasien              - List all pasien
POST   /api/pasien              - Create pasien
GET    /api/pasien/1            - Get pasien detail
PUT    /api/pasien/1            - Update pasien
DELETE /api/pasien/1            - Delete pasien
```

### Jadwal Management
```
GET    /api/jadwal              - List all jadwal
POST   /api/jadwal              - Create jadwal
GET    /api/jadwal/1            - Get jadwal detail
PUT    /api/jadwal/1            - Update jadwal
DELETE /api/jadwal/1            - Delete jadwal
```

### Verifikasi Pendaftaran
```
POST /api/pendaftaran/{id}/verifikasi
{
  "status": "confirmed"  // atau "rejected"
}
```

### Rekam Medis
```
GET    /api/rekam-medis         - List all
POST   /api/rekam-medis         - Create
GET    /api/rekam-medis/1       - Get detail
PUT    /api/rekam-medis/1       - Update
DELETE /api/rekam-medis/1       - Delete
```

### Laporan
```
GET /api/laporan                - Get all reports
```

---

## 👨‍⚕️ DOKTER API ENDPOINTS

### Dashboard
```
GET /api/dashboard-dokter
Response: Overview statistik
```

### Pasien Saya
```
GET /api/pasien-saya
Response: List pasien untuk dokter tersebut
```

### Rekam Medis
```
GET    /api/rekam-medis         - List
POST   /api/rekam-medis         - Create
GET    /api/rekam-medis/1       - Get detail
PUT    /api/rekam-medis/1       - Update
DELETE /api/rekam-medis/1       - Delete
```

---

## 👤 PASIEN API ENDPOINTS

### Dashboard Pasien
```
GET /api/dashboard-pasien
Response: {pasien, pendaftaranTerbaru, totalKunjungan}
```

### Daftar Berobat
```
POST /api/daftar-berobat
{
  "dokter_id": 1,
  "jadwal_dokter_id": 1,
  "tanggal_pendaftaran": "2024-02-15",
  "jam_kunjungan": "09:00",
  "keluhan": "Sakit kepala"
}
```

### Riwayat Pendaftaran
```
GET /api/riwayat?page=1
Response: Paginated list of pendaftaran
```

### Antrian Aktif
```
GET /api/antrian
Response: Current queue info
```

---

## 📊 DATABASE TABLES REFERENCE

| Table | Primary Key | Foreign Keys |
|-------|------------|--------------|
| users | id | - |
| pasiens | id | - |
| dokters | id | - |
| jadwal_dokters | id | dokter_id |
| pendaftarans | id | pasien_id, dokter_id, jadwal_dokter_id |
| rekam_medis | id | pasien_id, dokter_id, pendaftaran_id |
| notifikasis | id | pendaftaran_id, user_id |

---

## 🔄 COMMON WORKFLOWS

### Workflow 1: Pasien Daftar Berobat
```
1. Pasien login
2. GET /api/dashboard-pasien (lihat info)
3. POST /api/daftar-berobat (daftar dengan dokter tertentu)
4. GET /api/antrian (lihat queue)
5. GET /api/riwayat (lihat history)
```

### Workflow 2: Admin Verifikasi Pendaftaran
```
1. Admin login
2. GET /api/dashboard (lihat pending count)
3. GET /api/pendaftaran (lihat daftar pendaftaran) - jika endpoint ada
4. POST /api/pendaftaran/{id}/verifikasi (approve/reject)
```

### Workflow 3: Dokter Input Rekam Medis
```
1. Dokter login
2. GET /api/dashboard-dokter (lihat overview)
3. GET /api/pasien-saya (lihat pasien)
4. POST /api/rekam-medis (input rekam medis)
5. GET /api/rekam-medis (lihat history)
```

---

## 🛠️ USEFUL ARTISAN COMMANDS

```bash
# Database
php artisan migrate                    # Run migrations
php artisan migrate:fresh --seed       # Fresh DB dengan seed
php artisan migrate:rollback           # Rollback migrations
php artisan db:seed                    # Seed data

# Cache
php artisan cache:clear                # Clear cache
php artisan config:clear               # Clear config cache

# Development
php artisan serve                      # Start dev server
php artisan tinker                     # Interactive shell

# Code Generation
php artisan make:model ModelName       # Create model
php artisan make:controller Controller # Create controller
php artisan make:migration create_table_name  # Create migration

# Testing
php artisan test                       # Run tests
```

---

## 📝 REQUEST HEADERS

Semua request (kecuali login/register) harus include:

```
Authorization: Bearer {token}
Content-Type: application/json
```

---

## ✅ RESPONSE FORMAT

### Success (200/201)
```json
{
  "message": "Success message",
  "data": { ... }
}
```

### Error (4xx/5xx)
```json
{
  "message": "Error message",
  "errors": {
    "field": ["Error detail"]
  }
}
```

---

## 🔒 MIDDLEWARE REFERENCE

| Middleware | Usage | Roles |
|-----------|-------|-------|
| auth:sanctum | Require authentication | Any authenticated user |
| role:admin | Require admin role | admin only |
| role:dokter | Require dokter role | dokter only |
| role:pasien | Require pasien role | pasien only |

---

## 📋 ROLE PERMISSIONS

| Action | Admin | Dokter | Pasien |
|--------|-------|--------|--------|
| Dashboard | ✅ | ✅ | ✅ |
| Manage Dokter | ✅ | ❌ | ❌ |
| Manage Pasien | ✅ | ✅ | ❌ |
| Manage Jadwal | ✅ | ✅ | ❌ |
| Verifikasi Pendaftaran | ✅ | ❌ | ❌ |
| CRUD Rekam Medis | ✅ | ✅ | ❌ |
| Daftar Berobat | ❌ | ❌ | ✅ |
| Lihat Riwayat | ✅ | ✅ | ✅ |
| Lihat Antrian | ✅ | ✅ | ✅ |
| Lihat Laporan | ✅ | ✅ | ❌ |

---

## 🐛 TROUBLESHOOTING

### Issue: "SQLSTATE[HY000] [2002] No such file or directory"
```
Solution: Pastikan MySQL running dan database sudah dibuat
```

### Issue: "Call to undefined method role()"
```
Solution: Ensure RoleMiddleware registered di bootstrap/app.php
```

### Issue: "Token invalid or expired"
```
Solution: Login ulang dan gunakan token terbaru
```

### Issue: "Unauthorized - You do not have permission"
```
Solution: Check role di database dan pastikan sudah login dengan akun yang benar
```

---

## 📞 SUPPORT

Untuk issue/pertanyaan:
1. Check API_DOCUMENTATION.md
2. Check README.md
3. Check STRUCTURE.md
4. Check error logs di `storage/logs/`

---

## 📱 TESTING WITH POSTMAN

1. Import `Postman_Collection.json`
2. Set `base_url` variable: `http://localhost:8000/api`
3. Login untuk mendapat token
4. Set `token` variable dengan token dari response
5. Test endpoints

---

**Last Updated: February 1, 2026**
**Status: ✅ READY FOR USE**
