# Draft Ontologi IS-Path

Versi: 0.1  
Status: rancangan domain knowledge untuk dibaca dan ditinjau sebelum dibuat di Protégé  
Ruang lingkup: rekomendasi karier dan learning path bagi mahasiswa Sistem Informasi

## 1. Batas domain

IS-Path hanya memiliki satu aktor aplikasi, yaitu **Mahasiswa**. Ontologi tidak memodelkan dosen, admin, rekruter, perusahaan, atau pakar sebagai pengguna. Istilah *Career Role* di dokumen ini berarti jenis pekerjaan yang dapat direkomendasikan kepada mahasiswa, bukan role untuk masuk ke aplikasi.

Ontologi akan menghubungkan:

1. profil dan minat mahasiswa;
2. kompetensi yang dimiliki mahasiswa;
3. kompetensi yang dibutuhkan suatu pekerjaan;
4. kelas dan modul yang mengajarkan kompetensi;
5. asesmen dan proyek yang menjadi bukti kompetensi;
6. hasil rekomendasi karier; dan
7. learning path untuk menutup competency gap.

SWRL belum dimasukkan pada versi ini. Aturan rekomendasi akan dibahas setelah struktur kelas dan properti disetujui.

## 2. Prinsip pemodelan

- `Student`, `Competency`, `CareerRole`, `Course`, dan entitas domain lain dibuat sebagai **class**.
- Data nyata seperti Rani Putri, SQL, Data Analyst, dan SQL & Relational Database dibuat sebagai **individual**.
- Jenis kompetensi dibuat sebagai subclass agar struktur kompetensi mudah ditelusuri.
- Relasi yang memiliki atribut sendiri dimodelkan sebagai class penghubung. Contohnya `CareerRequirement` menyimpan level minimum dan bobot kompetensi pada sebuah pekerjaan.
- Nilai level menggunakan skala 0–5, sedangkan nilai penguasaan, kepercayaan bukti, kecocokan, dan kesiapan menggunakan skala 0–100.
- Rekomendasi bukan fakta permanen. Setiap hasil memiliki waktu perhitungan dan dapat berubah ketika profil atau bukti mahasiswa berubah.

## 3. Prefix yang disarankan

```text
ispath:  https://is-path.local/ontology#
rdf:     http://www.w3.org/1999/02/22-rdf-syntax-ns#
rdfs:    http://www.w3.org/2000/01/rdf-schema#
owl:     http://www.w3.org/2002/07/owl#
xsd:     http://www.w3.org/2001/XMLSchema#
```

## 4. Class hierarchy

```text
owl:Thing
├── Student
├── CareerProfile
├── CareerCluster
├── CareerRole
├── WorkStyle
├── Competency
│   ├── TechnicalCompetency
│   ├── BusinessCompetency
│   └── ProfessionalCompetency
├── StudentCompetency
├── CareerRequirement
├── Course
├── CourseCompetencyMapping
├── Module
├── Assessment
├── AssessmentItem
├── AssessmentAttempt
├── Project
├── ProjectSubmission
├── CompetencyEvidence
├── CareerRecommendation
├── LearningPath
└── LearningPathItem
```

`TechnicalCompetency`, `BusinessCompetency`, dan `ProfessionalCompetency` disarankan saling `owl:disjointWith`. Satu individual kompetensi hanya berada pada satu kelompok utama tersebut.

## 5. Definisi class

| Class | Makna faktual dalam IS-Path |
|---|---|
| `Student` | Mahasiswa Sistem Informasi yang menggunakan aplikasi untuk mengetahui arah karier dan learning path. |
| `CareerProfile` | Ringkasan minat, gaya kerja, tujuan, dan penilaian awal mahasiswa. |
| `CareerCluster` | Kelompok pekerjaan yang memiliki fokus domain serupa. |
| `CareerRole` | Jenis pekerjaan yang dapat menjadi target atau rekomendasi karier. |
| `WorkStyle` | Preferensi umum cara bekerja mahasiswa. |
| `Competency` | Pengetahuan, keterampilan, atau kemampuan yang dapat dipelajari dan dibuktikan. |
| `StudentCompetency` | Keadaan penguasaan satu kompetensi oleh satu mahasiswa pada suatu waktu. |
| `CareerRequirement` | Kebutuhan satu kompetensi pada satu pekerjaan beserta level minimum dan bobotnya. |
| `Course` | Unit pembelajaran terstruktur yang mengajarkan satu atau lebih kompetensi. |
| `CourseCompetencyMapping` | Kontribusi sebuah kelas terhadap suatu kompetensi. |
| `Module` | Bagian pembelajaran yang berada di dalam sebuah kelas. |
| `Assessment` | Instrumen untuk mengukur kompetensi mahasiswa. |
| `AssessmentItem` | Pertanyaan atau tugas yang menjadi bagian dari asesmen. |
| `AssessmentAttempt` | Rekaman pengerjaan asesmen oleh mahasiswa. |
| `Project` | Tugas praktik yang dirancang untuk menerapkan satu atau lebih kompetensi. |
| `ProjectSubmission` | Tautan atau artefak proyek yang dikirim mahasiswa ke portofolionya. |
| `CompetencyEvidence` | Bukti terukur yang mendukung nilai kompetensi mahasiswa. |
| `CareerRecommendation` | Hasil perhitungan kecocokan mahasiswa dengan suatu pekerjaan. |
| `LearningPath` | Urutan pembelajaran untuk mencapai target karier mahasiswa. |
| `LearningPathItem` | Satu langkah pada learning path yang menghubungkan gap kompetensi dengan kelas. |

