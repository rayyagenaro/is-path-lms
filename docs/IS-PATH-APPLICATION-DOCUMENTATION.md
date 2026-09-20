# IS-Path — Dokumentasi Aplikasi

> Ringkasan produk, arsitektur, domain knowledge, dan status implementasi untuk kebutuhan presentasi serta latihan interview.

## 1. Ringkasan Produk

IS-Path adalah platform pembelajaran dan eksplorasi karier untuk mahasiswa Sistem Informasi. Aplikasi ini membantu mahasiswa memahami kekuatan kompetensinya, mengidentifikasi gap, memilih peran yang ingin dieksplorasi, dan mendapatkan learning path yang relevan.

IS-Path tidak dimaksudkan sebagai alat penentu pekerjaan secara mutlak. Hasil rekomendasi merupakan indikator awal untuk membantu mahasiswa mengambil keputusan belajar yang lebih terarah.

## 2. Masalah yang Diselesaikan

Mahasiswa sering memahami teori, tetapi belum mengetahui:

- kompetensi apa yang sudah dikuasai;
- bidang atau peran apa yang paling relevan;
- bukti apa yang mendukung penilaian tersebut;
- kemampuan apa yang masih menjadi gap; dan
- materi belajar apa yang sebaiknya diprioritaskan.

IS-Path menyatukan assessment, course, kompetensi, ontology, dan rekomendasi dalam satu alur pembelajaran.

## 3. Target Pengguna

### Pengguna utama

Mahasiswa, terutama mahasiswa Sistem Informasi, yang sedang:

- mengeksplorasi pilihan karier;
- memetakan kompetensi pribadi;
- mencari course yang sesuai;
- membangun bukti pembelajaran; atau
- menyusun rencana pengembangan diri.

### Batasan role

Produk berfokus pada satu role aplikasi, yaitu mahasiswa. Tidak ada dashboard recruiter, admin bisnis, atau role pengguna eksternal dalam flow utama.

## 4. Tujuan Produk

1. Memberikan gambaran kompetensi yang mudah dipahami.
2. Mengubah hasil assessment menjadi rekomendasi belajar yang dapat ditindaklanjuti.
3. Menjelaskan alasan di balik rekomendasi karier.
4. Membantu mahasiswa memantau progres kompetensi dan course.
5. Menghubungkan data aplikasi dengan domain knowledge ontology.

## 5. Alur Pengguna Utama

```text
Register
  ↓
Pre-assessment minat, pengetahuan, dan gaya kerja
  ↓
Memilih maksimal tiga peran untuk dieksplorasi
  ↓
Assessment berdasarkan topik kompetensi
  ↓
Eksplorasi course, modul, dan latihan
  ↓
Post-assessment dan bukti pembelajaran
  ↓
StudentCompetency diperbarui
  ↓
Ontology/SWRL reasoning
  ↓
Rekomendasi karier + competency gap
  ↓
Personalized learning path
```

Pengguna baru diarahkan menyelesaikan pre-assessment sebelum dapat menggunakan fitur pembelajaran secara penuh.

## 6. Modul Aplikasi

### 6.1 Authentication

- Register mahasiswa baru.
- Login dan logout.
- Session berbasis Laravel.
- Guest hanya dapat melihat tampilan terbatas.
- Pengguna baru diarahkan ke pre-assessment.

### 6.2 Dashboard

Menampilkan ringkasan progres mahasiswa, target karier, course aktif, kompetensi terukur, dan area yang perlu diperkuat.

### 6.3 Eksplorasi Karier

Menampilkan role berdasarkan bidang atau cluster. Mahasiswa dapat memilih hingga tiga role untuk dieksplorasi. Role utama dan alternatif disimpan untuk mendukung rekomendasi.

### 6.4 Assessment

Assessment dikelompokkan berdasarkan topik, misalnya:

- Data & Analytics;
- Software & Product Development;
- Business & Process;
- Solution & Enterprise Architecture; dan
- Information Systems Fundamentals.

Assessment memiliki pagination, tracker nomor soal, status aktif/terjawab, progress jawaban, serta riwayat pengerjaan.

### 6.5 Pembelajaran

