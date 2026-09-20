# IS-Path LMS

IS-Path adalah aplikasi pembelajaran dan eksplorasi karier untuk mahasiswa Sistem Informasi. Aplikasi menghubungkan assessment, profil kompetensi, domain knowledge OWL/RDF, rule-based reasoning, rekomendasi karier, competency gap, dan learning path dalam satu alur yang dapat dijelaskan.

## Alur utama

```text
Registrasi
  -> pre-assessment pengetahuan awal
  -> pemilihan maksimal tiga peran
  -> assessment topik
  -> kelas dan modul pembelajaran
  -> post-assessment
  -> profil StudentCompetency
  -> ontology dan SWRL reasoning
  -> rekomendasi karier
  -> competency gap
  -> personalized learning path
```

IS-Path hanya memiliki satu role aplikasi, yaitu mahasiswa. Pengunjung dapat masuk sebagai guest untuk melihat pratinjau tanpa mengakses fitur atau data mahasiswa.

## Fitur

- Registrasi dan autentikasi mahasiswa berbasis database.
- Pre-assessment wajib untuk akun baru sebelum memilih pembelajaran.
- Assessment dengan pagination lima soal, penyimpanan draft, tracker jawaban, riwayat percobaan, dan pembahasan hasil.
- Eksplorasi 22 peran karier dalam delapan bidang dengan filter dan pagination.
- Pemilihan maksimal tiga peran untuk ditinjau lebih lanjut.
- Course, module, prerequisite, progress, dan post-assessment.
- Profil kompetensi berdasarkan evidence beserta sumber, bobot, waktu, dan masa berlaku.
- Penyimpanan referensi proyek tanpa menaikkan skor sebelum proyek dinilai.
- Rekomendasi karier rule-based dengan penjelasan kecocokan dan competency gap.
- Personalized learning path berdasarkan kebutuhan kompetensi peran.
- Ontology OWL/RDF dan rule SWRL dari `Ontologi/LMS FIX.rdf`.
- API terautentikasi untuk kompetensi, rekomendasi, karier, course, dan ontology graph.
- Guest preview yang bersifat read-only.

## Teknologi

- PHP 8.2+
- Laravel 12
- PostgreSQL 16
- Laravel Sanctum
- Blade, CSS, dan JavaScript
- OWL/RDF serta SWRL
- PHPUnit dan Playwright

## Struktur domain

```text
app/Domains/Assessment       assessment dan pembentukan evidence
app/Domains/Competency       kalkulasi profil kompetensi
app/Domains/Ontology         pembacaan RDF dan evaluasi rule SWRL
app/Domains/Recommendation   workflow rekomendasi karier
Ontologi/                    sumber domain knowledge
database/seeders/            data awal karier, kompetensi, kelas, dan assessment
resources/views/             antarmuka Blade
public/css/                  design system dan komponen antarmuka
tests/                       unit, feature, dan browser regression tests
```

## Persyaratan

Pastikan perangkat memiliki:

- PHP 8.2 atau lebih baru dengan ekstensi `pdo_pgsql`.
- Composer.
- PostgreSQL 16, atau Docker Desktop untuk menjalankan PostgreSQL melalui Compose.
- Node.js dan npm jika ingin menjalankan build aset atau browser test.

## Instalasi

Clone repository dan masuk ke folder proyek:

```powershell
git clone https://github.com/rayyagenaro/is-path-lms.git
cd is-path-lms
composer install
Copy-Item .env.example .env
php artisan key:generate
```

### Opsi 1: PostgreSQL melalui Docker

```powershell
docker compose up -d
php artisan migrate --seed
php artisan serve
```

### Opsi 2: PostgreSQL lokal

Buat database dan user berikut di PostgreSQL:

```sql
CREATE USER is_path WITH PASSWORD 'is_path_secret';
CREATE DATABASE is_path OWNER is_path;
```

Pastikan konfigurasi database pada `.env` sesuai, kemudian jalankan:

```powershell
php artisan migrate --seed
php artisan serve
```

Buka `http://127.0.0.1:8000`.

> Gunakan `php artisan migrate --seed` untuk instalasi normal. `migrate:fresh` menghapus seluruh tabel dan data yang sudah ada.

## Akun pengembangan

Seeder menyediakan satu akun mahasiswa:

```text
Email: rani@ispath.id
Password: password
```

Untuk mencoba alur pengguna baru, buat akun melalui `/register`. Session aplikasi berakhir setelah tiga menit tanpa request ke server sesuai konfigurasi pengembangan saat ini.

## Ontology dan reasoning

Sumber ontology utama berada di:

```text
Ontologi/LMS FIX.rdf
```

Konfigurasinya tersedia pada `.env.example` melalui:

```text
ISPATH_ONTOLOGY_PATH
ISPATH_ONTOLOGY_NAMESPACE
ISPATH_ONTOLOGY_CACHE_SECONDS
```

`OntologyRepository` membaca class, relasi, individual, dan rule dari RDF. `SwrlReasoningService` memakai rule yang aktif sebagai bagian dari workflow rekomendasi. PostgreSQL tetap menjadi sumber data transaksi mahasiswa, sedangkan RDF menjadi sumber domain knowledge dan reasoning.

Ringkasan ontology terautentikasi tersedia melalui:

```text
GET /api/v1/ontology/graph
```

Dokumentasi domain lebih lanjut tersedia pada [IS-PATH-ONTOLOGY.md](IS-PATH-ONTOLOGY.md) dan [docs/ai-project-prd](docs/ai-project-prd/README.md).

## Perintah pengembangan

```powershell
# Menjalankan aplikasi
php artisan serve

# Memeriksa revisi assessment peran tanpa mengubah data
php artisan assessment:publish-role-v2 --dry-run

# Menerbitkan revisi assessment dan mempertahankan attempt lama
php artisan assessment:publish-role-v2

# Menjalankan seluruh unit dan feature test
php artisan test

# Menjalankan browser regression test
npm install
npm run test:browser
```

Browser test memakai database SQLite terpisah pada `storage/framework/testing` dan tidak menjalankan `migrate:fresh` terhadap database aplikasi.

## Status verifikasi

Pada pemeriksaan terakhir:

- 36 unit dan feature test lulus dengan 912 assertions.
- 7 browser test lulus pada viewport 375, 768, 1024, dan 1440 piksel serta landscape 812×375.
- Alur registrasi, pre-assessment, progress bar, keyboard focus, reduced motion, dan overflow responsif termasuk dalam browser test.

Jalankan kembali test setelah melakukan perubahan karena angka di atas menggambarkan kondisi commit saat dokumentasi ini disusun.

## Catatan keamanan repository

- File `.env` tidak boleh di-commit.
- Jangan menyimpan password produksi, API key, atau token di repository.
- Ubah kredensial database dan akun seed sebelum deployment publik.
- `APP_DEBUG` harus bernilai `false` pada production.

## Contributor

- [rayyagenaro](https://github.com/rayyagenaro)