## 6. Object properties

### 6.1 Profil mahasiswa

| Object property | Domain | Range | Inverse | Makna |
|---|---|---|---|---|
| `hasCareerProfile` | `Student` | `CareerProfile` | `profileOf` | Mahasiswa memiliki profil karier. |
| `profileOf` | `CareerProfile` | `Student` | `hasCareerProfile` | Profil karier milik mahasiswa. |
| `prefersWorkStyle` | `CareerProfile` | `WorkStyle` | — | Profil memiliki preferensi gaya kerja. |
| `hasStudentCompetency` | `Student` | `StudentCompetency` | `competencyRecordOf` | Mahasiswa memiliki rekaman penguasaan kompetensi. |
| `competencyRecordOf` | `StudentCompetency` | `Student` | `hasStudentCompetency` | Rekaman kompetensi milik mahasiswa. |
| `recordsCompetency` | `StudentCompetency` | `Competency` | — | Kompetensi yang diukur oleh rekaman tersebut. |
| `interestedInCareer` | `Student` | `CareerRole` | — | Mahasiswa pernah mengeksplorasi atau menyukai pekerjaan. |
| `targetsCareer` | `Student` | `CareerRole` | `isTargetOf` | Pekerjaan yang dipilih sebagai target aktif. |
| `isTargetOf` | `CareerRole` | `Student` | `targetsCareer` | Pekerjaan menjadi target mahasiswa. |

### 6.2 Pekerjaan dan kompetensi

| Object property | Domain | Range | Inverse | Makna |
|---|---|---|---|---|
| `belongsToCluster` | `CareerRole` | `CareerCluster` | `hasCareerRole` | Pekerjaan berada dalam cluster karier. |
| `hasCareerRole` | `CareerCluster` | `CareerRole` | `belongsToCluster` | Cluster memuat pekerjaan. |
| `hasRequirement` | `CareerRole` | `CareerRequirement` | `requirementOfCareer` | Pekerjaan memiliki kebutuhan kompetensi. |
| `requirementOfCareer` | `CareerRequirement` | `CareerRole` | `hasRequirement` | Kebutuhan terkait dengan pekerjaan tertentu. |
| `requiresCompetency` | `CareerRequirement` | `Competency` | `requiredByCareerRequirement` | Kompetensi yang dibutuhkan oleh requirement. |
| `requiredByCareerRequirement` | `Competency` | `CareerRequirement` | `requiresCompetency` | Kompetensi digunakan dalam requirement pekerjaan. |
| `relatedCompetency` | `Competency` | `Competency` | simetris | Dua kompetensi memiliki keterkaitan. |
| `competencyPrerequisiteOf` | `Competency` | `Competency` | `hasCompetencyPrerequisite` | Kompetensi perlu dikuasai sebelum kompetensi lain. |
| `hasCompetencyPrerequisite` | `Competency` | `Competency` | `competencyPrerequisiteOf` | Kompetensi memiliki prasyarat kompetensi. |

### 6.3 Pembelajaran