Course terdiri dari beberapa modul. Modul dapat berisi materi, latihan, bukti penyelesaian, dan assessment terkait. Progres dihitung dari modul serta assessment yang sudah selesai.

### 6.6 Kompetensi

Halaman kompetensi menampilkan:

- skor penguasaan;
- kekuatan bukti;
- level kompetensi;
- bidang kompetensi;
- status pengembangan; dan
- penjelasan sumber skor.

### 6.7 Profil Karier

Menampilkan hasil rekomendasi, role yang dipilih, skor kecocokan, kompetensi kuat, competency gap, dan learning path yang disarankan.

## 7. Domain Knowledge Ontology

Ontology utama berasal dari `Ontologi/LMS FIX.rdf` dan didokumentasikan lebih lanjut pada `IS-PATH-ONTOLOGY.md` serta `docs/ontology-integration.md`.

### Konsep utama

- Student
- CareerField
- CareerRole
- Competency
- Course
- Module
- Assessment
- AssessmentQuestion
- StudentCompetency
- LearningPath
- Evidence

### Relasi utama

- CareerField memiliki CareerRole.
- CareerRole membutuhkan Competency.
- Course mengembangkan Competency.
- Course memiliki Module.
- Assessment mengukur Competency.
- Student mengerjakan Assessment.
- Student memiliki StudentCompetency.
- StudentCompetency didukung Evidence.
- LearningPath terdiri dari Course atau Module.
- CareerRole direkomendasikan berdasarkan StudentCompetency.

### Data penting

Beberapa data yang digunakan dalam penilaian antara lain skor, level, confidence score, status penyelesaian, jumlah percobaan, dan waktu pengerjaan.

## 8. Sistem Rekomendasi

Sistem rekomendasi menggabungkan:

1. hasil pre-assessment;
2. hasil assessment topik;
3. kompetensi wajib dan pendukung role;
4. progres course dan modul;
5. bukti pembelajaran; dan
6. hasil reasoning dari domain ontology.

Output sistem meliputi:

- role rekomendasi utama;
- role alternatif;
- persentase kecocokan;
- kompetensi yang sudah kuat;
- kompetensi yang masih menjadi gap;
- alasan rekomendasi; dan
- urutan learning path.

Sistem memberikan rekomendasi yang explainable, bukan sekadar skor tanpa alasan.

## 9. Implementasi Ontology dan SWRL

RDF/OWL digunakan sebagai sumber struktur domain. Laravel membaca ontology melalui repository dan menerapkan rule reasoning melalui service aplikasi.

Rule yang aktif dipakai untuk membantu:

- menghubungkan kompetensi dengan role;
- mendeteksi gap kompetensi;
- menentukan kesiapan relatif;
- menyarankan course atau modul; dan
- menyimpan fakta serta rule yang terpicu sebagai bagian dari penjelasan rekomendasi.

Implementasi saat ini merupakan application-level reasoning bridge. Integrasi reasoner eksternal seperti Apache Jena, Pellet, atau HermiT dapat menjadi pengembangan lanjutan.

## 10. Arsitektur Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel / PHP |
| Database | PostgreSQL |
| View layer | Laravel Blade |
| Frontend behavior | JavaScript |
| Styling | CSS dengan design token IS-Path |
| Domain knowledge | RDF / OWL / SWRL |
| Testing | Laravel Feature Tests |
| Local server | Laravel Artisan |

## 11. Struktur Data Konseptual

Relasi inti aplikasi meliputi:

- `users` untuk akun mahasiswa;
- career fields dan career roles;
- competencies dan role competencies;
- courses dan course modules;
- assessments dan assessment questions;
- assessment attempts dan answers;
- student competencies;
- learning progress;
- evidence atau project records; dan
- recommendation snapshots.

Struktur aktual mengikuti migration dan model Laravel yang tersedia di codebase.

## 12. Prinsip UX dan Visual

IS-Path menggunakan identitas visual minimalis dengan dasar:

- warna putih dan hijau gelap;
- tipografi Poppins;
- layout editorial yang lapang;
- border tipis dan radius konsisten;
- tombol dengan label aksi yang jelas;
- progress bar yang menunjukkan nilai secara nyata;
- tracker assessment yang membedakan soal aktif dan terjawab; serta
- animasi ringan yang mendukung orientasi pengguna.

