# Integrasi Ontology IS-Path

## Tujuan

Ontology `Ontologi/LMS FIX.rdf` menjadi sumber istilah domain dan aturan SWRL. PostgreSQL tetap menjadi sumber data operasional mahasiswa, assessment, kompetensi, rekomendasi, serta learning path. File RDF tidak disalin ke tabel dan struktur database existing tidak diubah.

## Alur data

1. Mahasiswa melengkapi profil awal: minat, gaya kerja, tujuan, dan self-rating.
2. IS-Path menyusun arah eksplorasi dari profil. Self-rating dipertahankan utuh, tetapi skor rekomendasi karier belum dihitung atau ditampilkan.
3. Mahasiswa memilih target dan dapat mengikuti course/module yang relevan.
4. Post-assessment menghasilkan `AssessmentAttempt`, jawaban, dan `CompetencyEvidence`.
5. `CompetencyScoreCalculator` memperbarui `StudentCompetency`.
6. `RecommendationWorkflowService` menghitung ulang peringkat karier dan learning path.
7. `SwrlReasoningService` mengevaluasi kondisi runtime PostgreSQL terhadap aturan aktif di RDF.
8. Hasil rekomendasi menyimpan skor, kekuatan, gap, readiness, rule yang terpanggil, dan inferred facts dalam `career_recommendations.explanation_snapshot`.

## Batas tanggung jawab

- `OntologyRepository` membaca RDF/XML secara read-only, menghitung checksum, dan memeriksa rule yang aktif.
- `SwrlReasoningService` adalah application bridge untuk semantik rule karier yang digunakan IS-Path; ini bukan reasoner SWRL generik.
- PostgreSQL menyimpan keadaan terbaru dan histori operasional. RDF menyimpan definisi pengetahuan yang relatif stabil.
- Jika RDF hilang atau invalid, rekomendasi berbasis database tetap berjalan. Trace ontology diberi status `unavailable` agar kegagalan terlihat tanpa memutus flow mahasiswa.

## Rule yang dipakai application bridge

| Rule | Makna aplikasi |
|---|---|
| R07 | Post-assessment selesai |
| R08 | Post-assessment mengevaluasi kompetensi |
| R13 | Terdapat gap kebutuhan karier |
| R14 | Terdapat gap kompetensi wajib |
| R15 | Terdapat gap kompetensi rekomendasi |
| R16 | Mahasiswa menjadi kandidat rekomendasi setelah post-assessment |
| R17 | Pengembangan kompetensi diperlukan |
| R21 | Course kandidat tersedia untuk gap |
| R22–R24 | Kategori rekomendasi berdasarkan skor akhir |

## Konfigurasi

Gunakan variabel berikut bila lokasi atau namespace ontology berubah:

```dotenv
ISPATH_ONTOLOGY_PATH="Ontologi/LMS FIX.rdf"
ISPATH_ONTOLOGY_NAMESPACE="http://www.semanticweb.org/hp/ontologies/2026/8/untitled-ontology-79#"
ISPATH_ONTOLOGY_CACHE_SECONDS=3600
```

Path default menunjuk ke `base_path('Ontologi/LMS FIX.rdf')`. Setelah mengganti RDF atau `.env`, jalankan `php artisan optimize:clear` agar cache konfigurasi dan ringkasan ontology dibaca ulang.

## Interpretasi skor

- **Arah eksplorasi**: profil minat dan gaya kerja sebelum post-assessment; bukan rekomendasi karier dan tidak memiliki persentase kecocokan.
- **Kecocokan karier**: setelah post-assessment, gabungan 80% skor berbukti dan 20% self-rating untuk kompetensi yang memiliki keduanya.
- **Kesiapan**: baru ditampilkan sebagai nilai terukur setelah post-assessment selesai.
- **Gap**: selisih level mahasiswa dan level kebutuhan role, termasuk penanda mandatory/recommended.

Pemisahan ini mencegah self-rating tinggi tampak jatuh menjadi 20%, mencegah readiness nol dianggap sebagai hasil assessment, dan menjaga recommendation gate sesuai PRD.

## Validasi

Pengujian otomatis memastikan RDF dapat dibaca, 24 rule aktif ditemukan, penyelesaian assessment menghasilkan bukti, lima rekomendasi tersimpan, R07 terpanggil, serta satu learning path aktif dibuat ulang.