| Object property | Domain | Range | Inverse | Makna |
|---|---|---|---|---|
| `hasCourseCompetencyMapping` | `Course` | `CourseCompetencyMapping` | `mappingOfCourse` | Kelas memiliki pemetaan kompetensi. |
| `mappingOfCourse` | `CourseCompetencyMapping` | `Course` | `hasCourseCompetencyMapping` | Pemetaan dimiliki sebuah kelas. |
| `teachesCompetency` | `CourseCompetencyMapping` | `Competency` | `taughtThroughMapping` | Pemetaan menunjukkan kompetensi yang diajarkan. |
| `taughtThroughMapping` | `Competency` | `CourseCompetencyMapping` | `teachesCompetency` | Kompetensi diajarkan melalui pemetaan kelas. |
| `hasModule` | `Course` | `Module` | `moduleOfCourse` | Kelas terdiri dari modul. |
| `moduleOfCourse` | `Module` | `Course` | `hasModule` | Modul merupakan bagian dari kelas. |
| `coursePrerequisiteOf` | `Course` | `Course` | `hasCoursePrerequisite` | Kelas menjadi prasyarat kelas lain. |
| `hasCoursePrerequisite` | `Course` | `Course` | `coursePrerequisiteOf` | Kelas memiliki prasyarat kelas. |
| `modulePrerequisiteOf` | `Module` | `Module` | `hasModulePrerequisite` | Modul menjadi prasyarat modul lain. |
| `hasModulePrerequisite` | `Module` | `Module` | `modulePrerequisiteOf` | Modul memiliki prasyarat modul. |

### 6.4 Asesmen, proyek, dan bukti

| Object property | Domain | Range | Inverse | Makna |
|---|---|---|---|---|
| `assessesCompetency` | `Assessment` | `Competency` | `assessedBy` | Asesmen mengukur kompetensi. |
| `assessedBy` | `Competency` | `Assessment` | `assessesCompetency` | Kompetensi diukur oleh asesmen. |
| `hasAssessmentItem` | `Assessment` | `AssessmentItem` | `itemOfAssessment` | Asesmen memiliki butir pertanyaan atau tugas. |
| `itemOfAssessment` | `AssessmentItem` | `Assessment` | `hasAssessmentItem` | Butir merupakan bagian dari asesmen. |
| `attemptedBy` | `AssessmentAttempt` | `Student` | `hasAssessmentAttempt` | Pengerjaan dilakukan mahasiswa. |
| `hasAssessmentAttempt` | `Student` | `AssessmentAttempt` | `attemptedBy` | Mahasiswa memiliki rekaman pengerjaan. |
| `attemptOfAssessment` | `AssessmentAttempt` | `Assessment` | — | Pengerjaan merujuk ke asesmen. |
| `projectDemonstrates` | `Project` | `Competency` | `demonstratedByProject` | Proyek dirancang untuk menunjukkan kompetensi. |
| `demonstratedByProject` | `Competency` | `Project` | `projectDemonstrates` | Kompetensi dapat ditunjukkan melalui proyek. |
| `submissionOfProject` | `ProjectSubmission` | `Project` | `hasProjectSubmission` | Kiriman dibuat untuk proyek tertentu. |
| `hasProjectSubmission` | `Project` | `ProjectSubmission` | `submissionOfProject` | Proyek memiliki kiriman mahasiswa. |
| `submittedBy` | `ProjectSubmission` | `Student` | `hasSubmittedProject` | Kiriman dibuat oleh mahasiswa. |
| `hasSubmittedProject` | `Student` | `ProjectSubmission` | `submittedBy` | Mahasiswa mengirim proyek ke portofolio. |
| `hasEvidence` | `StudentCompetency` | `CompetencyEvidence` | `evidenceFor` | Rekaman kompetensi didukung bukti. |
| `evidenceFor` | `CompetencyEvidence` | `StudentCompetency` | `hasEvidence` | Bukti mendukung rekaman kompetensi. |
| `derivedFromAttempt` | `CompetencyEvidence` | `AssessmentAttempt` | — | Bukti berasal dari hasil asesmen. |
| `derivedFromSubmission` | `CompetencyEvidence` | `ProjectSubmission` | — | Bukti berasal dari kiriman proyek. |

### 6.5 Rekomendasi dan learning path

