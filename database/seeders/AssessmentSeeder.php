<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $competencies = DB::table('competencies')->pluck('id', 'name');
        $questionSets = $this->assessments();
        $required = collect($questionSets)->flatMap(fn (array $set) => collect($set['questions'])->pluck('competency'))->unique()->values();

        if ($required->contains(fn (string $name) => !$competencies->has($name))) {
            $this->command?->warn('Assessment belum dibuat karena master kompetensi belum lengkap.');
            return;
        }

        $now = now();
        foreach (DB::table('career_roles as r')->join('career_clusters as c', 'c.id', '=', 'r.career_cluster_id')->where('r.is_active', true)->select('r.*', 'c.name as cluster_name')->get() as $role) {
            DB::table('assessments')->where('title', "{$role->name} Competency Assessment")
                ->update(['title' => "Pemetaan Topik {$role->cluster_name}", 'updated_at' => $now]);
        }
        $legacyTitles = [
            'Pre-Assessment Kecocokan Karier SI' => 'Pre-Assessment Kecocokan Karier SI',
            'Diagnostic Data & Analytics' => 'Pemetaan Topik Data & Analytics',
            'Diagnostic Business & Information Systems' => 'Pemetaan Topik Business & Information Systems',
            'Diagnostic Software & Product Development' => 'Pemetaan Topik Software & Product Development',
            'Diagnostic Infrastructure & Cloud' => 'Pemetaan Topik Infrastructure & Cloud',
            'Diagnostic Cybersecurity & Governance' => 'Pemetaan Topik Cybersecurity & Governance',
            'Diagnostic IT Management' => 'Pemetaan Topik IT Management',
            'Diagnostic Architecture' => 'Pemetaan Topik Solution & Enterprise Architecture',
            'Diagnostic Emerging Technology & Consulting' => 'Pemetaan Topik Emerging Technology & Consulting',
            'Pemetaan Jalur Data & Analytics — Data Analyst dan Data Engineer' => 'Pemetaan Topik Data & Analytics',
            'Pemetaan Jalur Business & Information Systems — Business Analyst dan ERP/CRM' => 'Pemetaan Topik Business & Information Systems',
            'Pemetaan Jalur Software & Product Development — Software Engineer dan Product' => 'Pemetaan Topik Software & Product Development',
            'Pemetaan Jalur Infrastructure & Cloud — Cloud dan DevOps Engineer' => 'Pemetaan Topik Infrastructure & Cloud',
            'Pemetaan Jalur Cybersecurity & Governance — Security Analyst dan IT Governance' => 'Pemetaan Topik Cybersecurity & Governance',
            'Pemetaan Jalur IT Management — IT Project dan Service Management' => 'Pemetaan Topik IT Management',
            'Pemetaan Jalur Architecture — Solution dan Enterprise Architecture' => 'Pemetaan Topik Solution & Enterprise Architecture',
            'Pemetaan Jalur Emerging Technology & Consulting — AI, Automation dan Technology Consulting' => 'Pemetaan Topik Emerging Technology & Consulting',
        ];
        foreach ($legacyTitles as $oldTitle => $newTitle) {
            if ($oldTitle !== $newTitle && DB::table('assessments')->where('title', $oldTitle)->exists()) {
                DB::table('assessments')->where('title', $oldTitle)->update(['title' => $newTitle, 'updated_at' => $now]);
            }
        }
        foreach ($questionSets as $set) {
            $this->upsertAssessment($set, $competencies, $now);
        }

        foreach ($this->roleAssessments() as $set) {
            $this->upsertAssessment($set, $competencies, $now);
        }
    }

    private function upsertAssessment(array $set, $competencies, $now): void
    {
        $identity = [
            'assessment_scope' => $set['scope'] ?? 'global',
            'assessment_purpose' => $set['purpose'],
            'scope_reference_id' => $set['scope_reference_id'] ?? null,
        ];
        if (($set['scope'] ?? 'global') !== 'career_role') {
            $identity['title'] = $set['title'];
        }
        $existing = DB::table('assessments')->where($identity)->orderByDesc('id')->first();
        // Published revisions and historical questions must retain their original grading contract.
        if ($existing && (DB::table('assessment_attempts')->where('assessment_id', $existing->id)->exists()
            || DB::table('assessment_questions')->where('assessment_id', $existing->id)->where('prompt', 'not like', 'SECTION -%')->exists())) {
            return;
        }
        DB::table('assessments')->updateOrInsert(
            $identity,
            [
                'title' => $set['title'],
                'course_id' => null,
                'lesson_id' => null,
                'type' => 'exam',
                'passing_score' => $set['passing_score'],
                'max_attempts' => 3,
                'time_limit_minutes' => null,
                'attempt_strategy' => 'best',
                'is_published' => true,
                'assessment_scope' => $set['scope'] ?? 'global',
                'assessment_purpose' => $set['purpose'],
                'scope_reference_id' => $set['scope_reference_id'] ?? null,
                'opens_at' => null,
                'closes_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $assessmentId = DB::table('assessments')->where($identity)->value('id');
        $seenQuestionIds = [];

        foreach ($set['questions'] as $position => $question) {
            DB::table('assessment_questions')->updateOrInsert(
                ['assessment_id' => $assessmentId, 'position' => $position + 1],
                [
                    'type' => 'single_choice',
                    'prompt' => $question['prompt'],
                    'options' => json_encode($question['options']),
                    'answer_key' => json_encode([
                        'correct' => $question['correct'],
                        'explanation' => $question['explanation'],
                    ]),
                    'rubric' => null,
                    'max_score' => $question['weight'] ?? 1,
                    'measures_competency' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $questionId = DB::table('assessment_questions')
                ->where('assessment_id', $assessmentId)
                ->where('position', $position + 1)
                ->value('id');
            $seenQuestionIds[] = $questionId;

            DB::table('question_competencies')->updateOrInsert(
                ['assessment_question_id' => $questionId, 'competency_id' => $competencies[$question['competency']]],
                ['weight' => 100]
            );
            DB::table('question_competencies')
                ->where('assessment_question_id', $questionId)
                ->where('competency_id', '!=', $competencies[$question['competency']])
                ->delete();
        }

        DB::table('assessment_questions')
            ->where('assessment_id', $assessmentId)
            ->whereNotIn('id', $seenQuestionIds)
            ->delete();
    }

    private function roleAssessments(): array
    {
        $roles = DB::table('career_roles as r')->join('career_clusters as c', 'c.id', '=', 'r.career_cluster_id')
            ->where('r.is_active', true)->select('r.*', 'c.name as cluster_name')->orderBy('r.name')->get();
        $requirements = DB::table('career_role_competencies as crc')
            ->join('competencies as c', 'c.id', '=', 'crc.competency_id')
            ->select('crc.*', 'c.name as competency_name')
            ->orderByDesc('crc.is_required')
            ->orderByDesc('crc.weight')
            ->get()
            ->groupBy('job_role_id');

        return $roles->map(function ($role) use ($requirements) {
            $roleRequirements = $requirements->get($role->id, collect());

            return [
                'title' => "Pemetaan Topik {$role->cluster_name}",
                'purpose' => 'role_competency_assessment',
                'scope' => 'career_role',
                'scope_reference_id' => $role->id,
                'passing_score' => $this->passingScore($roleRequirements),
                'questions' => $roleRequirements->flatMap(fn ($requirement) => $this->roleQuestions($role->name, $requirement))->values()->all(),
            ];
        })->filter(fn (array $set) => count($set['questions']) > 0)->values()->all();
    }

    private function passingScore($requirements): int
    {
        $mandatory = $requirements->where('requirement_type', 'mandatory');
        $base = $mandatory->isNotEmpty() ? (int) round($mandatory->avg('minimum_level') * 20) : 70;
        return min(85, max(65, $base));
    }

    private function roleQuestions(string $roleName, object $requirement): array
    {
        $competency = $requirement->competency_name;
        $weight = $requirement->requirement_type === 'mandatory' ? 1.5 : 1;
        $questions = [[
            'competency' => $competency,
            'prompt' => "SECTION - {$competency}: Saat bekerja sebagai {$roleName}, tindakan mana yang paling tepat untuk membuktikan kemampuan {$competency}?",
            'options' => [
                'a' => "Menentukan kebutuhan, memilih teknik {$competency} yang sesuai, lalu memvalidasi hasilnya dengan data atau stakeholder.",
                'b' => "Menghafal definisi {$competency} tanpa menghubungkannya dengan konteks pekerjaan.",
                'c' => 'Menunda analisis sampai semua masalah selesai oleh tim lain.',
                'd' => 'Memakai template lama tanpa mengecek tujuan, risiko, dan batasannya.',
            ],
            'correct' => 'a',
            'explanation' => "{$competency} untuk role {$roleName} harus terlihat dari keputusan praktis, validasi, dan dampaknya pada kebutuhan kerja.",
            'weight' => $weight,
        ]];

        if ($requirement->requirement_type === 'mandatory' || (float) $requirement->weight >= 12) {
            $questions[] = [
                'competency' => $competency,
                'prompt' => "SECTION - {$competency}: Output kerja menunjukkan masalah pada {$competency}. Langkah evaluasi paling kuat adalah...",
                'options' => [
                    'a' => 'Mencari bukti penyebab, membandingkan dengan requirement, memperbaiki prioritas, lalu mendokumentasikan keputusan.',
                    'b' => 'Mengganti seluruh solusi tanpa mengecek bagian yang masih valid.',
                    'c' => 'Mengabaikan temuan karena assessment sebelumnya sudah cukup baik.',
                    'd' => 'Memilih tool paling populer tanpa mengukur kecocokan masalah.',
                ],
                'correct' => 'a',
                'explanation' => "Evaluasi {$competency} yang matang dimulai dari bukti, requirement, prioritas perbaikan, dan dokumentasi keputusan.",
                'weight' => $weight,
            ];
        }

        return $questions;
    }

    private function assessments(): array
    {
        return [
            [
                'title' => 'Pre-Assessment Kecocokan Karier SI',
                'purpose' => 'career_diagnostic',
                'passing_score' => 60,
                'questions' => array_merge(
                    $this->dataQuestions(),
                    $this->businessInformationSystemQuestions(),
                    $this->softwareProductQuestions(),
                    $this->infrastructureCloudQuestions(),
                    $this->cybersecurityGovernanceQuestions(),
                    $this->itManagementQuestions(),
                    $this->architectureQuestions(),
                    $this->emergingConsultingQuestions(),
                ),
            ],
            [
                'title' => 'Pemetaan Topik Data & Analytics',
                'purpose' => 'competency_post_assessment',
                'passing_score' => 65,
                'questions' => $this->dataQuestions(),
            ],
            [
                'title' => 'Pemetaan Topik Business & Information Systems',
                'purpose' => 'competency_post_assessment',
                'passing_score' => 65,
                'questions' => $this->businessInformationSystemQuestions(),
            ],
            [
                'title' => 'Pemetaan Topik Software & Product Development',
                'purpose' => 'competency_post_assessment',
                'passing_score' => 65,
                'questions' => $this->softwareProductQuestions(),
            ],
            [
                'title' => 'Pemetaan Topik Infrastructure & Cloud',
                'purpose' => 'competency_post_assessment',
                'passing_score' => 65,
                'questions' => $this->infrastructureCloudQuestions(),
            ],
            [
                'title' => 'Pemetaan Topik Cybersecurity & Governance',
                'purpose' => 'competency_post_assessment',
                'passing_score' => 65,
                'questions' => $this->cybersecurityGovernanceQuestions(),
            ],
            [
                'title' => 'Pemetaan Topik IT Management',
                'purpose' => 'competency_post_assessment',
                'passing_score' => 65,
                'questions' => $this->itManagementQuestions(),
            ],
            [
                'title' => 'Pemetaan Topik Solution & Enterprise Architecture',
                'purpose' => 'competency_post_assessment',
                'passing_score' => 65,
                'questions' => $this->architectureQuestions(),
            ],
            [
                'title' => 'Pemetaan Topik Emerging Technology & Consulting',
                'purpose' => 'competency_post_assessment',
                'passing_score' => 65,
                'questions' => $this->emergingConsultingQuestions(),
            ],
        ];
    }

    private function dataQuestions(): array
    {
        return [
            ['competency' => 'SQL', 'prompt' => 'Klausa SQL mana yang digunakan untuk menyaring baris sebelum hasil ditampilkan?', 'options' => ['a' => 'ORDER BY', 'b' => 'WHERE', 'c' => 'GROUP BY', 'd' => 'SELECT'], 'correct' => 'b', 'explanation' => 'WHERE menyaring baris berdasarkan kondisi sebelum hasil query ditampilkan.'],
            ['competency' => 'SQL', 'prompt' => 'Kamu ingin menampilkan semua pelanggan, termasuk pelanggan yang belum pernah membuat pesanan. Jenis JOIN yang tepat adalah...', 'options' => ['a' => 'INNER JOIN', 'b' => 'CROSS JOIN', 'c' => 'LEFT JOIN dari tabel pelanggan', 'd' => 'SELF JOIN'], 'correct' => 'c', 'explanation' => 'LEFT JOIN mempertahankan seluruh baris dari tabel pelanggan dan mengisi data pesanan yang tidak ada dengan NULL.'],
            ['competency' => 'Excel', 'prompt' => 'Fitur Excel yang paling tepat untuk merangkum total penjualan berdasarkan wilayah dan bulan adalah...', 'options' => ['a' => 'PivotTable', 'b' => 'Goal Seek', 'c' => 'Data Validation', 'd' => 'Find and Replace'], 'correct' => 'a', 'explanation' => 'PivotTable dirancang untuk mengelompokkan dan meringkas data berdasarkan beberapa dimensi.'],
            ['competency' => 'Statistics', 'prompt' => 'Dua variabel memiliki korelasi yang tinggi. Kesimpulan yang paling tepat adalah...', 'options' => ['a' => 'Salah satu pasti menyebabkan yang lain', 'b' => 'Keduanya selalu memiliki satuan yang sama', 'c' => 'Ada hubungan, tetapi sebab-akibat belum terbukti', 'd' => 'Data pasti bebas dari bias'], 'correct' => 'c', 'explanation' => 'Korelasi menunjukkan keterkaitan, bukan bukti bahwa satu variabel menyebabkan variabel lainnya.'],
            ['competency' => 'Python', 'prompt' => 'Struktur utama pada pandas untuk menyimpan data berbentuk tabel dua dimensi adalah...', 'options' => ['a' => 'DataFrame', 'b' => 'Tuple', 'c' => 'Set', 'd' => 'Generator'], 'correct' => 'a', 'explanation' => 'DataFrame menyimpan data tabular dalam baris dan kolom berlabel.'],
            ['competency' => 'Power BI', 'prompt' => 'Dalam model Power BI, hubungan antartabel terutama digunakan untuk...', 'options' => ['a' => 'Mengubah warna visual', 'b' => 'Meneruskan konteks filter antartabel', 'c' => 'Mengganti nama file sumber', 'd' => 'Menyimpan screenshot dashboard'], 'correct' => 'b', 'explanation' => 'Relationship memungkinkan konteks filter mengalir sehingga measure dihitung dari tabel yang saling terkait.'],
        ];
    }

    private function businessQuestions(): array
    {
        return [
            ['competency' => 'Business Understanding', 'prompt' => 'Sebelum membuat dashboard, langkah yang paling membantu memastikan analisis relevan adalah...', 'options' => ['a' => 'Memilih warna grafik', 'b' => 'Menambah seluruh data yang tersedia', 'c' => 'Menetapkan keputusan bisnis dan KPI yang perlu didukung', 'd' => 'Menghapus nilai yang tidak sesuai dugaan'], 'correct' => 'c', 'explanation' => 'Keputusan dan KPI menentukan pertanyaan analisis serta data yang benar-benar diperlukan.'],
            ['competency' => 'Business Process Analysis', 'prompt' => 'Tujuan utama memetakan proses bisnis saat awal proyek sistem adalah...', 'options' => ['a' => 'Membuat dokumen lebih tebal', 'b' => 'Menemukan alur kerja, aktor, dan titik masalah', 'c' => 'Mengganti semua proses manual dengan aplikasi', 'd' => 'Menentukan warna antarmuka'], 'correct' => 'b', 'explanation' => 'Pemetaan proses membantu menemukan alur, tanggung jawab, bottleneck, dan kebutuhan perbaikan.'],
            ['competency' => 'Requirement Analysis', 'prompt' => 'Contoh requirement non-fungsional adalah...', 'options' => ['a' => 'Pengguna dapat mengunduh laporan', 'b' => 'Admin dapat membuat akun', 'c' => 'Halaman laporan terbuka di bawah dua detik', 'd' => 'Manajer dapat menyetujui transaksi'], 'correct' => 'c', 'explanation' => 'Non-fungsional menjelaskan kualitas sistem seperti performa, keamanan, reliabilitas, dan usability.'],
            ['competency' => 'Product Management', 'prompt' => 'Saat banyak ide fitur masuk, product manager sebaiknya memprioritaskan berdasarkan...', 'options' => ['a' => 'Fitur yang paling ramai dibahas', 'b' => 'Dampak ke tujuan produk, effort, dan bukti kebutuhan pengguna', 'c' => 'Urutan permintaan terbaru', 'd' => 'Teknologi yang sedang tren'], 'correct' => 'b', 'explanation' => 'Prioritas produk perlu menimbang dampak, effort, strategi, dan bukti masalah pengguna.'],
            ['competency' => 'Project Management', 'prompt' => 'Risiko proyek berbeda dari isu karena risiko...', 'options' => ['a' => 'Sudah pasti terjadi', 'b' => 'Kemungkinan terjadi dan perlu mitigasi', 'c' => 'Selalu berasal dari tim teknis', 'd' => 'Tidak perlu dicatat'], 'correct' => 'b', 'explanation' => 'Risiko adalah kejadian potensial; isu adalah masalah yang sudah terjadi.'],
            ['competency' => 'Stakeholder Management', 'prompt' => 'Stakeholder dengan pengaruh tinggi dan minat tinggi sebaiknya dikelola dengan cara...', 'options' => ['a' => 'Monitor sesekali', 'b' => 'Keep satisfied saja', 'c' => 'Manage closely dengan komunikasi aktif', 'd' => 'Abaikan sampai proyek selesai'], 'correct' => 'c', 'explanation' => 'Pengaruh dan minat tinggi membutuhkan komunikasi dekat karena keputusan mereka berdampak besar.'],
        ];
    }

    private function businessInformationSystemQuestions(): array
    {
        return array_merge($this->businessQuestions(), [
            ['competency' => 'BPMN', 'prompt' => 'Dalam BPMN, gateway biasanya dipakai untuk...', 'options' => ['a' => 'Menunjukkan percabangan atau penggabungan alur proses', 'b' => 'Menyimpan password user', 'c' => 'Mengganti desain database', 'd' => 'Mengukur kecepatan server'], 'correct' => 'a', 'explanation' => 'Gateway memodelkan keputusan, percabangan, dan penggabungan alur proses.'],
            ['competency' => 'ERP/CRM', 'prompt' => 'CRM berfokus pada...', 'options' => ['a' => 'Pengelolaan hubungan dan interaksi pelanggan', 'b' => 'Konfigurasi router kantor', 'c' => 'Kompilasi kode backend', 'd' => 'Desain kabel jaringan'], 'correct' => 'a', 'explanation' => 'CRM membantu organisasi mengelola prospek, pelanggan, aktivitas penjualan, dan layanan.'],
        ]);
    }

    private function softwareProductQuestions(): array
    {
        return array_merge($this->softwareQuestions(), [
            ['competency' => 'UX Research', 'prompt' => 'Wawancara pengguna paling berguna untuk...', 'options' => ['a' => 'Memahami kebutuhan, perilaku, dan konteks pengguna', 'b' => 'Mengganti semua validasi aplikasi', 'c' => 'Memilih framework backend', 'd' => 'Menghapus backlog produk'], 'correct' => 'a', 'explanation' => 'UX research menggali masalah pengguna sebelum solusi dirancang.'],
            ['competency' => 'Product Management', 'prompt' => 'MVP dibuat untuk...', 'options' => ['a' => 'Menguji nilai inti produk dengan scope kecil', 'b' => 'Menunda feedback sampai semua fitur selesai', 'c' => 'Menghapus kebutuhan pengguna', 'd' => 'Menghindari prioritas produk'], 'correct' => 'a', 'explanation' => 'MVP membantu tim belajar cepat dari fitur inti yang paling penting.'],
        ]);
    }

    private function infrastructureCloudQuestions(): array
    {
        return [
            ['competency' => 'Networking', 'prompt' => 'DNS digunakan untuk...', 'options' => ['a' => 'Menerjemahkan nama domain menjadi alamat IP', 'b' => 'Mengenkripsi file lokal', 'c' => 'Menghapus database', 'd' => 'Membuat desain UI'], 'correct' => 'a', 'explanation' => 'DNS menerjemahkan nama domain agar client menemukan alamat server.'],
            ['competency' => 'Linux', 'prompt' => 'Perintah Linux untuk melihat isi direktori adalah...', 'options' => ['a' => 'cd', 'b' => 'ls', 'c' => 'pwd', 'd' => 'mkdir'], 'correct' => 'b', 'explanation' => 'ls menampilkan daftar file dan direktori.'],
            ['competency' => 'Cloud Computing', 'prompt' => 'Manfaat utama autoscaling di cloud adalah...', 'options' => ['a' => 'Menambah atau mengurangi kapasitas sesuai beban', 'b' => 'Menghapus kebutuhan monitoring', 'c' => 'Mengganti seluruh kode aplikasi', 'd' => 'Mematikan backup'], 'correct' => 'a', 'explanation' => 'Autoscaling menjaga kapasitas seimbang dengan trafik dan biaya.'],
            ['competency' => 'Git & Version Control', 'prompt' => 'Kenapa infrastruktur sering disimpan sebagai kode?', 'options' => ['a' => 'Agar perubahan bisa ditinjau, dilacak, dan diulang', 'b' => 'Agar password boleh ditaruh di repository', 'c' => 'Agar server tidak perlu dipantau', 'd' => 'Agar biaya cloud selalu nol'], 'correct' => 'a', 'explanation' => 'Infrastructure as Code membuat konfigurasi lebih konsisten dan auditable.'],
        ];
    }

    private function cybersecurityGovernanceQuestions(): array
    {
        return [
            ['competency' => 'Cybersecurity', 'prompt' => 'Prinsip least privilege berarti...', 'options' => ['a' => 'Semua user memakai akses admin', 'b' => 'Akses diberikan sesuai kebutuhan minimum', 'c' => 'Password dibagi lewat chat', 'd' => 'Log keamanan dimatikan'], 'correct' => 'b', 'explanation' => 'Least privilege membatasi akses agar dampak kesalahan atau kompromi lebih kecil.'],
            ['competency' => 'IT Governance', 'prompt' => 'Kontrol IT dibuat terutama untuk...', 'options' => ['a' => 'Memperlambat pekerjaan tanpa alasan', 'b' => 'Mengurangi risiko dan memastikan proses sesuai kebijakan', 'c' => 'Menghapus kebutuhan audit', 'd' => 'Mengganti semua keputusan bisnis'], 'correct' => 'b', 'explanation' => 'Kontrol membantu organisasi mengelola risiko, kepatuhan, dan kualitas layanan.'],
            ['competency' => 'Documentation', 'prompt' => 'Dokumentasi insiden keamanan harus mencatat...', 'options' => ['a' => 'Timeline, dampak, tindakan, bukti, dan perbaikan', 'b' => 'Opini tanpa bukti', 'c' => 'Password akun terdampak', 'd' => 'Hanya nama pelapor'], 'correct' => 'a', 'explanation' => 'Catatan insiden yang lengkap mendukung audit dan perbaikan kontrol.'],
            ['competency' => 'Networking', 'prompt' => 'Firewall paling tepat digunakan untuk...', 'options' => ['a' => 'Mengatur trafik jaringan berdasarkan aturan keamanan', 'b' => 'Mendesain wireframe aplikasi', 'c' => 'Menghitung KPI produk', 'd' => 'Membuat query laporan'], 'correct' => 'a', 'explanation' => 'Firewall membatasi trafik masuk dan keluar sesuai kebijakan keamanan.'],
        ];
    }

    private function itManagementQuestions(): array
    {
        return [
            ['competency' => 'Project Management', 'prompt' => 'Risiko proyek berbeda dari isu karena risiko...', 'options' => ['a' => 'Sudah pasti terjadi', 'b' => 'Kemungkinan terjadi dan perlu mitigasi', 'c' => 'Selalu berasal dari tim teknis', 'd' => 'Tidak perlu dicatat'], 'correct' => 'b', 'explanation' => 'Risiko adalah kejadian potensial; isu adalah masalah yang sudah terjadi.'],
            ['competency' => 'IT Service Management', 'prompt' => 'Incident management bertujuan utama untuk...', 'options' => ['a' => 'Memulihkan layanan secepat mungkin', 'b' => 'Menambah scope proyek', 'c' => 'Menghapus catatan masalah', 'd' => 'Mengganti semua perangkat'], 'correct' => 'a', 'explanation' => 'Incident management fokus pada pemulihan layanan dan pengurangan dampak ke pengguna.'],
            ['competency' => 'Agile', 'prompt' => 'Sprint retrospective digunakan untuk...', 'options' => ['a' => 'Mengevaluasi cara kerja tim dan menentukan perbaikan berikutnya', 'b' => 'Menambah requirement diam-diam', 'c' => 'Menghapus backlog', 'd' => 'Mengganti customer feedback'], 'correct' => 'a', 'explanation' => 'Retrospective membantu tim memperbaiki proses kerja secara berkelanjutan.'],
            ['competency' => 'Collaboration', 'prompt' => 'Ketika ada konflik prioritas di tim, langkah awal yang sehat adalah...', 'options' => ['a' => 'Menyepakati tujuan, constraint, dan dampak tiap pilihan', 'b' => 'Mengabaikan anggota yang berbeda pendapat', 'c' => 'Langsung mengganti semua rencana', 'd' => 'Menghapus dokumentasi keputusan'], 'correct' => 'a', 'explanation' => 'Kolaborasi efektif membutuhkan tujuan bersama dan trade-off yang terlihat.'],
        ];
    }

    private function architectureQuestions(): array
    {
        return [
            ['competency' => 'Software Architecture', 'prompt' => 'Trade-off arsitektur berarti...', 'options' => ['a' => 'Keputusan yang menukar satu kualitas sistem dengan kualitas lain', 'b' => 'Semua pilihan selalu gratis', 'c' => 'Dokumentasi tidak perlu dibuat', 'd' => 'Database selalu dihapus'], 'correct' => 'a', 'explanation' => 'Arsitektur menimbang kualitas seperti performa, keamanan, maintainability, dan biaya.'],
            ['competency' => 'Solution Architecture', 'prompt' => 'Diagram arsitektur solusi yang baik terutama menunjukkan...', 'options' => ['a' => 'Komponen, integrasi, data flow, dan batas sistem', 'b' => 'Warna favorit tim', 'c' => 'Daftar chat pribadi', 'd' => 'Semua logo vendor tanpa konteks'], 'correct' => 'a', 'explanation' => 'Diagram solusi harus memperjelas struktur sistem dan hubungan antarkomponen.'],
            ['competency' => 'Data Modeling', 'prompt' => 'Normalisasi database membantu untuk...', 'options' => ['a' => 'Mengurangi duplikasi dan anomali data', 'b' => 'Menghilangkan kebutuhan backup', 'c' => 'Membuat query selalu lambat', 'd' => 'Menghapus relasi antartabel'], 'correct' => 'a', 'explanation' => 'Normalisasi membuat struktur data lebih konsisten dan mudah dijaga.'],
            ['competency' => 'API Development', 'prompt' => 'Kontrak API penting karena...', 'options' => ['a' => 'Menyepakati format request, response, dan error antarsistem', 'b' => 'Membuat UI otomatis bagus', 'c' => 'Menghapus kebutuhan autentikasi', 'd' => 'Menyimpan seluruh data di browser'], 'correct' => 'a', 'explanation' => 'Kontrak API mengurangi miskomunikasi antara service dan client.'],
        ];
    }

    private function emergingConsultingQuestions(): array
    {
        return [
            ['competency' => 'AI & Automation', 'prompt' => 'Automasi proses bisnis paling tepat dimulai dari proses yang...', 'options' => ['a' => 'Berulang, berbasis aturan, dan volumenya cukup tinggi', 'b' => 'Jarang terjadi dan tidak jelas pemiliknya', 'c' => 'Selalu membutuhkan keputusan etis kompleks', 'd' => 'Tidak punya data input'], 'correct' => 'a', 'explanation' => 'Proses repetitif dan berbasis aturan paling mudah memberi dampak awal dari automasi.'],
            ['competency' => 'Machine Learning', 'prompt' => 'Model machine learning perlu data validasi agar...', 'options' => ['a' => 'Kemampuan generalisasi bisa diuji pada data yang tidak dilatih', 'b' => 'Akurasi training selalu menjadi 100%', 'c' => 'Fitur bisnis tidak perlu dipahami', 'd' => 'Semua bias otomatis hilang'], 'correct' => 'a', 'explanation' => 'Validasi membantu mendeteksi overfitting dan memperkirakan performa nyata.'],
            ['competency' => 'Business Understanding', 'prompt' => 'Dalam konsultasi teknologi, solusi sebaiknya dimulai dari...', 'options' => ['a' => 'Masalah bisnis, dampak, constraint, dan stakeholder', 'b' => 'Tool yang sedang ramai', 'c' => 'Kode sebelum kebutuhan jelas', 'd' => 'Dashboard tanpa keputusan target'], 'correct' => 'a', 'explanation' => 'Konsultasi yang baik menautkan teknologi ke hasil bisnis yang diukur.'],
            ['competency' => 'Presentation', 'prompt' => 'Presentasi rekomendasi ke manajemen sebaiknya menonjolkan...', 'options' => ['a' => 'Masalah, bukti, rekomendasi, dan next step', 'b' => 'Semua teks laporan tanpa seleksi', 'c' => 'Animasi sebanyak mungkin', 'd' => 'Istilah asing tanpa definisi'], 'correct' => 'a', 'explanation' => 'Presentasi rekomendasi harus membantu audiens mengambil keputusan.'],
        ];
    }

    private function softwareQuestions(): array
    {
        return [
            ['competency' => 'Programming & OOP', 'prompt' => 'Dalam OOP, enkapsulasi terutama membantu...', 'options' => ['a' => 'Menyembunyikan detail internal dan menjaga akses data', 'b' => 'Menghapus semua error runtime', 'c' => 'Membuat program tanpa fungsi', 'd' => 'Menjalankan database otomatis'], 'correct' => 'a', 'explanation' => 'Enkapsulasi membatasi akses langsung ke state internal dan menyediakan operasi yang aman.'],
            ['competency' => 'Web Development', 'prompt' => 'HTTP status code 404 berarti...', 'options' => ['a' => 'Permintaan berhasil', 'b' => 'Resource tidak ditemukan', 'c' => 'Server sedang sibuk', 'd' => 'User berhasil login'], 'correct' => 'b', 'explanation' => '404 menunjukkan resource yang diminta tidak tersedia pada URL tersebut.'],
            ['competency' => 'API Development', 'prompt' => 'Endpoint REST untuk mengambil detail satu course biasanya menggunakan...', 'options' => ['a' => 'GET /courses/{id}', 'b' => 'POST /courses/delete', 'c' => 'PATCH /login', 'd' => 'PUT /search/all'], 'correct' => 'a', 'explanation' => 'GET digunakan untuk membaca resource tanpa mengubah state server.'],
            ['competency' => 'Git & Version Control', 'prompt' => 'Perintah Git untuk membuat cabang baru sekaligus pindah ke cabang itu adalah...', 'options' => ['a' => 'git status', 'b' => 'git checkout -b nama-cabang', 'c' => 'git log --oneline', 'd' => 'git remote -v'], 'correct' => 'b', 'explanation' => 'git checkout -b membuat branch baru dan langsung berpindah ke branch tersebut.'],
            ['competency' => 'Software Testing', 'prompt' => 'Test case yang baik minimal memuat...', 'options' => ['a' => 'Nama developer saja', 'b' => 'Langkah uji, data/input, dan expected result', 'c' => 'Screenshot warna aplikasi', 'd' => 'Daftar library frontend'], 'correct' => 'b', 'explanation' => 'Test case perlu menjelaskan cara menjalankan uji dan hasil yang diharapkan.'],
            ['competency' => 'UI Design', 'prompt' => 'Kontras teks yang baik penting karena...', 'options' => ['a' => 'Membuat halaman selalu lebih cepat', 'b' => 'Membantu keterbacaan dan aksesibilitas', 'c' => 'Menghapus kebutuhan navigasi', 'd' => 'Mengubah database otomatis'], 'correct' => 'b', 'explanation' => 'Kontras memengaruhi keterbacaan, terutama untuk pengguna dengan penglihatan terbatas.'],
        ];
    }

    private function infrastructureQuestions(): array
    {
        return [
            ['competency' => 'Networking', 'prompt' => 'DNS digunakan untuk...', 'options' => ['a' => 'Menerjemahkan nama domain menjadi alamat IP', 'b' => 'Mengenkripsi file lokal', 'c' => 'Menulis kode HTML', 'd' => 'Menghapus cache browser'], 'correct' => 'a', 'explanation' => 'DNS memetakan nama domain yang mudah dibaca ke alamat IP yang dipakai jaringan.'],
            ['competency' => 'Linux', 'prompt' => 'Perintah Linux untuk melihat isi direktori adalah...', 'options' => ['a' => 'cd', 'b' => 'ls', 'c' => 'pwd', 'd' => 'mkdir'], 'correct' => 'b', 'explanation' => 'ls menampilkan file dan folder pada direktori.'],
            ['competency' => 'Cloud Computing', 'prompt' => 'Manfaat utama autoscaling di cloud adalah...', 'options' => ['a' => 'Menambah atau mengurangi kapasitas sesuai beban', 'b' => 'Menghapus kebutuhan backup', 'c' => 'Membuat semua layanan gratis', 'd' => 'Menonaktifkan monitoring'], 'correct' => 'a', 'explanation' => 'Autoscaling menjaga kapasitas selaras dengan traffic dan efisiensi biaya.'],
            ['competency' => 'Cybersecurity', 'prompt' => 'Prinsip least privilege berarti...', 'options' => ['a' => 'Semua user memakai akses admin', 'b' => 'Akses diberikan sesuai kebutuhan minimum', 'c' => 'Password tidak perlu diganti', 'd' => 'Log keamanan dimatikan'], 'correct' => 'b', 'explanation' => 'Least privilege membatasi dampak kesalahan atau kompromi akun.'],
            ['competency' => 'Data Engineering', 'prompt' => 'Pipeline ETL biasanya berarti...', 'options' => ['a' => 'Extract, Transform, Load', 'b' => 'Edit, Test, Launch', 'c' => 'Encrypt, Tokenize, Login', 'd' => 'Evaluate, Train, Label'], 'correct' => 'a', 'explanation' => 'ETL mengambil data, mengubahnya sesuai kebutuhan, lalu memuatnya ke target.'],
            ['competency' => 'Solution Architecture', 'prompt' => 'Diagram arsitektur solusi yang baik terutama menunjukkan...', 'options' => ['a' => 'Komponen, integrasi, data flow, dan batas tanggung jawab', 'b' => 'Warna favorit tim', 'c' => 'Semua baris kode aplikasi', 'd' => 'Daftar cuti anggota tim'], 'correct' => 'a', 'explanation' => 'Arsitektur membantu pihak teknis dan bisnis memahami komponen, integrasi, serta trade-off.'],
        ];
    }

    private function governanceQuestions(): array
    {
        return [
            ['competency' => 'IT Governance', 'prompt' => 'Kontrol IT dibuat terutama untuk...', 'options' => ['a' => 'Memperlambat pekerjaan tanpa alasan', 'b' => 'Mengurangi risiko dan memastikan proses sesuai tujuan', 'c' => 'Menghapus kebutuhan audit', 'd' => 'Mengganti semua SOP dengan chat'], 'correct' => 'b', 'explanation' => 'Kontrol menjaga proses tetap aman, dapat diaudit, dan selaras dengan tujuan organisasi.'],
            ['competency' => 'ERP/CRM', 'prompt' => 'CRM berfokus pada...', 'options' => ['a' => 'Pengelolaan hubungan dan interaksi pelanggan', 'b' => 'Konfigurasi router kantor', 'c' => 'Desain logo perusahaan', 'd' => 'Kompilasi kode backend'], 'correct' => 'a', 'explanation' => 'CRM membantu mencatat, mengelola, dan menganalisis hubungan dengan pelanggan.'],
            ['competency' => 'IT Service Management', 'prompt' => 'Incident management bertujuan utama untuk...', 'options' => ['a' => 'Memulihkan layanan secepat mungkin', 'b' => 'Mencari fitur baru', 'c' => 'Membuat kampanye marketing', 'd' => 'Menyusun laporan keuangan'], 'correct' => 'a', 'explanation' => 'Incident management fokus mengembalikan layanan agar dampak ke pengguna minimum.'],
            ['competency' => 'AI & Automation', 'prompt' => 'Automasi proses bisnis paling tepat dimulai dari proses yang...', 'options' => ['a' => 'Berulang, berbasis aturan, dan volumenya cukup besar', 'b' => 'Selalu berubah setiap jam', 'c' => 'Tidak memiliki data input', 'd' => 'Membutuhkan keputusan etis kompleks tanpa review'], 'correct' => 'a', 'explanation' => 'Proses berulang dan berbasis aturan biasanya memberi manfaat automasi paling jelas.'],
            ['competency' => 'UX Research', 'prompt' => 'Wawancara pengguna paling berguna untuk...', 'options' => ['a' => 'Memahami kebutuhan, perilaku, dan konteks pengguna', 'b' => 'Mengganti semua validasi data', 'c' => 'Menentukan harga server', 'd' => 'Menulis query SQL otomatis'], 'correct' => 'a', 'explanation' => 'Riset pengguna menggali masalah nyata sebelum solusi dirancang.'],
            ['competency' => 'BPMN', 'prompt' => 'Dalam BPMN, gateway biasanya dipakai untuk...', 'options' => ['a' => 'Menunjukkan percabangan atau penggabungan alur proses', 'b' => 'Menyimpan password', 'c' => 'Menggambar struktur database', 'd' => 'Mengatur warna dashboard'], 'correct' => 'a', 'explanation' => 'Gateway mengatur keputusan, percabangan, dan penggabungan flow proses.'],
        ];
    }

    private function professionalQuestions(): array
    {
        return [
            ['competency' => 'Communication', 'prompt' => 'Saat menjelaskan temuan teknis ke stakeholder non-teknis, pendekatan terbaik adalah...', 'options' => ['a' => 'Memakai jargon sebanyak mungkin', 'b' => 'Mulai dari dampak bisnis lalu jelaskan bukti utama', 'c' => 'Mengirim raw log tanpa konteks', 'd' => 'Menyembunyikan batasan analisis'], 'correct' => 'b', 'explanation' => 'Stakeholder perlu memahami dampak, keputusan yang bisa diambil, dan batasan bukti.'],
            ['competency' => 'Presentation', 'prompt' => 'Slide rekomendasi yang kuat sebaiknya menonjolkan...', 'options' => ['a' => 'Masalah, bukti, rekomendasi, dan next step', 'b' => 'Semua teks laporan tanpa seleksi', 'c' => 'Animasi sebanyak mungkin', 'd' => 'Istilah asing tanpa definisi'], 'correct' => 'a', 'explanation' => 'Presentasi rekomendasi harus membantu audiens mengambil keputusan.'],
            ['competency' => 'Documentation', 'prompt' => 'Dokumentasi teknis yang baik harus...', 'options' => ['a' => 'Menjelaskan tujuan, langkah, asumsi, dan batasan', 'b' => 'Disimpan hanya di chat pribadi', 'c' => 'Berisi screenshot tanpa teks', 'd' => 'Tidak pernah diperbarui'], 'correct' => 'a', 'explanation' => 'Dokumentasi berguna saat orang lain bisa mengikuti konteks dan langkahnya.'],
            ['competency' => 'Collaboration', 'prompt' => 'Ketika ada konflik prioritas di tim, langkah awal yang sehat adalah...', 'options' => ['a' => 'Menyepakati tujuan, constraint, dan dampak tiap pilihan', 'b' => 'Mengabaikan anggota yang berbeda pendapat', 'c' => 'Langsung mengganti semua rencana', 'd' => 'Menghapus dokumentasi keputusan'], 'correct' => 'a', 'explanation' => 'Kolaborasi efektif membutuhkan tujuan bersama dan trade-off yang terlihat.'],
            ['competency' => 'Problem Solving', 'prompt' => 'Root cause analysis membantu tim agar...', 'options' => ['a' => 'Menangani penyebab utama, bukan gejala saja', 'b' => 'Menyalahkan satu orang', 'c' => 'Menghindari data', 'd' => 'Menambah fitur tanpa alasan'], 'correct' => 'a', 'explanation' => 'Analisis akar masalah mengurangi risiko masalah yang sama berulang.'],
            ['competency' => 'Agile', 'prompt' => 'Sprint retrospective digunakan untuk...', 'options' => ['a' => 'Mengevaluasi cara kerja tim dan menentukan perbaikan berikutnya', 'b' => 'Menambah requirement diam-diam', 'c' => 'Menghapus backlog', 'd' => 'Mengganti customer feedback'], 'correct' => 'a', 'explanation' => 'Retrospective membantu tim memperbaiki proses kerja secara berkelanjutan.'],
        ];
    }
}



