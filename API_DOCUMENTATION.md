# API Endpoints Documentation

## Base URL
```
http://localhost:8000/api
```

---

## Authentication Endpoints

### 1. Register
**POST** `/register`

Request:
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "pasien" // admin, dokter, pasien
}
```

Response:
```json
{
    "message": "User registered successfully",
    "user": { ... },
    "token": "token_value"
}
```

---

### 2. Login
**POST** `/login`

Request:
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

Response:
```json
{
    "message": "Login successful",
    "user": { ... },
    "token": "token_value"
}
```

---

### 3. Get Current User
**GET** `/me`

Headers:
```
Authorization: Bearer {token}
```

Response:
```json
{
    "user": { ... }
}
```

---

### 4. Profile
**GET** `/profile`

Headers:
```
Authorization: Bearer {token}
```

Response:
```json
{
    "user": { ... }
}
```

---

### 5. Update Profile
**PUT** `/profile`

Headers:
```
Authorization: Bearer {token}
```

Request:
```json
{
    "name": "John Doe Updated",
    "email": "john.updated@example.com"
}
```

---

### 6. Logout
**POST** `/logout`

Headers:
```
Authorization: Bearer {token}
```

---

## Admin Routes

> Semua route admin hanya dapat diakses oleh user dengan `role: admin`.

### Dashboard
**GET** `/dashboard`

**GET** `/dashboard/statistik-pasien`

**GET** `/dashboard/statistik-dokter`

**GET** `/dashboard/statistik-pendaftaran`

**GET** `/dashboard/pendaftaran-terbaru`

**GET** `/dashboard/aktivitas-hari-ini`

---

### Dokter Management (CRUD)
**GET** `/dokter`

**POST** `/dokter`

**GET** `/dokter/{id}`

**PUT/PATCH** `/dokter/{id}`

**DELETE** `/dokter/{id}`

**GET** `/dokter/spesialisasi/{spesialisasi}`

---

### Jadwal Management (CRUD)
**GET** `/jadwal`

**POST** `/jadwal`

**GET** `/jadwal/{id}`

**PUT/PATCH** `/jadwal/{id}`

**DELETE** `/jadwal/{id}`

**GET** `/jadwal/dokter/{dokter_id}`

---

### Pasien Management (CRUD)
**GET** `/pasien`

**POST** `/pasien`

**GET** `/pasien/{id}`

**PUT/PATCH** `/pasien/{id}`

**DELETE** `/pasien/{id}`

---

### Pendaftaran
**POST** `/pendaftaran/{id}/verifikasi`

Request:
```json
{
    "status": "confirmed" // atau "rejected"
}
```

**GET** `/pendaftaran/pasien/{pasien_id}`

**GET** `/pendaftaran/dokter/{dokter_id}`

---

### Rekam Medis
**GET** `/rekam-medis`

**POST** `/rekam-medis`

**GET** `/rekam-medis/{id}`

**PUT/PATCH** `/rekam-medis/{id}`

**DELETE** `/rekam-medis/{id}`

**GET** `/rekam-medis/pasien/{pasien_id}`

**GET** `/rekam-medis/dokter/{dokter_id}`

---

### Laporan
**GET** `/laporan`

**GET** `/laporan/pasien`

**GET** `/laporan/rekam-medis`

**GET** `/laporan/dokter`

**GET** `/laporan/pendaftaran`

**POST** `/laporan/export-pdf`

**POST** `/laporan/export-excel`

---

## Dokter Routes

> Semua route dokter bisa diakses oleh user dengan `role: dokter`.

### Dashboard Dokter
**GET** `/dashboard-dokter`

### Pasien Saya
**GET** `/pasien-saya`

### Rekam Medis Dokter
**GET** `/rekam-medis`

**POST** `/rekam-medis`

**GET** `/rekam-medis/{id}`

**PUT/PATCH** `/rekam-medis/{id}`

**DELETE** `/rekam-medis/{id}`

**GET** `/rekam-medis/pasien/{pasien_id}`

**GET** `/rekam-medis/dokter/{dokter_id}`

---

## Pasien Routes

> Semua route pasien bisa diakses oleh user dengan `role: pasien`.

### Dashboard Pasien
**GET** `/dashboard-pasien`

### Daftar Berobat
**POST** `/daftar-berobat`

Request:
```json
{
    "pasien_id": 1,
    "dokter_id": 1,
    "jadwal_dokter_id": 1,
    "tanggal_pendaftaran": "2024-02-15",
    "jam_kunjungan": "09:00",
    "keluhan": "Sakit kepala"
}
```

### Riwayat
**GET** `/riwayat`

Query:
```
?page=1
```

### Antrian
**GET** `/antrian`

---

## Catatan
- Front-end menggunakan endpoint `/api/...`.
- `API_DOCUMENTATION.md` adalah dokumentasi; front-end tidak otomatis mengambil data dari file ini.
- Yang penting adalah endpoint yang tersedia di `routes/api.php` dan controller yang mengimplementasikan endpoint tersebut.