| Object property | Domain | Range | Inverse | Makna |
|---|---|---|---|---|
| `recommendationFor` | `CareerRecommendation` | `Student` | `hasCareerRecommendation` | Rekomendasi dihitung untuk mahasiswa. |
| `hasCareerRecommendation` | `Student` | `CareerRecommendation` | `recommendationFor` | Mahasiswa memiliki hasil rekomendasi. |
| `recommendsCareer` | `CareerRecommendation` | `CareerRole` | — | Pekerjaan yang direkomendasikan. |
| `identifiesCompetencyGap` | `CareerRecommendation` | `Competency` | — | Kompetensi yang belum memenuhi kebutuhan pekerjaan. |
| `hasLearningPath` | `Student` | `LearningPath` | `learningPathFor` | Mahasiswa memiliki learning path. |
| `learningPathFor` | `LearningPath` | `Student` | `hasLearningPath` | Learning path dibuat untuk mahasiswa. |
| `pathTargetsCareer` | `LearningPath` | `CareerRole` | — | Learning path mengarah pada target pekerjaan. |
| `hasLearningPathItem` | `LearningPath` | `LearningPathItem` | `itemOfLearningPath` | Learning path memiliki langkah belajar. |
| `itemOfLearningPath` | `LearningPathItem` | `LearningPath` | `hasLearningPathItem` | Langkah berada di dalam learning path. |
| `addressesCompetency` | `LearningPathItem` | `Competency` | — | Langkah menutup gap kompetensi tertentu. |
| `recommendsCourse` | `LearningPathItem` | `Course` | — | Kelas yang disarankan untuk langkah tersebut. |

## 7. Data properties

### 7.1 Identitas dan profil

| Data property | Domain | Range | Catatan |
|---|---|---|---|
| `studentId` | `Student` | `xsd:string` | ID internal mahasiswa. |
| `studentName` | `Student` | `xsd:string` | Nama mahasiswa. |
| `nim` | `Student` | `xsd:string` | Nomor induk mahasiswa. |
| `email` | `Student` | `xsd:string` | Email akun mahasiswa. |
| `cohortYear` | `Student` | `xsd:gYear` | Tahun angkatan. |
| `studyProgram` | `Student` | `xsd:string` | Program studi. |
| `primaryInterest` | `CareerProfile` | `xsd:string` | Bidang minat utama. |
| `careerGoal` | `CareerProfile` | `xsd:string` | Tujuan karier yang ditulis mahasiswa. |
| `profileCompletedAt` | `CareerProfile` | `xsd:dateTime` | Waktu profil selesai diisi. |

### 7.2 Kompetensi dan kebutuhan karier

| Data property | Domain | Range | Batas nilai |
|---|---|---|---|
| `competencyCode` | `Competency` | `xsd:string` | Unik. |
| `competencyName` | `Competency` | `xsd:string` | — |
| `competencyDescription` | `Competency` | `xsd:string` | — |
| `masteryScore` | `StudentCompetency` | `xsd:decimal` | 0–100. |
| `proficiencyLevel` | `StudentCompetency` | `xsd:integer` | 0–5. |
| `confidenceScore` | `StudentCompetency` | `xsd:decimal` | 0–100. |
| `calculatedAt` | `StudentCompetency` | `xsd:dateTime` | — |
| `minimumLevel` | `CareerRequirement` | `xsd:integer` | 1–5. |
| `requirementWeight` | `CareerRequirement` | `xsd:decimal` | 0–100; total per pekerjaan 100. |
| `requirementType` | `CareerRequirement` | `xsd:string` | `mandatory` atau `recommended`. |

### 7.3 Pekerjaan dan pembelajaran

| Data property | Domain | Range | Catatan |
|---|---|---|---|
| `careerRoleName` | `CareerRole` | `xsd:string` | Nama pekerjaan. |
| `careerRoleDescription` | `CareerRole` | `xsd:string` | Ringkasan tanggung jawab pekerjaan. |
| `careerLevel` | `CareerRole` | `xsd:string` | `standard` atau `advanced`. |
| `clusterName` | `CareerCluster` | `xsd:string` | Nama cluster karier. |
| `courseCode` | `Course` | `xsd:string` | Kode unik C01–C26. |
| `courseTitle` | `Course` | `xsd:string` | Judul kelas. |
| `courseLevel` | `Course` | `xsd:string` | Level pembelajaran. |
| `durationMinutes` | `Course` atau `Module` | `xsd:integer` | Durasi dalam menit. |
| `contributionPercent` | `CourseCompetencyMapping` | `xsd:decimal` | Besar kontribusi kelas. |
| `competencyGain` | `CourseCompetencyMapping` | `xsd:integer` | Perkiraan kenaikan level. |
| `moduleTitle` | `Module` | `xsd:string` | Judul modul. |
| `moduleOrder` | `Module` | `xsd:integer` | Posisi modul dalam kelas. |

### 7.4 Bukti, rekomendasi, dan learning path

