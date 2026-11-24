# Dokumentasi Resmi REST API GrowHub
**Enterprise Knowledge Sharing & Gamification Platform**

Dokumen ini berisi spesifikasi teknis lengkap dari seluruh endpoint RESTful API yang tersedia pada backend GrowHub, mencakup metode autentikasi, format parameter request, aturan validasi, dan contoh respons JSON.

---

## Daftar Isi
1. [Standar & Konvensi API](#1-standar--konvensi-api)
2. [Autentikasi & Manajemen Sesi (`/api/auth`)](#2-autentikasi--manajemen-sesi-apiauth)
3. [Beranda, Dashboard & Gamifikasi (`/api/home`, `/api/dashboard`, `/api/leaderboard`)](#3-beranda-dashboard--gamifikasi)
4. [Knowledge Feed & Konten Pengetahuan (`/api/contents`, `/api/categories`, `/api/my/*`)](#4-knowledge-feed--konten-pengetahuan)
5. [Interaksi Sosial & Komentar (`/api/contents/{id}/*`, `/api/comments/*`)](#5-interaksi-sosial--komentar)
6. [Komunitas Karyawan (`/api/communities`)](#6-komunitas-karyawan-apicommunities)
7. [Learning Center & Kursus (`/api/courses`)](#7-learning-center--kursus-apicourses)
8. [Notifikasi In-App (`/api/notifications`)](#8-notifikasi-in-app-apinotifications)
9. [Profil Karyawan & Akun (`/api/profile`, `/api/users`)](#9-profil-karyawan--akun)
10. [Layanan Bantuan & FAQ (`/api/support`)](#10-layanan-bantuan--faq-apisupport)
11. [Portal Reviewer (`/api/review`)](#11-portal-reviewer-apireview)
12. [Admin Console - Manajemen User (`/api/admin/users`)](#12-admin-console---manajemen-user-apiadminusers)
13. [Admin Console - Pengaturan Poin Gamifikasi (`/api/admin/point-rules`)](#13-admin-console---pengaturan-poin-gamifikasi-apiadminpoint-rules)
14. [Admin Console - Pengaturan Jenjang Level (`/api/admin/level-rules`)](#14-admin-console---pengaturan-jenjang-level-apiadminlevel-rules)
15. [Admin Console - Pengaturan Batas Ranking & Reset (`/api/admin/rank-settings`)](#15-admin-console---pengaturan-batas-ranking--reset-apiadminrank-settings)
16. [Kebijakan Etika & Moderasi (`/api/ethics-policies`, `/api/admin/policy-configs`)](#16-kebijakan-etika--moderasi-apiethics-policies-apiadminpolicy-configs)
17. [Kontak Helper & Support Layanan Karyawan (`/api/support/helpers`, `/api/admin/helper-contacts`)](#17-kontak-helper--support-layanan-karyawan-apisupporthelpers-apiadminhelper-contacts)
18. [Admin Console - Pengaturan Email & Server SMTP (`/api/admin/mail-settings`)](#18-admin-console---pengaturan-email--server-smtp-apiadminmail-settings)
19. [Tabel Kode Respons HTTP](#19-tabel-kode-respons-http)

---

## 1. Standar & Konvensi API

### 1.1 Base URL
- **Lokal (Development)**: `http://localhost:8001/api`
- **Produksi**: `https://<domain-aplikasi>/api`

### 1.2 Header Standar
Kecuali untuk endpoint publik login, seluruh request wajib menyertakan header:
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token_sanctum_anda>
```
*(Catatan: Untuk upload berkas multi-part dokumen/gambar, hilangkan header `Content-Type: application/json` agar browser/klien HTTP otomatis membuat boundary `multipart/form-data`)*.

### 1.3 Tingkat Hak Akses (Role-Based Access Control)
- **Public**: Dapat diakses tanpa login (misal `POST /api/auth/login`).
- **Authenticated (`auth:sanctum`)**: Semua pengguna yang telah login (Employee, Reviewer, Admin).
- **Reviewer (`reviewer`)**: Karyawan dengan role `reviewer` atau `admin`.
- **Admin**: Khusus pengguna dengan role `admin`.

---

## 2. Autentikasi & Manajemen Sesi (`/api/auth`)

### 2.1 Login Pengguna
Masuk ke dalam sistem menggunakan 9–10 digit NIP dan password.
- **URL**: `POST /api/auth/login`
- **Akses**: `Public` (Rate limit: 30 request per menit / `throttle:30,1`)
- **Request Body** (`application/json`):
  | Parameter | Tipe Data | Wajib | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `nip` | string | Ya | 9–10 digit angka NIP (Nomor Induk Pegawai). |
  | `password` | string | Ya | Password akun. |

- **Contoh Request Body**:
  ```json
  {
    "nip": "1000000001",
    "password": "PasswordKuat2026!"
  }
  ```

- **Contoh Response Sukses (`200 OK`)**:
  ```json
  {
    "token": "1|7xK8mPqRtL9...plainTextSanctumToken...",
    "user": {
      "id": 1,
      "nip": "1000000001",
      "name": "Super Admin GrowHub",
      "email": "admin@growhub.co.id",
      "role": "admin",
      "department": "IT Operations",
      "position": "Lead Enterprise Administrator",
      "location": "Head Office",
      "avatar": null,
      "points": 5000,
      "current_streak": 12,
      "longest_streak": 20,
      "is_active": true,
      "must_change_password": false,
      "is_reviewer": true,
      "unread_notifications": 3,
      "points_today": 2,
      "default_password": false
    }
  }
  ```

- **Contoh Response Error Validasi (`422 Unprocessable Content`)**:
  ```json
  {
    "message": "NIP atau password salah.",
    "errors": {
      "nip": [
        "NIP atau password salah."
      ]
    }
  }
  ```

---

### 2.2 Ambil Data Profil User yang Sedang Login (`Me`)
Mengambil identitas user, poin terkini, status streak, serta hak akses reviewer dari token sesi aktif.
- **URL**: `GET /api/auth/me`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "id": 1,
    "nip": "1000000001",
    "name": "Super Admin GrowHub",
    "email": "admin@growhub.co.id",
    "role": "admin",
    "points": 5000,
    "current_streak": 12,
    "is_reviewer": true,
    "must_change_password": false,
    "unread_notifications": 2
  }
  ```

---

### 2.3 Alur OTP Email & Wajib Ganti Password Pertama Kali (`First-Time Password Change`)
Sebelum menyetel password baru pada login pertama atau pasca-reset admin (`must_change_password = true`), pengguna wajib memverifikasi kode OTP 6 digit yang dikirim ke email terdaftarnya:

1. **Kirim OTP Email**: `POST /api/auth/change-password/send-otp` *(Authenticated, Rate limit: `throttle:10,1`, cooldown kirim ulang 60 detik, masa berlaku 1 menit)*
   - **Response Sukses (`200 OK`)**:
     ```json
     {
       "message": "Kode OTP telah dikirim ke email terdaftar Anda.",
       "masked_email": "bu***@growhub.co.id",
       "expires_in": 60,
       "resend_cooldown": 60
     }
     ```
2. **Verifikasi OTP Email**: `POST /api/auth/change-password/verify-otp` *(Authenticated, Rate limit: `throttle:15,1`, maks. 5 percobaan salah)*
   - **Request Body**: `{ "otp": "123456" }`
   - **Response Sukses (`200 OK`)**:
     ```json
     {
       "message": "Kode OTP berhasil diverifikasi. Silakan buat password baru Anda.",
       "otp_verification_token": "8x9AbCdEf...random64charToken..."
     }
     ```
3. **Simpan Password Baru**: `POST /api/auth/first-change-password`
   - **Akses**: `Authenticated`
   - **Request Body** (`application/json`):
     | Parameter | Tipe Data | Wajib | Keterangan |
     | :--- | :--- | :--- | :--- |
     | `otp_verification_token` | string | Ya | Token hasil verifikasi OTP dari langkah sebelumnya. |
     | `password` | string | Ya | Password baru minimal 8 karakter. Tidak boleh sama dengan NIP. |
     | `password_confirmation` | string | Ya | Konfirmasi password baru (harus sama persis). |

- **Contoh Request Body (`POST /api/auth/first-change-password`)**:
  ```json
  {
    "otp_verification_token": "8x9AbCdEf...random64charToken...",
    "password": "PasswordBaruKaryawan#2026",
    "password_confirmation": "PasswordBaruKaryawan#2026"
  }
  ```

- **Contoh Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Password berhasil diperbarui. Selamat datang di GrowHub!",
    "user": {
      "id": 5,
      "nip": "202301001",
      "name": "Budi Store Crew",
      "must_change_password": false
    }
  }
  ```

- **Contoh Response Ditolak (`403 Forbidden` jika user mencoba reset sendiri tanpa izin admin)**:
  ```json
  {
    "message": "Reset password mandiri dinonaktifkan. Password hanya dapat direset oleh Administrator."
  }
  ```

---

### 2.4 Logout Pengguna
Mencabut token bearer aktif dari database personal access token.
- **URL**: `POST /api/auth/logout`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Berhasil logout."
  }
  ```

---

## 3. Beranda, Dashboard & Gamifikasi

### 3.1 Ringkasan Halaman Beranda (`Home Overview`)
Mengambil konten trending, feed terbaru, maks. 3 konten yang terakhir dilihat (`recently_viewed`), juara mingguan (`top_weekly` sesuai batas `home_widget_limit`), kategori konten, dan materi kursus rekomendasi.
- **URL**: `GET /api/home`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "stats": {
      "published_contents": 84,
      "contributors": 26,
      "employees": 115,
      "contents_this_week": 8
    },
    "my_rank": 3,
    "trending": [...],
    "latest": [...],
    "recently_viewed": [
      { "id": 12, "title": "Tips Visual Display Rak", "viewed_at": "2026-11-12T08:30:00+00:00" }
    ],
    "top_weekly": [
      { "id": 5, "name": "Budi Store Crew", "score": 85, "avatar": null }
    ],
    "categories": [...],
    "courses": [...]
  }
  ```

---

### 3.2 Dashboard Pribadi Karyawan (`My Dashboard`)
Menampilkan statistik pencapaian pribadi karyawan: peringkat global & departemen, grafik poin 30 hari, kalender aktivitas 12 minggu, rincian perolehan per aksi, lencana capaian (*badges*), dan riwayat mutasi poin terbaru.
- **URL**: `GET /api/dashboard`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "user": {
      "id": 5,
      "name": "Budi",
      "points": 340,
      "current_streak": 6,
      "longest_streak": 12,
      "level_info": { "level": 3, "name": "Sapling", "next_level": "Grower", "points_to_next": 160, "progress": 60 }
    },
    "rank": 12,
    "department_rank": 3,
    "points_this_week": 45,
    "points_this_month": 180,
    "content_counts": { "draft": 1, "pending": 0, "published": 4, "rejected": 0 },
    "engagement": { "likes": 32, "comments": 14, "views": 240, "shares": 8, "saves": 19 },
    "chart": [{ "date": "2026-11-12", "points": 25 }],
    "activity": { "2026-11-12": 3 },
    "breakdown": { "content_published": 100, "daily_login": 24 },
    "badges": [{ "key": "first_post", "name": "First Seed", "earned": true, "progress": 1, "target": 1 }],
    "courses_completed": 2,
    "recent_points": [
      {
        "id": 142,
        "points": 25,
        "action": "content_published",
        "description": "Konten \"Tips Penataan Kasir Cepat\" dipublikasikan",
        "created_at": "2026-10-28T09:15:00.000000Z"
      }
    ]
  }
  ```

---

### 3.3 Leaderboard Gamifikasi
Peringkat klasemen karyawan berdasarkan perolehan poin, mengikuti batas kuota dan siklus reset yang dikonfigurasi pada *Rank Settings*.
- **URL**: `GET /api/leaderboard`
- **Akses**: `Authenticated`
- **Query Parameter**:
  | Parameter | Tipe Data | Pilihan Nilai | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `period` | string | `all`, `month`, `week` | Periode waktu akumulasi poin (default: `all`). |
  | `department` | string | - | Filter nama departemen karyawan. |

- **Response Sukses (`200 OK`)**:
  ```json
  {
    "period": "all",
    "leaders": [
      {
        "id": 2,
        "name": "Siti Nurhaliza",
        "department": "Store Operations",
        "position": "Category Specialist",
        "points": 1820,
        "score": 1820,
        "current_streak": 14,
        "rank": 1
      }
    ],
    "top_limit": 50,
    "settings": {
      "leaderboard_limit": 50,
      "show_podium": true,
      "show_my_rank_outside": true,
      "reset_frequency": "never",
      "reset_frequency_label": "Sepanjang Waktu (Tanpa Reset)",
      "last_reset_at": null,
      "next_reset_at": null,
      "start_date": null
    },
    "me": { "id": 5, "name": "Andi Saputra", "rank": 12, "score": 340 },
    "departments": ["Store Operations", "Merchandising", "IT"]
  }
  ```

---

## 4. Knowledge Feed & Konten Pengetahuan

### 4.1 Daftar Kategori Konten
Mengambil master 8 kategori berbagi pengetahuan.
- **URL**: `GET /api/categories`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  [
    {
      "id": 1,
      "name": "Best Practice",
      "slug": "best-practice",
      "icon": "Award",
      "color": "#1e8c45",
      "description": "Praktik operasional terbaik di lingkungan kerja."
    },
    {
      "id": 2,
      "name": "Document & SOP",
      "slug": "document-sop",
      "icon": "FileText",
      "color": "#2563eb",
      "description": "Panduan resmi standar operasional."
    }
  ]
  ```

---

### 4.2 Daftar Knowledge Feed (Publikasi)
Mengambil materi pengetahuan yang sudah lolos kurasi tim Reviewer (`published`).
- **URL**: `GET /api/contents`
- **Akses**: `Authenticated`
- **Query Parameter**:
  | Parameter | Tipe Data | Keterangan |
  | :--- | :--- | :--- |
  | `q` | string | Kata kunci pencarian pada judul, isi body, atau tag topik. |
  | `category` | string | Slug kategori (contoh: `best-practice`, `document-sop`). |
  | `community_id` | integer | Filter berdasarkan ID komunitas tertentu. |
  | `author_id` | integer | Filter berdasarkan user author pembuat konten. |
  | `media` | string | Filter lampiran media: `image`, `video`, atau `document`. |
  | `sort` | string | `latest` (terbaru), `popular` (like terbanyak), `trending`. |
  | `per_page` | integer | Jumlah data per halaman (default: 10). |
  | `page` | integer | Nomor halaman pagination. |

- **Response Sukses (`200 OK`)**:
  ```json
  {
    "current_page": 1,
    "data": [
      {
        "id": 18,
        "title": "SOP Penanganan Barang Rusak & Retur Supplier 2026",
        "body": "Berikut langkah-langkah pencatatan barcode barang rusak...",
        "tags": ["SOP", "Gudang", "Inventory"],
        "views_count": 142,
        "likes_count": 28,
        "comments_count": 7,
        "shares_count": 4,
        "saves_count": 15,
        "is_liked": true,
        "is_saved": false,
        "author": {
          "id": 5,
          "name": "Budi Store Crew",
          "avatar": null,
          "position": "Inventory Specialist"
        },
        "media": [
          {
            "id": 34,
            "type": "document",
            "path": "content_media/sop_retur_2026.pdf",
            "original_name": "SOP_Retur_2026.pdf",
            "size": 1542100
          }
        ]
      }
    ],
    "total": 45
  }
  ```

---

### 4.3 Detail Konten
Mengambil artikel lengkap beserta status like/save pembaca dan 4 materi terkait.
- **URL**: `GET /api/contents/{id}`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "content": {
      "id": 18,
      "title": "SOP Penanganan Barang Rusak & Retur Supplier 2026",
      "body": "Penjelasan detail...",
      "tags": ["SOP", "Gudang"],
      "media": [...],
      "is_liked": true,
      "is_saved": false,
      "author": { ... },
      "reviewer": { "id": 2, "name": "Reviewer GrowHub" }
    },
    "related": [ ... ]
  }
  ```

---

### 4.4 Pembuatan Konten Baru (Submit / Draft)
Mengunggah artikel baru lengkap dengan lampiran multi-file (gambar, video, PDF).
- **URL**: `POST /api/contents`
- **Akses**: `Authenticated`
- **Content-Type**: `multipart/form-data`
- **Form Data**:
  | Field | Tipe Data | Wajib | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `title` | string | Ya | Judul materi (5–255 karakter). |
  | `body` | string | Ya | Konten materi (minimal 20 karakter). |
  | `category_id` | integer | Ya | ID kategori yang valid. |
  | `community_id` | integer | Tidak | ID komunitas (opsional). |
  | `tags` | string / JSON | Tidak | Array string tag topik, contoh: `["SOP", "Kasir"]`. |
  | `action` | string | Tidak | `submit` (langsung kirim ke Reviewer) atau `draft`. |
  | `media[]` | file | Tidak | Multi-file lampiran (JPG, PNG, MP4, PDF, DOCX, XLSX, maks. 50 MB per file). |

- **Response Sukses (`201 Created`)**:
  ```json
  {
    "message": "Konten berhasil dikirim untuk direview tim Reviewer.",
    "content": {
      "id": 19,
      "title": "Tips Cepat Closing Kasir",
      "status": "pending"
    }
  }
  ```

---

### 4.5 Update / Edit Konten
- **URL**: `POST /api/contents/{id}` *(Mendukung form-data multi-part)*
- **Akses**: `Authenticated` (Hanya oleh Author atau Admin)
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Konten berhasil diperbarui.",
    "content": { ... }
  }
  ```

---

### 4.6 Hapus Konten
- **URL**: `DELETE /api/contents/{id}`
- **Akses**: `Authenticated` (Author atau Admin)
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Konten berhasil dihapus."
  }
  ```

---

### 4.7 Kontribusi Saya (`My Contents`), Tersimpan (`Saved`) & Riwayat Baca (`History Content`)
- `GET /api/my/contents` : Mengambil seluruh artikel yang pernah dibuat user (Draft, Pending, Published, Rejected).
- `GET /api/my/saved` : Mengambil daftar artikel yang di-bookmark oleh user.
- `GET /api/my/history` : Mengambil daftar artikel berstatus `published` yang pernah dibuka user, diurutkan dari yang terakhir dibuka (`last_viewed_at`). Mendukung query parameter `category` (slug), `q` (pencarian judul/isi/tag), dan `per_page` (default: 12).

---

## 5. Interaksi Sosial & Komentar

### 5.1 Like / Unlike Konten
Toggle suka pada konten yang telah dipublikasikan. Memberikan poin bagi penulis jika belum pernah di-like sebelumnya.
- **URL**: `POST /api/contents/{id}/like`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "liked": true,
    "likes_count": 29
  }
  ```

---

### 5.2 Simpan (Bookmark) Konten
Toggle simpan materi ke daftar *Saved Knowledge*.
- **URL**: `POST /api/contents/{id}/save`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "saved": true,
    "saves_count": 16
  }
  ```

---

### 5.3 Bagikan (Share) Konten
Mencatat statistik pembagian materi ke WhatsApp, Email, atau salin tautan dan memberikan bonus reward poin ke pembagi dan author.
- **URL**: `POST /api/contents/{id}/share`
- **Akses**: `Authenticated`
- **Request Body**:
  ```json
  { "channel": "whatsapp" }
  ```
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "shares_count": 5,
    "points_earned": 1
  }
  ```

---

### 5.4 Thread Diskusi Komentar
- **Ambil Komentar**: `GET /api/contents/{id}/comments`
- **Kirim Komentar**: `POST /api/contents/{id}/comments`
  ```json
  {
    "body": "Terima kasih atas tipsnya, sangat relevan dengan operasional toko kami!",
    "parent_id": null
  }
  ```
- **Hapus Komentar**: `DELETE /api/comments/{id}` *(Hanya pemilik komentar atau Admin)*
  - Apabila **Administrator** menghapus komentar milik pengguna lain (`$comment->user_id !== $user->id`), body wajib menyertakan alasan pelanggaran dari daftar kebijakan aktif:
    ```json
    {
      "ethics_policy_id": 2
    }
    ```
  - Sistem akan menghapus komentar beserta balasannya, mengurangi poin pemilik komentar sebesar `point_deduction`, membuat notifikasi in-app (`policy_violation`), serta mengirim email pemberitahuan pelanggaran.
  - **Contoh Response Moderasi Admin (`200 OK`)**:
    ```json
    {
      "message": "Komentar dihapus, poin Andi Saputra dikurangi 1 Pts, dan email pemberitahuan telah dikirim.",
      "comments_count": 4,
      "points_deducted": 1,
      "email_sent": true,
      "violation_reason": "Konten SARA, Ujaran Kebencian & Pelecehan"
    }
    ```

---

## 6. Komunitas Karyawan (`/api/communities`)

### 6.1 Daftar Komunitas
- **URL**: `GET /api/communities`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  [
    {
      "id": 1,
      "name": "Store Operations Squad",
      "slug": "store-operations-squad",
      "description": "Wadah diskusi kru operasional seluruh cabang.",
      "members_count": 48,
      "contents_count": 32,
      "is_member": true
    }
  ]
  ```

---

### 6.2 Detail Komunitas & Feed Terkait
- **URL**: `GET /api/communities/{slug}`
- **Akses**: `Authenticated`

### 6.3 Buat Komunitas Baru
- **URL**: `POST /api/communities`
- **Akses**: `Authenticated`
- **Request Body** (`application/json`):
  | Parameter | Tipe Data | Wajib | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `name` | string | Ya | Nama komunitas (unik, maks. 80 karakter). |
  | `description` | string | Tidak | Deskripsi singkat komunitas (maks. 500 karakter). |
  | `icon` | string | Tidak | Nama ikon Lucide (default: `Users`, maks. 40 karakter). |
  | `color` | string | Tidak | Kode warna HEX (default: `#1e8c45`, maks. 20 karakter). |
- **Response Sukses (`201 Created`)**: Mengembalikan objek komunitas baru beserta pembuat otomatis bergabung sebagai admin komunitas.

### 6.4 Gabung / Keluar Komunitas (`Join/Leave`)
- **URL**: `POST /api/communities/{id}/join`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "is_member": true,
    "members_count": 49
  }
  ```

---

## 7. Learning Center & Kursus (`/api/courses`)

### 7.1 Daftar Modul Kursus
- **URL**: `GET /api/courses`
- **Akses**: `Authenticated`
- **Query Parameter**: `category`, `q`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "courses": [
      {
        "id": 1,
        "title": "Dasar Standar Visual Merchandising",
        "slug": "dasar-standar-visual-merchandising",
        "category": "Merchandising",
        "level": "Beginner",
        "summary": "Teknik display produk dan penataan etalase promo...",
        "duration_minutes": 15,
        "points_reward": 15,
        "is_completed": false
      }
    ],
    "completed": 3,
    "total": 10
  }
  ```

---

### 7.2 Detail Modul Kursus
- **URL**: `GET /api/courses/{slug}`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**: Mengembalikan detail lengkap materi kursus termasuk `body`, `video_url`, `completions_count`, `is_completed`, dan `points_reward` (mengikuti status aktif aturan `course_completed`).

---

### 7.3 Selesaikan Kursus & Klaim Poin Reward
Menandai kelulusan modul pembelajaran mandiri dan mentransfer poin reward secara instan.
- **URL**: `POST /api/courses/{id}/complete`
- **Akses**: `Authenticated`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Selamat! Materi selesai.",
    "points_earned": 15
  }
  ```

---

## 8. Notifikasi In-App (`/api/notifications`)

| Endpoint | Method | Keterangan |
| :--- | :--- | :--- |
| `/api/notifications` | `GET` | Mengambil 20 notifikasi terbaru user. |
| `/api/notifications/unread-count` | `GET` | Mengambil angka badge notifikasi belum dibaca. |
| `/api/notifications/{id}/read` | `POST` | Menandai satu notifikasi telah dibaca. |
| `/api/notifications/read-all` | `POST` | Menandai seluruh notifikasi telah dibaca. |

---

## 9. Profil Karyawan & Akun

### 9.1 Lihat Profil User Lain
- **URL**: `GET /api/users/{id}`
- **Akses**: `Authenticated`

### 9.2 Perbarui Profil Diri
- **URL**: `PUT /api/profile`
- **Akses**: `Authenticated`
- **Request Body**:
  | Parameter | Tipe Data | Wajib | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `name` | string | Ya | Nama lengkap karyawan (maks. 120 karakter). |
  | `email` | string (email) | Ya | Alamat email aktif & unik (maks. 150 karakter, wajib untuk pengiriman OTP). |
  | `bio` | string | Tidak | Bio singkat (maks. 300 karakter). |
  | `location` | string | Tidak | Lokasi cabang/kantor (maks. 100 karakter). |
  ```json
  {
    "name": "Budi Store Crew",
    "email": "budi.crew@growhub.co.id",
    "location": "Bandung",
    "bio": "Store Crew Cabang Bandung - Passionate in customer experience."
  }
  ```

### 9.3 Unggah Foto Avatar
- **URL**: `POST /api/profile/avatar`
- **Akses**: `Authenticated`
- **Content-Type**: `multipart/form-data`
- **Form Data**: `avatar` (File gambar `image`, ukuran maks. **3 MB** / `3072` KB).

### 9.4 Ubah Password di Profil (Verifikasi OTP Email)
Tersedia khusus untuk pengguna dengan role `admin` atau user berstatus `must_change_password = true` (karyawan reguler diblokir dengan `403 Forbidden`):
1. **Kirim OTP Email**: `POST /api/profile/password/send-otp` *(Rate limit: `throttle:10,1`)*
2. **Verifikasi OTP Email**: `POST /api/profile/password/verify-otp` *(Rate limit: `throttle:15,1`, body: `{ "otp": "123456" }`, mengembalikan `otp_verification_token`)*
3. **Update Password**: `PUT /api/profile/password`
   ```json
   {
     "otp_verification_token": "8x9AbCdEf...random64charToken...",
     "current_password": "PasswordLama#2026",
     "password": "PasswordBaruAdmin#2026",
     "password_confirmation": "PasswordBaruAdmin#2026"
   }
   ```

---

## 10. Layanan Bantuan & FAQ (`/api/support`)

### 10.1 Daftar FAQ Sistem
- **URL**: `GET /api/support/faqs`
- **Akses**: `Authenticated`

### 10.2 Tiket Bantuan (Help Desk)
- **Lihat Riwayat Tiket**: `GET /api/support/tickets`
- **Kirim Tiket Bantuan**: `POST /api/support/tickets`
  ```json
  {
    "subject": "Kendala Upload Video Materi SOP",
    "category": "technical",
    "message": "Video berukuran 40 MB gagal terunggah dengan pesan timeout..."
  }
  ```

---

## 11. Portal Reviewer (`/api/review`)
> [!IMPORTANT]
> Seluruh endpoint di bawah ini diproteksi oleh middleware `auth:sanctum` dan `reviewer`. Hanya pengguna dengan role `reviewer` atau `admin` yang berhak memanggil endpoint ini.

### 11.1 Antrean Validasi Konten (`Validation Queue`)
- **URL**: `GET /api/review/contents`
- **Query Parameter**:
  - `status`: `pending` (antrean tunggu), `published` (disetujui), `rejected` (ditolak), `all`.
  - `q`: Pencarian kata kunci judul.
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "data": { ... },
    "stats": {
      "pending": 4,
      "published": 80,
      "rejected": 5,
      "reviewed_today": 2
    }
  }
  ```

---

### 11.2 Setujui Konten (`Approve`)
Menyetujui materi, mempublikasikan ke Knowledge Feed publik, dan secara otomatis memberikan poin reward kontribusi ke penulis dan reviewer.
- **URL**: `POST /api/review/contents/{id}/approve`
- **Request Body**:
  ```json
  {
    "note": "SOP sangat jelas dan sesuai dengan standar operasional 2026."
  }
  ```
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Konten disetujui dan dipublikasikan.",
    "content": { "id": 19, "status": "published" }
  }
  ```

---

### 11.3 Tolak Konten (`Reject`)
Mengembalikan materi kepada penulis dengan catatan revisi wajib.
- **URL**: `POST /api/review/contents/{id}/reject`
- **Request Body**:
  ```json
  {
    "note": "Mohon lampirkan template formulir retur versi terbaru 2026."
  }
  ```
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Konten ditolak.",
    "content": { "id": 19, "status": "rejected" }
  }
  ```

---

## 12. Admin Console - Manajemen User (`/api/admin/users`)
> [!WARNING]
> Endpoint ini khusus untuk role `admin` guna mengelola direktori karyawan dan keamanan kredensial sistem.

### 12.1 Direktori Karyawan Lengkap
- **URL**: `GET /api/admin/users`
- **Query Parameter**:
  | Parameter | Tipe Data | Keterangan |
  | :--- | :--- | :--- |
  | `q` | string | Pencarian nama, NIP, email, divisi, atau jabatan. |
  | `role` | string | Filter role: `employee`, `reviewer`, `admin`. |
  | `department` | string | Filter divisi / departemen. |
  | `sort` | string | `name`, `nip`, `points`, `created_at`. |
  | `order` | string | `asc` atau `desc`. |
  | `per_page` | integer | Jumlah per halaman (default: 10). |

- **Response Sukses (`200 OK`)**:
  ```json
  {
    "users": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "nip": "1000000001",
          "name": "Super Admin GrowHub",
          "email": "admin@growhub.co.id",
          "role": "admin",
          "department": "IT Operations",
          "points": 5000,
          "is_active": true,
          "must_change_password": false
        }
      ],
      "total": 115
    },
    "departments": ["Store Operations", "Logistics", "IT Operations"],
    "stats": {
      "total_users": 115,
      "total_employees": 105,
      "total_reviewers": 7,
      "total_admins": 3
    }
  }
  ```

---

### 12.2 Daftarkan Karyawan Baru (`Onboarding`)
- **URL**: `POST /api/admin/users`
- **Request Body** (`application/json`):
  | Field | Tipe Data | Wajib | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `nip` | string | Ya | 6–12 digit angka unik NIP. |
  | `name` | string | Ya | Nama lengkap karyawan (2–150 karakter). |
  | `role` | string | Ya | `employee`, `reviewer`, atau `admin`. |
  | `email` | string | Tidak | Email korporat unik (opsional). |
  | `password` | string | Tidak | Default jika kosong diset sama dengan NIP. |
  | `department` | string | Tidak | Divisi (default: `Store Operations`). |
  | `position` | string | Tidak | Posisi/Jabatan (default: `Team Member`). |
  | `location` | string | Tidak | Cabang Toko / Head Office. |
  | `points` | integer | Tidak | Bonus poin selamat datang (onboarding points). |

- **Contoh Request Body**:
  ```json
  {
    "nip": "2026010088",
    "name": "Rian Kurniawan",
    "email": "rian.kurniawan@growhub.co.id",
    "role": "employee",
    "department": "Store Operations",
    "position": "Cashier Specialist",
    "location": "Store 04 Surabaya",
    "points": 50
  }
  ```
- **Response Sukses (`201 Created`)**:
  ```json
  {
    "message": "User 'Rian Kurniawan' (NIP: 2026010088) created successfully.",
    "user": { ... }
  }
  ```

---

### 12.3 Update Akun Karyawan
- **URL**: `PUT /api/admin/users/{id}`
- **Request Body**:
  ```json
  {
    "name": "Rian Kurniawan",
    "role": "reviewer",
    "department": "Store Operations",
    "position": "Store Supervisor",
    "location": "Store 04 Surabaya",
    "is_active": true
  }
  ```
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "User 'Rian Kurniawan' (NIP: 2026010088) updated successfully.",
    "user": { ... }
  }
  ```

---

### 12.4 Reset Password Terpusat oleh Admin
Mereset password user kembali ke NIP default dan otomatis menandai `must_change_password = true` sehingga user diwajibkan menyetel password baru saat login berikutnya.
- **URL**: `POST /api/admin/users/{id}/reset-password`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Password user 'Rian Kurniawan' (NIP: 2026010088) berhasil direset ke default (NIP). User wajib mengganti password saat login berikutnya.",
    "user": {
      "id": 14,
      "nip": "2026010088",
      "must_change_password": true
    }
  }
  ```

---

### 12.5 Hapus Akun Karyawan Permanen
Menghapus akun karyawan dari sistem. Dilindungi oleh proteksi anti-hapus akun sendiri.
- **URL**: `DELETE /api/admin/users/{id}`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "User 'Rian Kurniawan' (NIP: 2026010088) deleted successfully."
  }
  ```
- **Response Error Hapus Diri Sendiri (`422 Unprocessable Content`)**:
  ```json
  {
    "message": "You cannot delete your own account while logged in."
  }
  ```

---

## 13. Admin Console - Pengaturan Poin Gamifikasi (`/api/admin/point-rules`)

Endpoint ini khusus untuk peran **Administrator** untuk mengonfigurasi skema gamifikasi, mengatur besaran poin per aksi, mengatur kuota batas harian (*daily caps*), dan menyalakan/mematikan (*on/off toggle*) sistem poin untuk tindakan tertentu kapan saja.

### 13.1 Ambil Seluruh Aturan Poin & Statistik
- **URL**: `GET /api/admin/point-rules`
- **Query Params**:
  * `category` *(optional)*: Filter berdasarkan kategori (`content`, `social`, `learning`, `general`, `review`).
  * `is_active` *(optional)*: Filter boolean status aktivasi (`true` atau `false`).
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "rules": [
      {
        "id": 1,
        "action": "daily_login",
        "name": "Login Harian (Streak)",
        "description": "Poin apresiasi untuk aktivitas login pertama karyawan setiap harinya.",
        "category": "general",
        "points": 2,
        "daily_cap": 1,
        "is_active": true,
        "sort_order": 1
      },
      {
        "id": 4,
        "action": "content_viewed",
        "name": "Membaca Konten",
        "description": "Poin membaca dan mempelajari artikel knowledge sharing rekan kerja.",
        "category": "content",
        "points": 1,
        "daily_cap": 10,
        "is_active": false,
        "sort_order": 4
      }
    ],
    "stats": {
      "total_rules": 12,
      "active_rules": 11,
      "inactive_rules": 1,
      "total_points_distributed": 2450,
      "categories": ["general", "content", "social", "review", "learning"]
    }
  }
  ```

---

### 13.2 Perbarui Aturan Poin Tunggal
Mengubah besaran poin, batas kuota harian, atau status aktivasi satu tindakan tertentu.
- **URL**: `PUT /api/admin/point-rules/{id}`
- **Request Body**:
  ```json
  {
    "points": 5,
    "daily_cap": 10,
    "is_active": true
  }
  ```
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Pengaturan aturan 'Membaca Konten' berhasil diperbarui.",
    "rule": {
      "id": 4,
      "action": "content_viewed",
      "points": 5,
      "daily_cap": 10,
      "is_active": true
    }
  }
  ```

---

### 13.3 Saklar On/Off Aktivasi Tindakan Poin (1-Klik Toggle)
Menyalakan atau mematikan sistem poin untuk tindakan tertentu secara instan. Jika dinonaktifkan, aksi tersebut akan otomatis menghasilkan 0 poin.
- **URL**: `POST /api/admin/point-rules/{id}/toggle`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Sistem poin untuk tindakan 'Membaca Konten' berhasil diaktifkan.",
    "rule": {
      "id": 4,
      "action": "content_viewed",
      "is_active": true
    }
  }
  ```

---

### 13.4 Simpan Pembaruan Aturan Massal (*Batch Save*)
Menyimpan konfigurasi banyak aturan sekaligus dalam satu kali klik.
- **URL**: `PUT /api/admin/point-rules/batch`
- **Request Body**:
  ```json
  {
    "rules": [
      { "id": 1, "points": 2, "daily_cap": 1, "is_active": true },
      { "id": 2, "points": 5, "daily_cap": null, "is_active": true },
      { "id": 4, "points": 1, "daily_cap": 10, "is_active": false }
    ]
  }
  ```
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Semua konfigurasi sistem poin berhasil disimpan.",
    "rules": [ ... ]
  }
  ```

---

### 13.5 Reset Aturan ke Nilai Default Bawaan
Mengembalikan seluruh bobot poin, daily cap, dan status aktivasi ke standar bawaan konfigurasi GrowHub.
- **URL**: `POST /api/admin/point-rules/reset-defaults`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Konfigurasi sistem poin telah direset ke nilai bawaan sistem.",
    "rules": [ ... ]
  }
  ```

---

### 13.6 Nonaktifkan Semua Aturan Poin Sekaligus
Menonaktifkan seluruh aturan sistem poin (mengubah `is_active = false` untuk semua aturan) dalam satu request.
- **URL**: `POST /api/admin/point-rules/disable-all`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Semua aturan sistem poin berhasil dinonaktifkan.",
    "rules": [ ... ]
  }
  ```

---

### 13.7 Aktifkan Semua Aturan Poin Sekaligus
Mengaktifkan kembali seluruh aturan sistem poin (mengubah `is_active = true` untuk semua aturan) dalam satu request.
- **URL**: `POST /api/admin/point-rules/enable-all`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Semua aturan sistem poin berhasil diaktifkan.",
    "rules": [ ... ]
  }
  ```

---

## 14. Admin Console - Pengaturan Jenjang Level (`/api/admin/level-rules`)
*Akses: Khusus Role `admin`*

### 14.1 Daftar Aturan Level & Distribusi Karyawan
- **URL**: `GET /api/admin/level-rules`
- **Response Sukses (`200 OK`)**: Mengembalikan `levels` (beserta `user_count` aktif per tier) dan `stats` (`total_levels`, `active_levels`, `inactive_levels`, `max_points`, `total_users`).

### 14.2 Tambah, Edit, Toggle & Hapus Tier Level
- **Tambah Tier Baru**: `POST /api/admin/level-rules` (`201 Created`)
- **Update Tier Tunggal**: `PUT /api/admin/level-rules/{levelRule}`
- **Toggle Aktif/Nonaktif**: `POST /api/admin/level-rules/{levelRule}/toggle`
- **Simpan Perubahan Massal**: `PUT /api/admin/level-rules/batch` (body: `{ "levels": [{ "id": 1, "level": 1, "name": "Seedling", "min_points": 0, ... }] }`)
- **Reset ke 8 Level Default**: `POST /api/admin/level-rules/reset-defaults`
- **Hapus Tier**: `DELETE /api/admin/level-rules/{levelRule}`
- **Parameter Validasi (`POST` / `PUT`)**: `level` (int 1–100), `name` (string max 100), `min_points` (int 0–1000000), `description` (nullable max 500), `badge_icon` (nullable max 50), `badge_color` (nullable max 50), `is_active` (boolean).

---

## 15. Admin Console - Pengaturan Batas Ranking & Reset (`/api/admin/rank-settings`)
*Akses: Khusus Role `admin`*

### 15.1 Ambil Pengaturan Ranking & Simulasi Preview
- **URL**: `GET /api/admin/rank-settings`
- **Response Sukses (`200 OK`)**: Mengembalikan `settings`, `stats` (`current_leaderboard_limit`, `current_home_limit`, `show_podium`, `show_my_rank_outside`, `reset_frequency`, `reset_frequency_label`, `last_reset_at`, `next_reset_at`, `leaderboard_start_date`, `total_active_employees`, `total_employees_with_points`), serta `preview_leaders`.

### 15.2 Simpan Pengaturan Ranking & Jadwal Reset
- **URL**: `PUT /api/admin/rank-settings`
- **Request Body** (`application/json`):
  | Parameter | Tipe Data | Wajib | Validasi & Keterangan |
  | :--- | :--- | :--- | :--- |
  | `leaderboard_limit` | integer | Ya | Rentang `3` – `500` (batas tampil tabel Leaderboard). |
  | `home_widget_limit` | integer | Ya | Rentang `1` – `20` (batas widget *Top Weekly* di Home). |
  | `show_podium` | boolean | Ya | Tampilkan visual panggung podium Top 3. |
  | `show_my_rank_outside` | boolean | Ya | Tampilkan peringkat aktual di sticky footer bila di luar kuota Top X. |
  | `reset_frequency` | string | Tidak | Salah satu dari: `never`, `weekly`, `monthly`, `quarterly`, `yearly`. |

### 15.3 Reset Manual & Reset ke Default
- **Reset Leaderboard Sekarang**: `POST /api/admin/rank-settings/manual-reset` *(Memulai perhitungan periode leaderboard dari timestamp saat ini)*
- **Batalkan Reset Manual**: `POST /api/admin/rank-settings/clear-manual-reset`
- **Kembalikan Default**: `POST /api/admin/rank-settings/reset-defaults`

---

## 16. Kebijakan Etika & Moderasi (`/api/ethics-policies`, `/api/admin/policy-configs`)

### 16.1 Ambil Daftar Kebijakan Etika Aktif (User Modal & Moderasi)
- **URL**: `GET /api/ethics-policies`
- **Akses**: `Authenticated` (`auth:sanctum`)
- **Response Sukses (`200 OK`)**: Mengembalikan `{ "policies": [ ... ] }` diurutkan berdasarkan `sort_order`.

### 16.2 Manajemen Policy & Validation Config (Admin Only)
*Akses: Khusus Role `admin`*
- **Daftar & Statistik**: `GET /api/admin/policy-configs` *(Mengembalikan daftar `policies` dan `stats: { total_policies, min_deduction, max_deduction, avg_deduction, deduction_range }`)*
- **Tambah Aturan Baru**: `POST /api/admin/policy-configs` (`201 Created`, body: `title` max 160, `description` max 1000, `point_deduction` integer min 1, `severity` string low|medium|high, `sort_order` nullable 0–1000)
- **Update Aturan Tunggal**: `PUT /api/admin/policy-configs/{ethicsPolicy}`
- **Simpan Perubahan Massal**: `PUT /api/admin/policy-configs/batch` *(Body: `{ "policies": [{ "id": 1, "title": "...", "description": "...", "point_deduction": 5, "severity": "high", "sort_order": 1 }] }`)*
- **Hapus Aturan**: `DELETE /api/admin/policy-configs/{ethicsPolicy}` *(Mengembalikan `422` jika hanya tersisa 1 aturan di sistem)*

---

## 17. Kontak Helper & Support Layanan Karyawan (`/api/support/helpers`, `/api/admin/helper-contacts`)

### 17.1 Ambil Daftar Kontak Helper Aktif (Guest & Authenticated)
- **URL**: `GET /api/support/helpers` (Alias: `GET /api/helper-contacts`)
- **Akses**: `Public` (dapat diakses tanpa login pada Halaman Login, serta dapat diakses oleh user terautentikasi pada Halaman Help & Support)
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "helpers": [
      {
        "id": 1,
        "name": "IT Helpdesk & System Support",
        "role_or_title": "Administrator Sistem GrowHub",
        "channel": "whatsapp",
        "contact_value": "+62 812-8899-7700",
        "available_hours": "Senin - Jumat, 08:00 - 17:00 WIB",
        "description": "Bantuan kendala login NIP, lupa password, reset akun, dan error teknis GrowHub.",
        "is_active": true,
        "sort_order": 1
      }
    ]
  }
  ```

### 17.2 Manajemen Kontak Helper (Admin Only)
*Akses: Khusus Role `admin` (`auth:sanctum`)*
- **Daftar & Statistik Kontak**: `GET /api/admin/helper-contacts` *(Mengembalikan seluruh kontak aktif & nonaktif beserta metrik `total_helpers`, `active_helpers`, `inactive_helpers`)*
- **Tambah Kontak Helper Baru**: `POST /api/admin/helper-contacts`
  - Body: `name` (string max 120, wajib), `role_or_title` (string nullable max 120), `channel` (string wajib: `whatsapp|email|phone|telegram|other`), `contact_value` (string max 160, wajib), `available_hours` (string nullable max 120), `description` (string nullable max 1000), `is_active` (boolean, opsional default true), `sort_order` (integer opsional 0–1000).
- **Update Kontak Helper**: `PUT /api/admin/helper-contacts/{helperContact}`
- **Toggle Status Aktif/Nonaktif**: `POST /api/admin/helper-contacts/{helperContact}/toggle`
- **Hapus Kontak Helper**: `DELETE /api/admin/helper-contacts/{helperContact}`
- **Reset ke Kontak Bawaan Default**: `POST /api/admin/helper-contacts/reset-defaults`

---

## 18. Admin Console - Pengaturan Email & Server SMTP (`/api/admin/mail-settings`)
*Akses: Khusus Role `admin` (`auth:sanctum`)*

Modul ini memungkinkan Administrator untuk melihat konfigurasi runtime dan file `.env`, memperbarui kredensial SMTP server secara dinamis (yang otomatis disimpan ke database `mail_settings` dan file `backend/.env`), serta melakukan pengujian kirim email (*SMTP test connection*).

### 18.1 Ambil Konfigurasi Email & Snapshot .env
- **URL**: `GET /api/admin/mail-settings`
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "setting": {
      "mail_mailer": "smtp",
      "mail_scheme": "smtps",
      "mail_host": "smtp-1.rcoid.com",
      "mail_port": 465,
      "mail_username": "noreply.pepito@rcoid.com",
      "has_password": true,
      "mail_from_address": "noreply.pepito@rcoid.com",
      "mail_from_name": "GrowHub- ${APP_NAME}",
      "updated_at": "2026-11-24T17:50:00+08:00",
      "updated_by_name": "Super Admin"
    },
    "env_snapshot": {
      "MAIL_MAILER": "smtp",
      "MAIL_SCHEME": "smtps",
      "MAIL_HOST": "smtp-1.rcoid.com",
      "MAIL_PORT": 465,
      "MAIL_USERNAME": "noreply.pepito@rcoid.com",
      "MAIL_FROM_ADDRESS": "noreply.pepito@rcoid.com",
      "MAIL_FROM_NAME": "GrowHub- ${APP_NAME}"
    }
  }
  ```

### 18.2 Simpan & Perbarui Konfigurasi SMTP
Menyimpan konfigurasi baru ke database `mail_settings`, memperbarui baris `MAIL_*` pada file `backend/.env`, menyinkronkan runtime config di memori Laravel, serta membersihkan cache konfigurasi (`Artisan config:clear`).
- **URL**: `PUT /api/admin/mail-settings`
- **Request Body** (`application/json`):
  | Parameter | Tipe Data | Wajib | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `mail_mailer` | string | Ya | Pilihan: `smtp`, `sendmail`, `log`, `array`. Default: `smtp`. |
  | `mail_scheme` | string | Tidak | Pilihan: `smtps`, `smtp`, `tls`, `ssl`, atau kosongkan. |
  | `mail_host` | string | Ya | Host server SMTP (maks. 190 karakter, misal: `smtp-1.rcoid.com`). |
  | `mail_port` | integer | Ya | Port server SMTP (1–65535, misal: `465` untuk SSL/SMTPS atau `587` untuk TLS). |
  | `mail_username` | string | Tidak | Username autentikasi akun email server (maks. 190 karakter). |
  | `mail_password` | string | Tidak | Password SMTP baru. **Kosongkan jika ingin mempertahankan password yang sudah ada**. |
  | `mail_from_address` | string | Ya | Alamat email resmi pengirim notifikasi sistem (format email valid). |
  | `mail_from_name` | string | Ya | Nama pengirim yang tampil di inbox penerima (misal: `GrowHub- ${APP_NAME}`). |

- **Response Sukses (`200 OK`)**:
  ```json
  {
    "message": "Konfigurasi SMTP berhasil disimpan dan file .env telah diperbarui.",
    "env_updated": true,
    "setting": {
      "mail_mailer": "smtp",
      "mail_scheme": "smtps",
      "mail_host": "smtp-1.rcoid.com",
      "mail_port": 465,
      "mail_username": "noreply.pepito@rcoid.com",
      "has_password": true,
      "mail_from_address": "noreply.pepito@rcoid.com",
      "mail_from_name": "GrowHub- ${APP_NAME}",
      "updated_at": "2026-11-24T17:55:00+08:00",
      "updated_by_name": "Super Admin"
    }
  }
  ```

### 18.3 Uji Coba Pengiriman Email (SMTP Test)
Mengirim email pengujian ke alamat tujuan untuk memvalidasi apakah koneksi socket port, hostname, autentikasi SSL/TLS, dan kredensial akun berjalan normal.
- **URL**: `POST /api/admin/mail-settings/test`
- **Request Body** (`application/json`):
  ```json
  {
    "recipient_email": "admin@rcoid.com"
  }
  ```
- **Response Sukses (`200 OK`)**:
  ```json
  {
    "success": true,
    "message": "Email pengujian berhasil dikirim ke admin@rcoid.com!"
  }
  ```
- **Response Gagal Koneksi (`500 Internal Server Error`)**:
  ```json
  {
    "success": false,
    "message": "Gagal mengirim email pengujian: Connection could not be established with host smtp-1.rcoid.com:465 ..."
  }
  ```

---

## 19. Tabel Kode Respons HTTP

| Kode Status | Keterangan | Penjelasan dalam Sistem GrowHub |
| :--- | :--- | :--- |
| **`200 OK`** | Berhasil | Request berhasil diproses dan mengembalikan payload data. |
| **`201 Created`** | Berhasil Dibuat | Data baru (user, konten, komentar, komunitas, level, kebijakan, tiket) berhasil dibuat. |
| **`400 Bad Request`** | Permintaan Tidak Valid | Format payload atau parameter HTTP tidak valid sebelum pemrosesan validasi bisnis. |
| **`401 Unauthorized`** | Tidak Terautentikasi | Token Bearer Sanctum hilang, kedaluwarsa, atau tidak valid. |
| **`403 Forbidden`** | Dilarang | Pengguna tidak memiliki hak akses role (misal non-reviewer mengakses endpoint kurasi, atau user reguler mengakses reset password mandiri). |
| **`404 Not Found`** | Tidak Ditemukan | ID resource konten, user, level, kebijakan, atau course tidak ditemukan di database. |
| **`405 Method Not Allowed`** | Metode Tidak Diizinkan | HTTP method tidak didukung endpoint atau terblokir konfigurasi handler web server (misal IIS FastCGI belum mengizinkan `OPTIONS`/`PUT`/`DELETE`). |
| **`408 Request Timeout`** | Waktu Permintaan Habis | Koneksi ke backend melebihi batas tunggu (timeout). |
| **`413 Content Too Large`** | Ukuran Berkas Terlalu Besar | Ukuran unggahan berkas melebihi batas maksimal server atau validasi Laravel (maks. 50 MB konten, 3 MB avatar). |
| **`419 Page Expired / CSRF Mismatch`** | Sesi Kedaluwarsa | Token sesi atau CSRF tidak cocok / kedaluwarsa. |
| **`422 Unprocessable Content`** | Validasi Gagal | Parameter request tidak memenuhi aturan validasi Laravel (contoh: NIP duplikat, OTP salah/kedaluwarsa, password kurang dari 8 karakter, alasan moderasi belum dipilih). |
| **`429 Too Many Requests`** | Terlalu Banyak Request | Terkena limit throttling (contoh: login `30/menit`, kirim OTP `10/menit` atau cooldown 60 detik, verifikasi OTP `15/menit`). |
| **`500 Internal Server Error`** | Kesalahan Server | Terjadi galat internal pada runtime PHP, pengiriman SMTP, atau query database. |
| **`502 Bad Gateway` / `503 Service Unavailable` / `504 Gateway Timeout`** | Layanan / Gateway Bermasalah | Reverse proxy (IIS/Nginx) gagal menjangkau upstream PHP-FPM/FastCGI atau layanan sedang maintenance/overload. |
