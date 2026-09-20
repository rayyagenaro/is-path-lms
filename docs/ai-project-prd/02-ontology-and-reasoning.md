# IS-Path Ontology and Reasoning Specification

## 1. Ontology Source

Primary RDF file:

- `Ontologi/LMS FIX.rdf`

Current ontology summary:

- Source hash: `49b193150d0a82a166a569d921fdde63f1e727e75b18e6cd4c18be8fd65c7a3e`
- Modified at: `2026-09-06T14:26:15+07:00`
- Namespace: `http://www.semanticweb.org/hp/ontologies/2026/8/untitled-ontology-79#`
- Classes: 26
- Object properties: 88
- Data properties: 74
- Named individuals: 786
- Enabled rules: 24

Configuration:

```dotenv
ISPATH_ONTOLOGY_PATH="Ontologi/LMS FIX.rdf"
ISPATH_ONTOLOGY_NAMESPACE="http://www.semanticweb.org/hp/ontologies/2026/8/untitled-ontology-79#"
ISPATH_ONTOLOGY_CACHE_SECONDS=3600
```

## 2. Responsibility Split

PostgreSQL owns operational state:

- student profile,
- assessment attempts and answers,
- competency evidence,
- calculated competency scores,
- course/module progress,
- career recommendations,
- learning paths,
- audit and learning events.

RDF/OWL owns domain knowledge:

- career ontology vocabulary,
- competency and role semantics,
- class/property definitions,
- named individuals,
- SWRL rule identifiers and stable domain rule intent.

The app must not migrate the entire RDF graph into database tables. The RDF file is read-only knowledge input.

## 3. Application Bridge

`SwrlReasoningService` is an application bridge for selected SWRL semantics. It is intentionally not a generic SWRL execution engine.

The bridge:

- confirms RDF availability through `OntologyRepository`,
- checks whether named rules exist and are enabled,
- evaluates runtime conditions from PostgreSQL,
- emits `fired_rules` and `inferred_facts`,
- stores the trace in recommendation explanation snapshots.

If RDF is missing or invalid:

- recommendation based on database still runs,
- ontology trace status becomes `unavailable`,
- student flow should not crash.

## 4. Rules Used in Runtime

Runtime bridge currently uses:

| Rule | Runtime meaning |
|---|---|
| R07 | Student completed post-assessment |
| R08 | Post-assessment evaluated competency |
| R13 | Career requirement gap exists |
| R14 | Mandatory career gap exists |
| R15 | Recommended career gap exists |
| R16 | Student becomes career recommendation candidate after post-assessment |
| R17 | Competency development is needed |
| R21 | Candidate course exists for a gap |
| R22 | High recommendation category |
| R23 | Medium recommendation category |
| R24 | Low or exploratory recommendation category |

Other RDF rules exist and may be mapped later if the app needs the behavior.

## 5. Recommendation Data Mapping

The reasoning context receives:

- `studentId`,
- ranked career role object,
- role match score,
- role readiness,
- strong skills,
- gaps,
- mandatory blocking gaps.

The output is stored under:

- `career_recommendations.explanation_snapshot.ontology`

Expected trace shape:

```json
{
  "engine": "SWRL application bridge",
  "status": "ready",
  "ontology_hash": "...",
  "ontology_modified_at": "...",
  "post_assessment_completed": true,
  "fired_rules": ["R07", "R08", "R16", "R13", "R21", "R23"],
  "inferred_facts": ["completedPostAssessment", "careerCandidateAfterPostAssessment"]
}
```

## 6. Competency and Career Model

Competencies are grouped into:

- Technical
- Business
- Professional

Career roles are grouped into 8 clusters:

- Data & Analytics
- Business & Information Systems
- Software & Product Development
- Infrastructure & Cloud
- Cybersecurity & Governance
- IT Management
- Architecture
- Emerging Technology & Consulting

Role requirements include:

- competency id,
- weight,
- minimum level,
- requirement type,
- required flag.

## 7. Reasoning Boundaries

Do not claim that the system performs complete OWL classification or arbitrary SWRL reasoning. The implemented system performs deterministic application reasoning informed by RDF/SWRL rule definitions.

Future full reasoning options:

- add a Java reasoner service using Apache Jena, OWL API, or Pellet-compatible tooling,
- precompute ontology inferences and expose them through an internal API,
- keep Laravel as orchestration layer and PostgreSQL as operational source.

Only add this if the academic/project requirement explicitly needs full semantic reasoner behavior beyond the current bridge.