| Data property | Domain | Range | Catatan |
|---|---|---|---|
| `attemptScore` | `AssessmentAttempt` | `xsd:decimal` | 0–100. |
| `attemptedAt` | `AssessmentAttempt` | `xsd:dateTime` | — |
| `submissionReference` | `ProjectSubmission` | `xsd:anyURI` | Tautan artefak atau repositori. |
| `submissionStatus` | `ProjectSubmission` | `xsd:string` | Status penyimpanan kiriman. |
| `evidenceType` | `CompetencyEvidence` | `xsd:string` | Misalnya `assessment`, `project`, atau `course_performance`. |
| `evidenceScore` | `CompetencyEvidence` | `xsd:decimal` | 0–100. |
| `evidenceWeight` | `CompetencyEvidence` | `xsd:decimal` | 0–100. |
| `earnedAt` | `CompetencyEvidence` | `xsd:dateTime` | — |
| `matchScore` | `CareerRecommendation` | `xsd:decimal` | 0–100. |
| `readinessScore` | `CareerRecommendation` | `xsd:decimal` | 0–100. |
| `recommendationRank` | `CareerRecommendation` | `xsd:integer` | 1 adalah rekomendasi tertinggi. |
| `recommendationCategory` | `CareerRecommendation` | `xsd:string` | Kategori interpretasi hasil. |
| `recommendedAt` | `CareerRecommendation` | `xsd:dateTime` | Waktu hasil dihitung. |
| `pathStatus` | `LearningPath` | `xsd:string` | `active`, `completed`, atau `superseded`. |
| `generatedAt` | `LearningPath` | `xsd:dateTime` | Waktu jalur dibuat. |
| `itemPosition` | `LearningPathItem` | `xsd:integer` | Urutan langkah. |
| `itemStatus` | `LearningPathItem` | `xsd:string` | `already_competent`, `pending`, `in_progress`, atau `completed`. |
| `gapScore` | `LearningPathItem` | `xsd:decimal` | Selisih kebutuhan dan penguasaan. |

## 8. Batasan kardinalitas utama

| Class | Batasan yang disarankan |
|---|---|
| `Student` | tepat 1 `hasCareerProfile`; minimal 0 `hasStudentCompetency`; maksimal 1 `targetsCareer` aktif. |
| `CareerProfile` | tepat 1 `profileOf`; tepat 1 `prefersWorkStyle`. |
| `StudentCompetency` | tepat 1 `competencyRecordOf`; tepat 1 `recordsCompetency`. |
| `CareerRole` | tepat 1 `belongsToCluster`; minimal 1 `hasRequirement`. |
| `CareerRequirement` | tepat 1 `requirementOfCareer`; tepat 1 `requiresCompetency`. |
| `CourseCompetencyMapping` | tepat 1 `mappingOfCourse`; tepat 1 `teachesCompetency`. |
| `Module` | tepat 1 `moduleOfCourse`. |
| `AssessmentAttempt` | tepat 1 `attemptedBy`; tepat 1 `attemptOfAssessment`. |
| `ProjectSubmission` | tepat 1 `submittedBy`; tepat 1 `submissionOfProject`. |
| `CareerRecommendation` | tepat 1 `recommendationFor`; tepat 1 `recommendsCareer`. |
| `LearningPath` | tepat 1 `learningPathFor`; tepat 1 `pathTargetsCareer`; minimal 1 `hasLearningPathItem`. |
| `LearningPathItem` | tepat 1 `itemOfLearningPath`; tepat 1 `addressesCompetency`; maksimal 1 `recommendsCourse`. |

Catatan: pembatasan “maksimal satu target aktif” lebih aman ditegakkan juga pada aplikasi/database karena OWL memakai asumsi dunia terbuka.

## 9. Individual awal

### 9.1 Career cluster

1. Data & Analytics
2. Business & Information Systems
3. Software & Product Development
4. Infrastructure & Cloud
5. Cybersecurity & Governance
6. IT Management
7. Architecture
8. Emerging Technology & Consulting

### 9.2 Career role

1. Data Analyst
2. Business Intelligence Analyst
3. Data Engineer
4. Data Scientist
5. Database Administrator
6. Business Analyst
7. System Analyst
8. Product Manager / Product Owner
9. ERP / CRM Consultant
10. IT / Digital Consultant
11. Software / Web Developer
12. QA Engineer / Software Tester
13. UI/UX Designer
14. System / Network Administrator
15. Cloud / DevOps Engineer
16. Cybersecurity Analyst
17. IT Auditor / GRC Analyst
18. IT Project Manager / Scrum Master
19. IT Service Management / IT Support
20. Solution Architect
21. AI / Automation Analyst
22. Pre-Sales / Solution Consultant

