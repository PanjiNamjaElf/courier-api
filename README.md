# Courier API

REST API untuk master data kurir, dibuat dengan Laravel 13. Hanya API (tanpa UI) dan tanpa autentikasi.

Repository: <https://github.com/PanjiNamjaElf/courier-api>

Fitur:

- CRUD kurir: index, show, store, update, destroy.
- Index mendukung pagination, urutan default berdasarkan nama, urutan berdasarkan tanggal pendaftaran, pencarian nama multi-kata, dan filter beberapa level sekaligus.
- Validasi input lengkap dengan pesan Bahasa Indonesia.
- Feature test untuk semua endpoint.
- Postman Collection v2.1 di folder `postman/`.

## Daftar Isi

- [Kebutuhan](#kebutuhan)
- [Instalasi](#instalasi)
- [Konfigurasi Database](#konfigurasi-database)
- [Migration dan Seeder](#migration-dan-seeder)
- [Menjalankan Server](#menjalankan-server)
- [Menjalankan Test](#menjalankan-test)
- [Struktur Data Courier](#struktur-data-courier)
- [Daftar Endpoint](#daftar-endpoint)
- [Parameter Query Index](#parameter-query-index)
- [Aturan Validasi](#aturan-validasi)
- [Contoh Request dan Response](#contoh-request-dan-response)
- [Postman Collection](#postman-collection)
- [Struktur Kode](#struktur-kode)
- [Asumsi dan Keputusan Desain](#asumsi-dan-keputusan-desain)

## Kebutuhan

- PHP 8.3 atau lebih baru, dengan ekstensi `pdo_sqlite` (atau `pdo_mysql` bila memakai MySQL), `mbstring`, `xml`, `ctype`, `fileinfo`
- Composer 2
- SQLite 3 (default) atau MySQL 8

## Instalasi

```bash
git clone https://github.com/PanjiNamjaElf/courier-api.git courier-api
cd courier-api
composer install
cp .env.example .env
php artisan key:generate
```

## Konfigurasi Database

### SQLite (default)

Tidak perlu server database. Buat file database kosong:

```bash
touch database/database.sqlite
```

`.env` bawaan sudah memakai `DB_CONNECTION=sqlite`.

### MySQL (opsional)

1. Buat database, misalnya `courier_api`.
2. Ubah bagian database di `.env`:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=courier_api
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. Isi `DB_USERNAME` dan `DB_PASSWORD` sesuai server MySQL Anda.

## Migration dan Seeder

```bash
php artisan migrate --seed
```

Seeder membuat 4 kurir contoh (termasuk `Budiono Hadi Agung`) dan 20 kurir acak, cukup untuk mencoba pencarian, filter, dan pagination.

## Menjalankan Server

```bash
php artisan serve
```

API tersedia di `http://127.0.0.1:8000/api`.

## Menjalankan Test

Test memakai SQLite in-memory, jadi tidak perlu menyiapkan database apa pun.

```bash
php artisan test
```

Dengan laporan coverage (butuh ekstensi `pcov` atau `xdebug`):

```bash
php artisan test --coverage
```

Cakupan test ada di `tests/Feature`:

| File | Yang diuji |
|---|---|
| `CourierCrudTest.php` | Store (data tersimpan di database, default tanggal pendaftaran, normalisasi input), show, update PUT dan PATCH (data berubah di database), destroy (data hilang dari database), 404, dan semua kasus input tidak valid |
| `CourierIndexTest.php` | Pagination, urutan default nama, urutan tanggal pendaftaran, pencarian multi-kata, filter level, kombinasi semuanya, dan parameter query tidak valid |

## Struktur Data Courier

Tabel `couriers`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `name` | varchar(100) | Nama lengkap kurir, ber-index |
| `phone` | varchar(20) | Nomor HP Indonesia, unik |
| `email` | varchar(255), nullable | Opsional, unik |
| `level` | tinyint unsigned | Level kurir 1 sampai 5, ber-index |
| `registered_at` | date | Tanggal kurir didaftarkan, ber-index |
| `created_at`, `updated_at` | timestamp | Waktu data dibuat dan diubah |

`registered_at` sengaja dipisah dari `created_at`: tanggal pendaftaran adalah data bisnis yang bisa diisi mundur (misalnya saat memasukkan data kurir lama), sedangkan `created_at` adalah waktu baris dibuat di sistem.

## Daftar Endpoint

Base URL: `http://127.0.0.1:8000/api`

| Method | Endpoint | Keterangan | Respons sukses |
|---|---|---|---|
| `GET` | `/couriers` | Daftar kurir (pagination, search, filter, sort) | `200` |
| `POST` | `/couriers` | Tambah kurir | `201` |
| `GET` | `/couriers/{id}` | Detail kurir | `200` |
| `PUT` / `PATCH` | `/couriers/{id}` | Ubah kurir (field yang tidak dikirim tidak berubah) | `200` |
| `DELETE` | `/couriers/{id}` | Hapus kurir secara permanen | `204` tanpa body |

Respons error:

| Status | Kapan | Bentuk body |
|---|---|---|
| `404` | Kurir tidak ditemukan | `{ "message": "Data tidak ditemukan." }` |
| `422` | Input atau parameter query tidak valid | `{ "message": "...", "errors": { "field": ["..."] } }` |

Semua respons berformat JSON, termasuk error, meskipun request tidak mengirim header `Accept: application/json`.

## Parameter Query Index

| Parameter | Default | Nilai yang diterima | Contoh |
|---|---|---|---|
| `search` | - | Teks maksimal 100 karakter: huruf, spasi, titik, apostrof, tanda hubung | `search=budi+agung` |
| `level` | - | Angka 1 sampai 5, dipisah koma | `level=2,3` |
| `sort` | `name` | `name` atau `registered_at` | `sort=registered_at` |
| `direction` | `asc` | `asc` atau `desc` | `direction=desc` |
| `per_page` | `15` | 1 sampai 100 | `per_page=10` |
| `page` | `1` | 1 atau lebih | `page=2` |

Perilaku:

- **Pencarian multi-kata.** Kata kunci dipecah per spasi (`+` di URL berarti spasi). Setiap kata harus muncul di nama, di bagian mana saja, dengan urutan bebas, tidak peka huruf besar/kecil. `?search=budi+agung` menemukan `Budiono Hadi Agung` karena nama itu mengandung `budi` (dalam "Budiono") dan `agung`, tetapi tidak menemukan `Budi Santoso` karena tidak mengandung `agung`.
- **Filter level.** `?level=2,3` hanya mengembalikan kurir level 2 atau 3. Satu level saja juga boleh: `?level=4`.
- **Urutan.** Default berdasarkan nama A-Z. `?sort=registered_at&direction=desc` mengurutkan dari tanggal pendaftaran terbaru. Data dengan nilai urutan yang sama diurutkan berdasarkan `id` agar urutan antar halaman stabil.
- **Pagination.** Link `links.next`, `links.prev`, dan lainnya ikut membawa parameter `search`, `level`, `sort`, dan `direction`.
- **Parameter tidak valid** (misalnya `level=2,9`, `sort=email`, `per_page=500`) dijawab `422` beserta pesan error, bukan diabaikan diam-diam.

Semua parameter bisa digabung:

```text
GET /api/couriers?search=budi&level=2,3&sort=registered_at&direction=desc&per_page=10
```

## Aturan Validasi

| Field | Store (`POST`) | Update (`PUT`/`PATCH`) |
|---|---|---|
| `name` | Wajib, teks 3-100 karakter, hanya huruf, spasi, titik, apostrof, tanda hubung | Opsional; bila dikirim, aturan sama dan tidak boleh kosong |
| `phone` | Wajib, nomor HP Indonesia (`08…`, `628…`, atau `+628…`, contoh `081234567890`), unik | Opsional; bila dikirim, aturan sama, boleh sama dengan nomor milik kurir itu sendiri |
| `email` | Opsional, format email valid, maksimal 255 karakter, unik | Opsional; boleh dikosongkan (`null`), boleh sama dengan email milik kurir itu sendiri |
| `level` | Wajib, bilangan bulat 1 sampai 5 | Opsional; bila dikirim, aturan sama dan tidak boleh kosong |
| `registered_at` | Opsional, format `YYYY-MM-DD`, tidak boleh lewat dari hari ini. Bila kosong, diisi tanggal hari ini | Opsional; bila dikirim, aturan sama dan tidak boleh kosong |

Sebelum divalidasi, spasi di awal/akhir semua teks dibuang, spasi ganda di `name` dirapikan, dan `email` diubah ke huruf kecil. Field lain di luar tabel di atas diabaikan.

## Contoh Request dan Response

### Daftar kurir dengan pencarian dan filter level

```bash
curl "http://127.0.0.1:8000/api/couriers?search=budi+agung&level=2,3"
```

```json
{
    "data": [
        {
            "id": 1,
            "name": "Budiono Hadi Agung",
            "phone": "081200000001",
            "email": "budiono@example.com",
            "level": 3,
            "registered_at": "2025-03-10",
            "created_at": "2026-11-24T03:19:20.000000Z",
            "updated_at": "2026-11-24T03:19:20.000000Z"
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8000/api/couriers?search=budi%20agung&level=2%2C3&page=1",
        "last": "http://127.0.0.1:8000/api/couriers?search=budi%20agung&level=2%2C3&page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "links": ["..."],
        "path": "http://127.0.0.1:8000/api/couriers",
        "per_page": 15,
        "to": 1,
        "total": 1
    }
}
```

`meta.links` berisi daftar tautan per halaman (dipersingkat di contoh ini).

### Tambah kurir

```bash
curl -X POST "http://127.0.0.1:8000/api/couriers" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dewi Lestari",
    "phone": "081398765432",
    "email": "dewi@example.com",
    "level": 2,
    "registered_at": "2026-11-01"
  }'
```

Respons `201 Created`:

```json
{
    "data": {
        "id": 25,
        "name": "Dewi Lestari",
        "phone": "081398765432",
        "email": "dewi@example.com",
        "level": 2,
        "registered_at": "2026-11-01",
        "created_at": "2026-11-24T03:19:32.000000Z",
        "updated_at": "2026-11-24T03:19:32.000000Z"
    }
}
```

### Detail kurir

```bash
curl "http://127.0.0.1:8000/api/couriers/1"
```

Respons `200 OK`: objek `data` seperti di atas.

### Ubah sebagian data kurir

```bash
curl -X PATCH "http://127.0.0.1:8000/api/couriers/25" \
  -H "Content-Type: application/json" \
  -d '{"level": 5}'
```

Respons `200 OK`: objek `data` dengan `level` yang baru. `PUT` menerima body yang sama dengan `POST`.

### Hapus kurir

```bash
curl -X DELETE "http://127.0.0.1:8000/api/couriers/25"
```

Respons `204 No Content` tanpa body.

### Input tidak valid

```bash
curl -X POST "http://127.0.0.1:8000/api/couriers" \
  -H "Content-Type: application/json" \
  -d '{"name": "Dewi 99", "phone": "12345", "email": "bukan-email", "level": 7, "registered_at": "2999-01-01"}'
```

Respons `422 Unprocessable Content`:

```json
{
    "message": "Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung. (dan 4 kesalahan lainnya)",
    "errors": {
        "name": ["Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung."],
        "phone": ["Nomor HP harus berupa nomor HP Indonesia, contoh 081234567890 atau +6281234567890."],
        "email": ["Email harus berupa alamat email yang valid."],
        "level": ["Level harus bernilai antara 1 sampai 5."],
        "registered_at": ["Tanggal pendaftaran harus berupa tanggal sebelum atau sama dengan hari ini."]
    }
}
```

### Kurir tidak ditemukan

```bash
curl "http://127.0.0.1:8000/api/couriers/999999"
```

Respons `404 Not Found`:

```json
{ "message": "Data tidak ditemukan." }
```

## Postman Collection

File: `postman/Courier API.postman_collection.json` (format Postman Collection v2.1).

1. Jalankan server: `php artisan serve` (dan pastikan sudah `php artisan migrate --seed`).
2. Di Postman, klik **Import** lalu pilih file collection di atas.
3. Buka collection **Courier API**, tab **Variables**. Pastikan `base_url` bernilai `http://127.0.0.1:8000/api`, atau sesuaikan dengan alamat server Anda.
4. Jalankan request satu per satu, atau semuanya lewat **Run collection** (Collection Runner) sesuai urutan di folder **Couriers**.

Isi collection:

- 7 request index: default, pagination, urut tanggal pendaftaran, pencarian multi-kata, filter level, kombinasi, dan contoh query tidak valid.
- Create, create tidak valid, show, update `PUT`, update `PATCH`, delete, dan show untuk kurir yang tidak ada.
- Setiap request punya contoh respons (tab **Examples**) dan test script sederhana (cek status code dan isi respons).
- **Create courier** membuat nomor HP acak lewat pre-request script, jadi bisa dijalankan berulang tanpa bentrok aturan unik. Id kurir yang baru dibuat disimpan otomatis ke variable `courier_id` dan dipakai oleh request Show, Update, dan Delete.

Collection tidak berisi credential atau data rahasia.

## Struktur Kode

```text
app/
├── Http/
│   ├── Controllers/Api/CourierController.php   # 5 action CRUD
│   ├── Requests/Courier/
│   │   ├── IndexCourierRequest.php             # validasi parameter query index
│   │   ├── StoreCourierRequest.php             # validasi tambah kurir
│   │   └── UpdateCourierRequest.php            # validasi ubah kurir (parsial)
│   └── Resources/CourierResource.php           # bentuk JSON kurir
└── Models/Courier.php                          # model, konstanta level, scope pencarian & filter level
bootstrap/app.php                               # registrasi routes/api.php, error JSON & pesan 404
database/
├── factories/CourierFactory.php
├── migrations/2026_11_24_031321_create_couriers_table.php
└── seeders/CourierSeeder.php
lang/id/validation.php, lang/id.json            # pesan Bahasa Indonesia
routes/api.php                                  # Route::apiResource('couriers', ...)
tests/Feature/CourierCrudTest.php
tests/Feature/CourierIndexTest.php
postman/Courier API.postman_collection.json
```

## Asumsi dan Keputusan Desain

- Tanpa autentikasi karena tidak diminta brief. Sanctum (`php artisan install:api`) sengaja tidak dipasang; `routes/api.php` didaftarkan langsung di `bootstrap/app.php`.
- `PUT` dan `PATCH` sama-sama update parsial. Field yang tidak dikirim tidak berubah.
- Hapus bersifat permanen (tanpa soft delete).
- Pencarian memakai `LIKE` per kata (`%kata%`), cukup untuk skala data tes ini. Untuk data sangat besar, full-text search bisa dipertimbangkan.
- Karakter `%` dan `_` tidak diterima di pencarian, sehingga tidak bisa dipakai sebagai wildcard.
- Nomor HP disimpan apa adanya (tidak dinormalisasi), jadi `0812…` dan `+62812…` dianggap nomor yang berbeda.
- `composer.json` mengunci `config.platform.php` ke 8.3, sehingga `composer.lock` tetap bisa dipasang di PHP 8.3 walaupun project dikembangkan di PHP yang lebih baru.
- File bawaan skeleton Laravel (halaman welcome, konfigurasi Vite, `AGENTS.md`/`CLAUDE.md`) dibiarkan apa adanya.