Desain menghindari kartu dekoratif berlebihan, copy generik, tombol tanpa konteks, dan layout yang terasa seperti template AI.

## 13. Keamanan dan Validasi

- Route utama dilindungi autentikasi.
- Pengguna non-mahasiswa tidak dapat mengakses flow aplikasi.
- Validasi membatasi pilihan role maksimal tiga.
- Assessment tidak dapat dikirim sebelum seluruh soal wajib dijawab.
- Jawaban yang sudah dikirim tidak dapat diubah pada percobaan yang sama.
- CSRF Laravel tetap digunakan pada form.
- Kredensial database disimpan di `.env`.

## 14. Testing dan Quality Checks

Validasi yang sudah tersedia mencakup:

- halaman pengalaman mahasiswa;
- register dan penyimpanan ke database;
- pembatasan role pengguna;
- pre-assessment wajib;
- penyelesaian modul;
- pagination eksplorasi karier;
- rekomendasi dan endpoint penting;
- kelengkapan master data PRD v2; dan
- progres course.

Perubahan frontend juga perlu diperiksa melalui syntax check JavaScript serta pemeriksaan visual pada viewport desktop dan mobile.

## 15. Keputusan Produk Penting

1. Satu role aplikasi: mahasiswa.
2. Assessment diberi nama berdasarkan topik, bukan nama pekerjaan tertentu.
3. Mahasiswa dapat memilih maksimal tiga role untuk eksplorasi.
4. Rekomendasi adalah alat bantu belajar, bukan jaminan pekerjaan.
5. Ontology menjadi sumber domain knowledge, sedangkan Laravel menghubungkan reasoning dengan data aplikasi.
6. Riwayat assessment ditampilkan terpisah agar halaman tidak menumpuk.

## 16. Risiko dan Batasan Saat Ini

- Kualitas rekomendasi bergantung pada kelengkapan master data kompetensi dan role.
- Nilai rendah dapat terjadi jika bukti pembelajaran belum cukup, meskipun satu assessment memperoleh skor tinggi.
- Reasoning saat ini belum menggunakan inference server eksternal secara penuh.
- Ontology perlu dipelihara ketika role, kompetensi, atau course bertambah.
- Hasil rekomendasi perlu divalidasi secara berkala bersama dosen atau praktisi.

## 17. Roadmap Pengembangan

### Prioritas dekat

- Memperbanyak bukti pembelajaran berbasis proyek.
- Menambahkan halaman penjelasan detail rekomendasi.
- Menambahkan filter dan perbandingan role.
- Menyempurnakan rubric skor kompetensi.

### Prioritas menengah

- Integrasi reasoner OWL/SWRL eksternal.
- Learning path adaptif berdasarkan progres aktual.
- Portofolio proyek dan validasi bukti.
- Visualisasi perkembangan kompetensi dari waktu ke waktu.

### Prioritas lanjut

- Validasi rekomendasi dengan data alumni atau industri.
- Eksperimen model hybrid rule-based dan machine learning.
- Integrasi sumber course eksternal.

## 18. Cara Menjelaskan IS-Path Saat Interview

> IS-Path adalah platform career-learning untuk mahasiswa Sistem Informasi. Pengguna memulai dari pre-assessment, lalu memilih beberapa peran yang ingin dieksplorasi. Sistem mengukur kompetensi melalui assessment, course, dan bukti pembelajaran. Data tersebut dipetakan ke ontology RDF/OWL dan rule SWRL untuk menghasilkan rekomendasi karier yang explainable, competency gap, serta learning path yang lebih personal. Backend dibangun dengan Laravel dan PostgreSQL, sedangkan frontend menggunakan Blade, CSS, dan JavaScript.

## 19. Referensi Internal

- `PRDLMS.md`
- `PRD-Competency-Based-LMS-Sistem-Informasi.md`
- `IS-Path-PRD-v2.md`
- `IS-PATH-ONTOLOGY.md`
- `Ontologi/LMS FIX.rdf`
- `docs/ontology-integration.md`
- `DESIGN.md`
- `docs/ai-project-prd/00-master-prd.md`