### 9.3 Competency

#### Technical Competency

SQL; Database Design; Excel; Statistics; Python; Data Visualization; Power BI; Data Modeling; Data Engineering; Machine Learning; Programming & OOP; Web Development; API Development; Git & Version Control; Software Testing; UI Design; UX Research; Networking; Linux; Cloud Computing; Cybersecurity; Software Architecture; Solution Architecture; AI & Automation.

#### Business Competency

Business Understanding; Business Process Analysis; BPMN; Requirement Analysis; Stakeholder Management; Product Management; Project Management; Agile; IT Governance; ERP/CRM; IT Service Management.

#### Professional Competency

Communication; Presentation; Documentation; Collaboration; Problem Solving.

### 9.4 Work style

1. Analytical
2. Collaborative
3. Creative
4. Structured

## 10. Contoh koneksi individual

Contoh ini hanya menunjukkan bentuk relasi, belum merupakan aturan rekomendasi.

```text
student:RaniPutri rdf:type ispath:Student
profile:RaniProfile rdf:type ispath:CareerProfile
student:RaniPutri ispath:hasCareerProfile profile:RaniProfile
profile:RaniProfile ispath:prefersWorkStyle workstyle:Analytical

career:DataAnalyst rdf:type ispath:CareerRole
career:DataAnalyst ispath:belongsToCluster cluster:DataAnalytics
student:RaniPutri ispath:targetsCareer career:DataAnalyst

requirement:DataAnalyst_SQL rdf:type ispath:CareerRequirement
career:DataAnalyst ispath:hasRequirement requirement:DataAnalyst_SQL
requirement:DataAnalyst_SQL ispath:requiresCompetency competency:SQL
requirement:DataAnalyst_SQL ispath:minimumLevel "4"^^xsd:integer
requirement:DataAnalyst_SQL ispath:requirementWeight "25"^^xsd:decimal

studentCompetency:Rani_SQL rdf:type ispath:StudentCompetency
student:RaniPutri ispath:hasStudentCompetency studentCompetency:Rani_SQL
studentCompetency:Rani_SQL ispath:recordsCompetency competency:SQL
studentCompetency:Rani_SQL ispath:proficiencyLevel "3"^^xsd:integer

mapping:C03_SQL rdf:type ispath:CourseCompetencyMapping
course:C03 ispath:hasCourseCompetencyMapping mapping:C03_SQL
mapping:C03_SQL ispath:teachesCompetency competency:SQL

path:Rani_DataAnalyst_Path rdf:type ispath:LearningPath
student:RaniPutri ispath:hasLearningPath path:Rani_DataAnalyst_Path
path:Rani_DataAnalyst_Path ispath:pathTargetsCareer career:DataAnalyst

item:Rani_Path_01 rdf:type ispath:LearningPathItem
path:Rani_DataAnalyst_Path ispath:hasLearningPathItem item:Rani_Path_01
item:Rani_Path_01 ispath:addressesCompetency competency:SQL
item:Rani_Path_01 ispath:recommendsCourse course:C03
```

## 11. Alur pengetahuan yang ingin dicapai

```text
Student
  → memiliki CareerProfile dan StudentCompetency
  → dibandingkan dengan CareerRequirement milik setiap CareerRole
  → menghasilkan CareerRecommendation dan competency gap
  → memilih satu CareerRole sebagai target
  → memperoleh LearningPath
  → setiap LearningPathItem menyasar Competency dan merekomendasikan Course
  → AssessmentAttempt atau ProjectSubmission menghasilkan CompetencyEvidence
  → evidence memperbarui StudentCompetency
  → rekomendasi dan learning path dapat dihitung ulang
```

## 12. Keputusan yang perlu dikunci sebelum masuk Protégé

1. Apakah minat utama cukup disimpan sebagai teks atau perlu dijadikan class `InterestArea`.
2. Apakah status kiriman proyek hanya berarti tersimpan di portofolio, atau kelak dinilai otomatis berdasarkan rubrik.
3. Apakah hasil asesmen awal langsung menjadi `CompetencyEvidence` atau diberi bobot lebih kecil daripada proyek.
4. Nilai minimum, bobot, dan tipe requirement untuk seluruh 22 pekerjaan harus ditinjau sebagai data kurasi, bukan kebenaran universal.
5. Setelah keputusan di atas selesai, tahap berikutnya adalah membuat individual lengkap, constraint OWL, lalu aturan SWRL untuk rekomendasi awal dan learning path.

