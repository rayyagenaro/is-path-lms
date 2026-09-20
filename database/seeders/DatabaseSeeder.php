<?php

namespace Database\Seeders;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'rani@ispath.id')->exists()) {
            $this->call(AssessmentSeeder::class);
            return;
        }

        $now = now();
        $studentUser = User::create(['name'=>'Rani Putri','email'=>'rani@ispath.id','password'=>Hash::make('password'),'role'=>'student']);
        $studentId = DB::table('students')->insertGetId(['user_id'=>$studentUser->id,'nim'=>'221011400123','cohort_year'=>2022,'study_program'=>'Sistem Informasi','created_at'=>$now,'updated_at'=>$now]);

        $competencyGroups = [
            'Technical'=>['SQL','Database Design','Excel','Statistics','Python','Data Visualization','Power BI','Data Modeling','Data Engineering','Machine Learning','Programming & OOP','Web Development','API Development','Git & Version Control','Software Testing','UI Design','UX Research','Networking','Linux','Cloud Computing','Cybersecurity','Software Architecture','Solution Architecture','AI & Automation'],
            'Business'=>['Business Understanding','Business Process Analysis','BPMN','Requirement Analysis','Stakeholder Management','Product Management','Project Management','Agile','IT Governance','ERP/CRM','IT Service Management'],
            'Professional'=>['Communication','Presentation','Documentation','Collaboration','Problem Solving'],
        ];
        $competencyIds=[];
        foreach ($competencyGroups as $group=>$names) foreach ($names as $index=>$name) {
            $competencyIds[$name]=DB::table('competencies')->insertGetId([
                'name'=>$name,'code'=>strtoupper(substr($group,0,1)).'-'.str_pad((string)($index+1),2,'0',STR_PAD_LEFT),
                'category'=>$this->categoryFor($name),'category_group'=>$group,'description'=>"Kemampuan {$name} yang dapat dibuktikan melalui pembelajaran, assessment, dan proyek.",
                'level_type'=>'skill','expected_evidence_count'=>3,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now,
            ]);
        }
        foreach ([['SQL','Database Design','related'],['Database Design','SQL','prerequisite'],['Requirement Analysis','BPMN','related'],['Programming & OOP','Web Development','prerequisite'],['Networking','Cloud Computing','prerequisite']] as [$from,$to,$type]) {
            DB::table('competency_relations')->insert(['source_competency_id'=>$competencyIds[$from],'target_competency_id'=>$competencyIds[$to],'relation_type'=>$type]);
        }

        $clusters = [
            ['Data & Analytics','Mengubah data menjadi keputusan yang dapat ditindaklanjuti.'],
            ['Business & Information Systems','Menjembatani kebutuhan organisasi dan solusi digital.'],
            ['Software & Product Development','Mendesain, membangun, dan menjaga produk digital.'],
            ['Infrastructure & Cloud','Menjaga fondasi infrastruktur modern tetap andal.'],
            ['Cybersecurity & Governance','Melindungi aset digital serta memastikan tata kelola.'],
            ['IT Management','Mengelola delivery, layanan, dan kolaborasi teknologi.'],
            ['Architecture','Merancang solusi teknologi lintas sistem secara menyeluruh.'],
            ['Emerging Technology & Consulting','Menerapkan teknologi baru untuk memecahkan masalah bisnis.'],
        ];
        $clusterIds=$careerPathIds=[];
        foreach ($clusters as $order=>[$name,$description]) {
            $clusterIds[$name]=DB::table('career_clusters')->insertGetId(['name'=>$name,'slug'=>Str::slug($name),'description'=>$description,'display_order'=>$order+1,'created_at'=>$now,'updated_at'=>$now]);
            $careerPathIds[$name]=DB::table('career_paths')->insertGetId(['name'=>$name,'description'=>$description,'created_at'=>$now,'updated_at'=>$now]);
        }

        $roles = [
            ['Data & Analytics','Data Analyst','standard','Menerjemahkan data menjadi insight untuk keputusan bisnis.',['SQL','Power BI','Excel','Statistics','Python','Business Understanding']],
            ['Data & Analytics','Business Intelligence Analyst','standard','Membangun model dan dashboard untuk memantau performa bisnis.',['Power BI','SQL','Data Visualization','Data Modeling','Business Understanding']],
            ['Data & Analytics','Data Engineer','standard','Membangun pipeline dan fondasi data yang andal.',['Data Engineering','SQL','Python','Data Modeling','Cloud Computing']],
            ['Data & Analytics','Data Scientist','advanced','Mengembangkan model prediktif untuk masalah bisnis kompleks.',['Statistics','Python','Machine Learning','SQL','Data Visualization']],
            ['Data & Analytics','Database Administrator','standard','Menjaga performa, keamanan, dan ketersediaan basis data.',['SQL','Database Design','Linux','Cloud Computing','Cybersecurity']],
            ['Business & Information Systems','Business Analyst','standard','Menggali kebutuhan dan merancang perbaikan proses bisnis.',['Requirement Analysis','Stakeholder Management','Business Process Analysis','SQL','Communication','BPMN']],
            ['Business & Information Systems','System Analyst','standard','Menerjemahkan kebutuhan menjadi rancangan sistem yang implementatif.',['Requirement Analysis','Software Architecture','Database Design','Business Process Analysis','Communication']],
            ['Business & Information Systems','Product Manager / Product Owner','standard','Menghubungkan kebutuhan pengguna, bisnis, dan tim produk.',['Product Management','Stakeholder Management','Business Understanding','Agile','Communication']],
            ['Business & Information Systems','ERP / CRM Consultant','standard','Menyelaraskan proses organisasi dengan solusi enterprise.',['ERP/CRM','Business Process Analysis','Requirement Analysis','Stakeholder Management','SQL']],
            ['Business & Information Systems','IT / Digital Consultant','standard','Membantu organisasi menyusun transformasi dan solusi teknologi.',['Business Understanding','Requirement Analysis','Presentation','IT Governance','Communication']],
            ['Software & Product Development','Software / Web Developer','standard','Membangun aplikasi web yang aman dan mudah dipelihara.',['Programming & OOP','Web Development','API Development','Database Design','Git & Version Control']],
            ['Software & Product Development','QA Engineer / Software Tester','standard','Menjaga kualitas produk melalui strategi dan otomasi pengujian.',['Software Testing','Problem Solving','API Development','Documentation','Collaboration']],
            ['Software & Product Development','UI/UX Designer','standard','Merancang pengalaman digital yang berguna, inklusif, dan intuitif.',['UI Design','UX Research','Communication','Presentation','Collaboration']],
            ['Infrastructure & Cloud','System / Network Administrator','standard','Mengoperasikan jaringan, server, dan layanan TI organisasi.',['Networking','Linux','Cybersecurity','Cloud Computing','Problem Solving']],
            ['Infrastructure & Cloud','Cloud / DevOps Engineer','standard','Mengotomasi delivery dan operasi infrastruktur cloud.',['Cloud Computing','Linux','Networking','Git & Version Control','Cybersecurity']],
            ['Cybersecurity & Governance','Cybersecurity Analyst','standard','Mendeteksi risiko dan merespons ancaman keamanan informasi.',['Cybersecurity','Networking','Linux','Problem Solving','Documentation']],
            ['Cybersecurity & Governance','IT Auditor / GRC Analyst','standard','Menilai kontrol, risiko, dan kepatuhan teknologi informasi.',['IT Governance','Cybersecurity','Business Process Analysis','Documentation','Communication']],
            ['IT Management','IT Project Manager / Scrum Master','advanced','Memimpin delivery teknologi dan menghilangkan hambatan tim.',['Project Management','Agile','Stakeholder Management','Communication','Collaboration']],
            ['IT Management','IT Service Management / IT Support','standard','Mengelola layanan dan pemulihan gangguan pengguna secara konsisten.',['IT Service Management','Problem Solving','Communication','Documentation','Networking']],
            ['Architecture','Solution Architect','advanced','Merancang solusi end-to-end yang aman, terukur, dan selaras bisnis.',['Solution Architecture','Software Architecture','Cloud Computing','API Development','Cybersecurity']],
            ['Emerging Technology & Consulting','AI / Automation Analyst','standard','Menemukan dan menerapkan peluang otomasi berbasis AI.',['AI & Automation','Business Process Analysis','Python','API Development','IT Governance']],
            ['Emerging Technology & Consulting','Pre-Sales / Solution Consultant','standard','Menerjemahkan kebutuhan calon klien menjadi solusi yang meyakinkan.',['Presentation','Communication','Business Understanding','Solution Architecture','Stakeholder Management']],
        ];
        $roleIds=[];
        $roleRequirementRows = $this->roleRequirementsFromOntology($competencyIds);
        foreach ($roles as [$cluster,$name,$level,$description,$requirements]) {
            $roleIds[$name]=DB::table('career_roles')->insertGetId([
                'career_path_id'=>$careerPathIds[$cluster],'career_cluster_id'=>$clusterIds[$cluster],'slug'=>Str::slug($name),'name'=>$name,'career_level'=>$level,
                'description'=>$description,'icon'=>'briefcase','is_active'=>true,'responsibilities'=>json_encode(["Menganalisis konteks {$name}",'Menghasilkan artefak kerja yang dapat diverifikasi','Berkolaborasi dengan stakeholder lintas fungsi']),
                'tools'=>json_encode(array_slice($requirements,0,4)),'created_at'=>$now,'updated_at'=>$now,
            ]);
            foreach ($roleRequirementRows[$this->ontologyKey($name)] ?? [] as $row) DB::table('career_role_competencies')->insert($row + ['job_role_id'=>$roleIds[$name]]);
        }

        [$courseIds,$moduleIds]=$this->seedCourses($competencyIds,$now);
        foreach ([['C09','C06',true],['C09','C05',true],['C08','C03',true],['C08','C06',true],['C19','C18',true],['C24','C11',true],['C24','C15',true],['C07','C04',false]] as [$course,$requires,$required]) {
            DB::table('course_prerequisites')->insert(['course_id'=>$courseIds[$course],'prerequisite_course_id'=>$courseIds[$requires],'is_required'=>$required]);
        }

        DB::table('students')->where('id',$studentId)->update(['target_job_role_id'=>$roleIds['Data Analyst']]);
        DB::table('student_career_interests')->insert([
            ['student_id'=>$studentId,'career_role_id'=>$roleIds['Data Analyst'],'interest_type'=>'target_active','created_at'=>$now,'updated_at'=>$now],
            ['student_id'=>$studentId,'career_role_id'=>$roleIds['Business Analyst'],'interest_type'=>'explored','created_at'=>$now,'updated_at'=>$now],
        ]);
        foreach ([['C03',68],['C04',100],['C06',32],['C07',18]] as [$code,$progress]) {
            DB::table('enrollments')->insert(['student_id'=>$studentId,'course_id'=>$courseIds[$code],'status'=>$progress===100?'completed':'active','progress'=>$progress,'last_activity_at'=>$now->copy()->subDay(),'completed_at'=>$progress===100?$now->copy()->subWeek():null,'created_at'=>$now,'updated_at'=>$now]);
            $take=max(1,(int)floor(count($moduleIds[$code])*$progress/100));
            foreach (array_slice($moduleIds[$code],0,$take) as $moduleId) DB::table('student_module_progress')->insert(['student_id'=>$studentId,'module_id'=>$moduleId,'status'=>'completed','started_at'=>$now->copy()->subWeeks(2),'completed_at'=>$now->copy()->subDays(2),'created_at'=>$now,'updated_at'=>$now]);
        }

        $scores=['SQL'=>[82,88,5],'Database Design'=>[74,76,4],'Excel'=>[86,90,5],'Data Visualization'=>[48,52,3],'Power BI'=>[54,62,3],'Statistics'=>[62,68,4],'Python'=>[35,44,2],'Requirement Analysis'=>[71,72,4],'BPMN'=>[58,64,3],'Business Process Analysis'=>[66,70,4],'Communication'=>[78,70,4],'Problem Solving'=>[72,73,4]];
        foreach ($scores as $skill=>[$score,$confidence,$level]) {
            $scId=DB::table('student_competencies')->insertGetId(['student_id'=>$studentId,'competency_id'=>$competencyIds[$skill],'score'=>$score,'confidence_score'=>$confidence,'proficiency_level'=>$level,'calculated_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
            DB::table('competency_evidences')->insert(['student_competency_id'=>$scId,'evidence_type'=>'technical_assessment','source_type'=>'assessment','source_id'=>1,'score'=>$score,'weight'=>30,'notes'=>'Evidence hasil assessment terverifikasi.','earned_at'=>$now->copy()->subDays(20),'created_at'=>$now,'updated_at'=>$now]);
            DB::table('competency_score_histories')->insert([['student_competency_id'=>$scId,'score'=>max(10,$score-14),'confidence_score'=>max(20,$confidence-20),'recorded_at'=>$now->copy()->subMonths(3)],['student_competency_id'=>$scId,'score'=>$score,'confidence_score'=>$confidence,'recorded_at'=>$now]]);
        }
        $ruleId=DB::table('recommendation_rule_versions')->insertGetId(['version'=>'2.0.0','configuration'=>json_encode(['highly_recommended'=>80,'recommended'=>65,'potential_match'=>50,'mandatory_gating'=>true]),'is_active'=>true,'published_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
        $pathId=DB::table('career_learning_paths')->insertGetId(['student_id'=>$studentId,'target_job_role_id'=>$roleIds['Data Analyst'],'status'=>'active','generated_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
        foreach ([['Excel','C04','ready',true,'already_competent'],['SQL','C03','ready',true,'already_competent'],['Statistics','C05','skill_gap',false,'pending'],['Python','C06','skill_gap',false,'in_progress'],['Power BI','C07','skill_gap',true,'pending']] as $position=>[$skill,$course,$severity,$mandatory,$status]) DB::table('career_learning_path_items')->insert(['learning_path_id'=>$pathId,'course_id'=>$courseIds[$course],'competency_id'=>$competencyIds[$skill],'severity'=>$severity,'is_mandatory'=>$mandatory,'position'=>$position+1,'is_completed'=>$status==='already_competent','status'=>$status]);

        $projectId=DB::table('projects')->insertGetId(['title'=>'E-Commerce Sales Analytics Dashboard','description'=>'Bangun dashboard penjualan end-to-end dan jelaskan tiga insight yang dapat ditindaklanjuti.','related_course_id'=>$courseIds['C07'],'is_published'=>true,'created_at'=>$now,'updated_at'=>$now]);
        foreach (['SQL'=>3,'Data Visualization'=>3,'Power BI'=>3,'Business Understanding'=>2] as $skill=>$requiredLevel) DB::table('project_competencies')->insert(['project_id'=>$projectId,'competency_id'=>$competencyIds[$skill],'required_level'=>$requiredLevel]);
        DB::table('project_submissions')->insert(['project_id'=>$projectId,'student_id'=>$studentId,'submission_ref'=>'https://example.com/portfolio/rani-sales-dashboard','student_note'=>'Dashboard, data model, dan catatan insight sudah disertakan.','status'=>'submitted','created_at'=>$now,'updated_at'=>$now]);

        $profileId=DB::table('career_profiles')->insertGetId(['student_id'=>$studentId,'primary_interest'=>'Data & Analytics','work_style'=>'analytical','career_goal'=>'Menjadi analis yang mampu mengubah data menjadi keputusan bisnis yang berdampak.','completed_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
        foreach (['SQL'=>90,'Database Design'=>75,'Data Visualization'=>60,'Statistics'=>75,'Python'=>45,'Requirement Analysis'=>70,'BPMN'=>55,'Communication'=>70] as $skill=>$rating) DB::table('career_profile_strengths')->insert(['career_profile_id'=>$profileId,'competency_id'=>$competencyIds[$skill],'self_rating'=>$rating,'created_at'=>$now,'updated_at'=>$now]);
        DB::table('audit_logs')->insert(['action'=>'published','auditable_type'=>'recommendation_rule_version','auditable_id'=>$ruleId,'new_values'=>json_encode(['version'=>'2.0.0','source'=>'PRD v1 + v2']),'created_at'=>$now]);

        $this->call(AssessmentSeeder::class);
    }

    private function seedCourses(array $competencyIds,$now): array
    {
        $courseIds=$moduleIds=[];
        foreach ($this->courseCatalog() as $code=>[$title,$skills,$modules]) {
            $category=$this->categoryFor($skills[0]);
            $courseIds[$code]=DB::table('courses')->insertGetId(['slug'=>Str::slug($title),'code'=>$code,'title'=>$title,'description'=>"Kuasai {$title} melalui materi terstruktur, latihan, dan bukti kompetensi yang relevan untuk karier.",'category'=>$category,'status'=>'published','duration_minutes'=>count($modules)*35,'level'=>in_array($code,['C09','C19','C24'])?'Lanjutan':'Fondasiâ€“Menengah','learning_outcomes'=>json_encode($skills),'created_at'=>$now,'updated_at'=>$now]);
            foreach ($skills as $skill) DB::table('course_competencies')->insert(['course_id'=>$courseIds[$code],'competency_id'=>$competencyIds[$skill],'contribution'=>(int)floor(100/count($skills)),'competency_gain'=>2]);
            $sectionId=DB::table('course_sections')->insertGetId(['course_id'=>$courseIds[$code],'title'=>'Learning modules','position'=>1,'created_at'=>$now,'updated_at'=>$now]);
            foreach ($modules as $order=>$title) {
                $isProject=str_contains(strtolower($title),'project')||str_contains(strtolower($title),'case study')||str_contains(strtolower($title),'simulation');
                $moduleId=DB::table('modules')->insertGetId(['course_id'=>$courseIds[$code],'title'=>$title,'order'=>$order+1,'type'=>$isProject?'project':'text','content_ref'=>"Materi {$title}",'duration_minutes'=>$isProject?60:30,'is_optional'=>false,'created_at'=>$now,'updated_at'=>$now]);
                $moduleIds[$code][]=$moduleId;
                if ($order>0) DB::table('module_prerequisites')->insert(['module_id'=>$moduleId,'prerequisite_module_id'=>$moduleIds[$code][$order-1]]);
                if ($isProject) foreach ($skills as $skill) DB::table('module_competencies')->insert(['module_id'=>$moduleId,'competency_id'=>$competencyIds[$skill],'competency_gain'=>1]);
                DB::table('lessons')->insert(['course_section_id'=>$sectionId,'title'=>$title,'type'=>$isProject?'project':'text','content'=>"Materi {$title}",'duration_minutes'=>$isProject?60:30,'position'=>$order+1,'created_at'=>$now,'updated_at'=>$now]);
            }
        }
        return [$courseIds,$moduleIds];
    }

    private function categoryFor(string $name): string
    {
        return match($name) {
            'SQL','Database Design','Data Modeling','Data Engineering'=>'Data & Database',
            'Excel','Statistics','Python','Data Visualization','Power BI','Machine Learning'=>'Data & Analytics',
            'Programming & OOP','Web Development','API Development','Git & Version Control','Software Testing','Software Architecture','Solution Architecture'=>'Software & Architecture',
            'Networking','Linux','Cloud Computing','Cybersecurity'=>'Infrastructure & Security',
            'UI Design','UX Research'=>'Design','AI & Automation'=>'Emerging Technology',
            'Communication','Presentation','Documentation','Collaboration','Problem Solving'=>'Professional Skills',
            default=>'Business & Management',
        };
    }

    private function roleRequirementsFromOntology(array $competencyIds): array
    {
        $path = config('ispath.ontology.path');
        if (!is_string($path) || basename($path) !== 'LMS FIX.rdf' || !is_readable($path)) {
            throw new RuntimeException('Ontology wajib pakai file Ontologi/LMS FIX.rdf');
        }

        $competencyKeyToId = [];
        foreach ($competencyIds as $name => $id) $competencyKeyToId[$this->ontologyKey($name)] = $id;

        $document = new DOMDocument();
        if (!$document->load($path, LIBXML_NONET | LIBXML_NOBLANKS)) throw new RuntimeException('LMS FIX.rdf tidak valid.');

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $xpath->registerNamespace('owl', 'http://www.w3.org/2002/07/owl#');
        $xpath->registerNamespace('lms', config('ispath.ontology.namespace'));

        $rows = [];
        foreach ($xpath->query('//owl:NamedIndividual[rdf:type[contains(@rdf:resource,"#CareerRequirement")]]') ?: [] as $node) {
            $role = $this->ontologyKey($this->localName($xpath->evaluate('string(lms:requirementOfCareer/@rdf:resource)', $node)));
            $competency = $this->ontologyKey($this->localName($xpath->evaluate('string(lms:requiresCompetency/@rdf:resource)', $node)));
            if (!isset($competencyKeyToId[$competency])) continue;

            $type = trim($xpath->evaluate('string(lms:requirementType)', $node)) ?: 'recommended';
            $rows[$role][] = [
                'competency_id' => $competencyKeyToId[$competency],
                'weight' => (float) $xpath->evaluate('string(lms:requirementWeight)', $node),
                'minimum_level' => (int) $xpath->evaluate('string(lms:minimumLevel)', $node),
                'requirement_type' => $type,
                'is_required' => $type === 'mandatory',
            ];
        }

        return $rows;
    }

    private function localName(string $iri): string
    {
        return str_contains($iri, '#') ? substr(strrchr($iri, '#'), 1) : basename($iri);
    }

    private function ontologyKey(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $value));
    }

    private function roleRequirementConfig(string $name,int $count,string $careerLevel): array
    {
        return match($name) {
            'Data Analyst'=>[[25,25,15,15,15,5],[4,4,4,3,3,2],2],
            'Business Analyst'=>[[25,20,20,15,15,5],[4,4,4,2,4,2],3],
            'System Analyst'=>[[20,25,20,15,10],[4,4,3,3,3],3],
            'Software / Web Developer'=>[[30,25,15,15,15],[4,4,3,3,3],2],
            'Data Scientist'=>[[25,25,25,15,10],[4,4,4,3,3],3],
            default=>[$count===6?[25,20,20,15,15,5]:[25,25,20,15,15],array_map(fn($i)=>$careerLevel==='advanced'?($i<3?4:3):($i<2?4:($i<4?3:2)),range(0,$count-1)),3],
        };
    }

    private function courseCatalog(): array
    {
        $course = fn (string $title, array $skills, string $modules) => [$title, $skills, explode('|', $modules)];
        return [
            'C01'=>$course('Information Systems Fundamentals',['Business Understanding'],'Introduction to Information Systems|People, Process, Data & Technology|Information Systems in Organizations|Business Models & Value Chains|Enterprise Information Systems|Digital Transformation & IS Ethics'),
            'C02'=>$course('Business Process & BPMN',['Business Process Analysis','BPMN'],'Introduction to Business Process|Identifying Business Processes|AS-IS Process Analysis|BPMN Events & Activities|Gateway, Pool & Lane|TO-BE Process Design|Process KPI & Bottleneck Analysis|Business Process Improvement Project'),
            'C03'=>$course('SQL & Relational Database',['SQL','Database Design'],'Introduction to Database & RDBMS|SELECT, WHERE, ORDER BY|Aggregate Functions & GROUP BY|SQL JOIN|Subquery & Common Table Expression|INSERT, UPDATE & DELETE|Database Design & Normalization|Window Functions|Indexing & Query Optimization|SQL Data Analysis Case Study'),
            'C04'=>$course('Excel for Data Analytics',['Excel'],'Excel Fundamentals|Data Cleaning|Logical & Statistical Functions|XLOOKUP / INDEX MATCH|Pivot Table|Data Visualization|Power Query|Interactive Excel Dashboard Project'),
            'C05'=>$course('Statistics for Analytics',['Statistics'],'Descriptive Statistics|Data Distribution|Probability Fundamentals|Sampling|Confidence Interval|Hypothesis Testing|Correlation|Linear Regression'),
            'C06'=>$course('Python for Data Analytics',['Python','Data Visualization'],'Python Environment & Syntax|Variables & Data Types|Lists, Dictionaries & Tuples|Conditions, Loops & Functions|NumPy Fundamentals|Pandas Fundamentals|Data Cleaning|Exploratory Data Analysis|Data Visualization|Data Analytics Project'),
            'C07'=>$course('Power BI',['Power BI','Data Visualization'],'Introduction to Business Intelligence|Power BI Ecosystem|Connecting Data Sources|Power Query|Data Transformation|Data Modeling & Relationships|DAX Fundamentals|Data Visualization|Dashboard Design|End-to-End BI Dashboard Project'),
            'C08'=>$course('Data Engineering Fundamentals',['Data Modeling','Data Engineering'],'Introduction to Data Engineering|Advanced SQL|Data Modeling|ETL vs ELT|Python Data Pipeline|Working with APIs|Data Warehouse|Data Lake|Data Pipeline Orchestration|Data Engineering Project'),
            'C09'=>$course('Machine Learning',['Machine Learning'],'Introduction to Machine Learning|Machine Learning Workflow|Data Preprocessing|Linear Regression|Classification|Decision Tree & Ensemble|Clustering|Model Evaluation|Feature Engineering & Explainability|Machine Learning Project'),
            'C10'=>$course('Business Analysis',['Requirement Analysis','Stakeholder Management'],'Introduction to Business Analysis|Stakeholder Analysis|Requirement Elicitation|Functional & Non-Functional Requirements|User Stories|Use Cases|Acceptance Criteria|Gap Analysis & Root Cause Analysis|Requirement Prioritization|Business Analysis Case Study'),
            'C11'=>$course('Systems Analysis & Design',['Requirement Analysis','Software Architecture'],'System Development Life Cycle|Feasibility Study|Requirement Analysis|Use Case Diagram|Activity Diagram|Sequence Diagram|Class Diagram|Entity Relationship Diagram|System Architecture Fundamentals|System Design Project'),
            'C12'=>$course('Product Management',['Product Management'],'Product Management Fundamentals|Product Discovery|Customer Problem Identification|Value Proposition|Product Requirement Document|Product Roadmap|Prioritization Framework|Product Metrics & Experimentation'),
            'C13'=>$course('Project Management & Agile',['Project Management','Agile'],'Project Management Fundamentals|Project Scope & WBS|Scheduling|Resource & Cost Management|Risk Management|Agile Fundamentals|Scrum & Kanban|Agile Project Simulation'),
            'C14'=>$course('Programming & Object-Oriented Programming',['Programming & OOP','Git & Version Control'],'Programming Logic|Variables & Data Types|Conditional Statements|Loops|Functions|Data Structures|Object-Oriented Programming|Error Handling|Git & Version Control|Programming Project'),
            'C15'=>$course('Web Development & REST API',['Web Development','API Development'],'How The Web Works|HTML|CSS|JavaScript Fundamentals|HTTP & Client-Server Architecture|Backend & MVC|REST API|Authentication & Authorization|Database Integration|Fullstack Application Project'),
            'C16'=>$course('Software Quality Assurance',['Software Testing'],'Software Quality Fundamentals|Software Testing Lifecycle|Test Scenario & Test Case|Bug Reporting|API Testing with Postman|Automation Testing Fundamentals|Performance & Security Testing Introduction|QA Testing Project'),
            'C17'=>$course('UI/UX Design',['UI Design','UX Research'],'Human Centered Design|UX Research|User Persona|Customer Journey|Information Architecture|Wireframing|UI Design with Figma|Prototype|Usability Testing & Accessibility|UI/UX Portfolio Case Study'),
            'C18'=>$course('IT Infrastructure & Networking',['Networking','Linux'],'Computer Hardware & Operating Systems|Network Fundamentals|TCP/IP|IP Addressing & Subnetting|Switching & Routing|DNS & DHCP|Linux Fundamentals|Virtualization|Monitoring & Troubleshooting|Infrastructure Lab Project'),
            'C19'=>$course('Cloud & DevOps',['Cloud Computing'],'Cloud Computing Fundamentals|Cloud Networking|Identity & Access Management|Compute & Storage|Docker|CI/CD|Infrastructure as Code|Monitoring & Logging|Cloud Security|Cloud Deployment Project'),
            'C20'=>$course('Cybersecurity',['Cybersecurity'],'Cybersecurity Fundamentals|CIA Triad & Security Risk|Network Security|Identity & Access Management|Endpoint Security|Web Application Security|Vulnerability Management|Logging & SIEM|Incident Response|Cybersecurity Investigation Project'),
            'C21'=>$course('IT Governance, Risk & Audit',['IT Governance'],'IT Governance Fundamentals|IT Risk Management|IT Controls|COBIT Fundamentals|ISO 27001 Fundamentals|IT Audit Lifecycle|Compliance & Data Privacy|IT Audit Case Study'),
            'C22'=>$course('ERP & CRM',['ERP/CRM','Business Process Analysis'],'ERP & CRM Fundamentals|Enterprise Business Processes|Master Data|Procure-to-Pay|Order-to-Cash|Finance, Inventory & Operations|ERP Integration & Data Migration|ERP Implementation Case Study'),
            'C23'=>$course('IT Service Management',['IT Service Management'],'IT Service Management Fundamentals|Service Desk|Incident Management|Service Request Management|Problem Management|Change Management|SLA & Service Performance|IT Service Management Simulation'),
            'C24'=>$course('Solution & Enterprise Architecture',['Software Architecture','Solution Architecture'],'Introduction to Solution Architecture|Application Architecture|Data Architecture|Technology Architecture|Integration Architecture|API & Event-Driven Architecture|Architecture Quality Attributes|Solution Architecture Case Study'),
            'C25'=>$course('Professional Skills & Career Development',['Communication','Presentation','Documentation','Collaboration'],'Professional Communication|Technical Documentation|Presentation Skills|Stakeholder Communication|Team Collaboration|CV & LinkedIn|Portfolio Development|Technical & Case Interview'),
            'C26'=>$course('AI & Business Automation',['AI & Automation'],'AI Fundamentals|Generative AI & LLM Fundamentals|Prompt Engineering|AI API Fundamentals|Business Process Automation|RPA / Workflow Automation|AI Governance & Responsible AI|AI Business Automation Project'),
        ];
    }
}

