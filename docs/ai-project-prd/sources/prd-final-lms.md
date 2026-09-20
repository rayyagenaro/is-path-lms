# IS-PATH — FINAL PRODUCT REQUIREMENTS DOCUMENT
## Existing Laravel Application Enhancement + Ontology/SWRL Integration

You are working on an EXISTING application called IS-Path.

IS-Path is a Learning Management System and career recommendation platform for Information Systems students.

The application already has an existing codebase, frontend style, layout system, components, routes, database structure, and partially implemented features.

Your job is NOT to rebuild the project.

Your job is to:

1. inspect the existing project carefully,
2. understand how it currently works,
3. understand the provided ontology,
4. map the ontology to the existing Laravel application,
5. fix incomplete or inconsistent features,
6. add only the features that are missing,
7. integrate ontology/SWRL reasoning into the application,
8. improve UX and frontend implementation WITHOUT changing the existing visual identity,
9. preserve all working functionality.

==================================================
0. NON-NEGOTIABLE RULES
==================================================

THIS IS AN EXISTING PROJECT.

DO NOT:

- rebuild the project from scratch,
- create a new Laravel project,
- replace the existing frontend,
- replace the existing design system,
- redesign the application,
- change the application's visual identity,
- change typography globally,
- change the color palette globally,
- change the sidebar style,
- change navbar/header style,
- replace existing cards with a totally different design,
- introduce a new UI framework unless already used,
- migrate the frontend to React/Vue/etc. unless already used,
- rename existing routes unnecessarily,
- rename existing database columns unnecessarily,
- rename existing models unnecessarily,
- delete working features,
- remove existing components,
- duplicate existing services,
- duplicate existing database structures,
- hardcode ontology knowledge inside controllers,
- hardcode recommendation rules inside Blade templates,
- expose technical ontology terminology unnecessarily to end users.

DO:

- preserve current UI style,
- preserve current spacing language,
- preserve current border radius,
- preserve current typography,
- preserve current color system,
- preserve current component patterns,
- reuse existing components,
- reuse existing layouts,
- reuse existing services/models when possible,
- extend instead of replacing,
- refactor only when necessary,
- keep changes incremental,
- maintain backward compatibility,
- make the application feel like the SAME project, just more complete.

The final product must look like the current IS-Path application has naturally evolved.

NOT like another developer replaced the entire frontend.

==================================================
1. AVAILABLE SKILLS
==================================================

The environment may provide the following installed skills:

- UI-UX Pro Max
- Frontend God Mode
- ASCII Animation

Use these skills when appropriate according to their own instructions.

IMPORTANT:

These skills are SUPPORTING TOOLS.

They must NOT override the existing project's visual design.

The existing project is the PRIMARY design reference.

--------------------------------------------------
UI-UX PRO MAX
--------------------------------------------------

Use UI-UX Pro Max to improve:

- usability,
- information hierarchy,
- navigation clarity,
- form flow,
- assessment experience,
- dashboard readability,
- learning progress visibility,
- career recommendation explanation,
- competency visualization,
- empty states,
- loading states,
- feedback states,
- accessibility,
- responsive behavior.

However:

DO NOT use the skill to redesign the product.

Before changing UI:

inspect the existing:

- buttons,
- cards,
- form controls,
- headings,
- sidebar,
- navigation,
- modals,
- tables,
- badges,
- progress indicators,
- spacing,
- colors,
- typography.

Then create new UI that visually matches those existing patterns.

Think:

"Improve the current IS-Path UX."

NOT:

"Create a better looking application from scratch."

--------------------------------------------------
FRONTEND GOD MODE
--------------------------------------------------

Use Frontend God Mode for high-quality implementation of existing and new UI.

Focus on:

- clean component structure,
- maintainable frontend code,
- reusable components,
- responsive layouts,
- state handling,
- loading states,
- empty states,
- validation states,
- progress indicators,
- visual hierarchy,
- semantic HTML,
- accessibility,
- frontend performance.

Again:

DO NOT use this skill to change the design direction.

When adding new UI, derive its visual language from the existing application.

For example:

If existing cards use:

- rounded-lg,
- specific border style,
- specific shadow,
- specific padding,
- existing badge format,

then new Career Recommendation cards MUST follow the same pattern.

Do not introduce random gradients, glassmorphism, neon themes, oversized typography, or trendy landing-page aesthetics unless those patterns already exist in the project.

--------------------------------------------------
ASCII ANIMATION
--------------------------------------------------

ASCII Animation is optional and should only be used if it fits the existing experience.

Potential acceptable use:

- lightweight loading state during ontology reasoning,
- processing state during career recommendation generation,
- small terminal-inspired visual inside a developer/debug ontology page,
- optional subtle loading animation.

Example concept:

Analyzing competency profile...

[■■■■■■□□□□] reasoning

or a subtle ASCII process visualization.

Do NOT:

- place distracting ASCII animation across the dashboard,
- turn the application into a terminal aesthetic,
- replace normal loading indicators unnecessarily,
- change the visual identity.

If ASCII animation does not naturally fit the existing UI:

DO NOT USE IT.

==================================================
2. TECH STACK
==================================================

Primary stack:

- Laravel
- PostgreSQL
- OWL Ontology
- SWRL
- Protégé-generated ontology file

Frontend:

Use whatever frontend technology is ALREADY used by the project.

Do not change the frontend stack unnecessarily.

==================================================
3. PRODUCT PURPOSE
==================================================

IS-Path helps Information Systems students:

- discover their interests,
- understand their preferred work style,
- learn relevant competencies,
- complete structured learning,
- measure competency development,
- receive career recommendations,
- understand why a career matches them,
- identify competency gaps,
- receive personalized learning paths.

The central product concept is:

PRE-ASSESSMENT
→ INTEREST PROFILE
→ EXPLORATION LEARNING
→ POST-ASSESSMENT
→ COMPETENCY PROFILE
→ ONTOLOGY/SWRL REASONING
→ CAREER RECOMMENDATION
→ COMPETENCY GAP
→ PERSONALIZED CAREER LEARNING PATH

This flow is extremely important.

Career Recommendation MUST NOT be shown before the required post-assessment has been completed.

==================================================
4. FIRST TASK: AUDIT THE EXISTING PROJECT
==================================================

BEFORE CODING:

Inspect the entire repository.

Understand:

- directory structure,
- Laravel version,
- authentication,
- routes,
- middleware,
- controllers,
- services,
- models,
- migrations,
- seeders,
- database relationships,
- Blade/components,
- frontend assets,
- dashboard,
- sidebar/navigation,
- course system,
- module system,
- assessment system,
- existing career pages,
- existing profile pages,
- existing onboarding,
- existing recommendation logic,
- admin functionality,
- test structure.

Then classify features as:

EXISTING AND WORKING
PARTIALLY IMPLEMENTED
MISSING
INCONSISTENT
DUPLICATED

Do not recreate functionality that already exists.

If an existing implementation is 70% correct:

finish and improve it.

Do not build another implementation beside it.

==================================================
5. ONTOLOGY AS DOMAIN KNOWLEDGE
==================================================

A provided OWL ontology file exists in the repository.

READ THE ONTOLOGY COMPLETELY.

Do not assume its structure.

Inspect:

- Classes
- Object Properties
- Data Properties
- Individuals
- Career Requirements
- Career Roles
- Competencies
- Career Clusters
- Work Styles
- Interest Areas
- Courses
- Modules
- Assessments
- Projects
- SWRL rules

Expected major concepts include:

Student
CareerProfile
InterestArea
WorkStyle

CareerCluster
CareerRole

Competency
TechnicalCompetency
BusinessCompetency
ProfessionalCompetency

StudentCompetency
CareerRequirement

Course
CourseCompetencyMapping
CourseEnrollment
Module

Assessment
AssessmentItem
AssessmentAttempt

Project
ProjectSubmission

CompetencyEvidence

CareerRecommendation
CompetencyGap

LearningPath
LearningPathItem

Ontology names must remain traceable.

Do not arbitrarily rename ontology identifiers.

==================================================
6. DATABASE VS ONTOLOGY RESPONSIBILITIES
==================================================

PostgreSQL is the operational application database.

OWL ontology is the semantic domain knowledge layer.

SWRL is the reasoning rule layer.

Laravel coordinates the system.

Do NOT attempt to replace PostgreSQL with the ontology.

--------------------------------------------------
POSTGRESQL STORES
--------------------------------------------------

Application state such as:

users
profiles
assessment answers
assessment attempts
course enrollment
module progress
project submissions
competency scores
career recommendation history
learning path state
application settings

--------------------------------------------------
ONTOLOGY STORES / REPRESENTS
--------------------------------------------------

Domain knowledge such as:

CareerRole
CareerCluster
Competency
CareerRequirement
InterestArea
WorkStyle
Course competency relationships
Career competency relationships
semantic relationships between entities

--------------------------------------------------
SWRL REASONS ABOUT
--------------------------------------------------

interest compatibility
work-style compatibility
profile compatibility
career requirement fulfillment
competency gaps
mandatory gaps
recommended gaps
candidate courses
semantic recommendation categories

--------------------------------------------------
LARAVEL CALCULATES
--------------------------------------------------

raw assessment score
weighted aggregation
final career match score
career ranking
top-N recommendations
database record creation
application workflow
learning progress
business state transitions

==================================================
7. ONTOLOGY INTEGRATION LAYER
==================================================

Create or adapt a clean ontology integration layer.

Prefer something equivalent to:

app/
Services/
Ontology/
OntologyService.php
OntologyRepository.php
ReasoningService.php
CompetencyService.php
RecommendationService.php
LearningPathService.php

BUT:

If equivalent services already exist, extend those instead.

Do not create unnecessary architecture.

Suggested responsibilities:

OntologyService
- ontology loading
- ontology availability
- entity access

OntologyRepository
- query ontology concepts
- map ontology identifiers

ReasoningService
- invoke/query reasoning results
- interpret SWRL-derived relationships

CompetencyService
- calculate/update student competency

RecommendationService
- calculate career match
- rank careers
- create recommendation results

LearningPathService
- generate career-specific learning path
- map gaps to courses

Keep controllers thin.

Controllers should orchestrate.

Business logic belongs in services.

==================================================
8. CONFIGURATION
==================================================

Create configuration only if appropriate:

config/ispath.php

Possible settings:

ontology_path
ontology_enabled
reasoning_enabled
post_assessment_required
recommendation_limit
recommendation_thresholds
scoring_weights

Do not hardcode environment-specific absolute paths.

Ontology may be stored under something like:

storage/app/ontology/

Use Laravel storage/config conventions.

==================================================
9. COMPLETE STUDENT FLOW
==================================================

The required flow is:

REGISTER
↓
PRE-ASSESSMENT
↓
CAREER PROFILE
↓
EXPLORATION LEARNING
↓
COURSE
↓
MODULE
↓
COURSE ASSESSMENT / PROJECT
↓
POST-ASSESSMENT
↓
STUDENT COMPETENCY
↓
ONTOLOGY/SWRL REASONING
↓
CAREER MATCH CALCULATION
↓
CAREER RECOMMENDATION
↓
EXPLANATION
↓
COMPETENCY GAP
↓
PERSONALIZED LEARNING PATH

The UI/navigation should guide the student through these stages naturally.

==================================================
10. FEATURE — REGISTRATION & ONBOARDING
==================================================

Preserve existing authentication.

After a first-time student registers:

determine whether pre-assessment has been completed.

If not:

guide them to Pre-Assessment.

Do not repeatedly force completed users through onboarding.

Avoid fragile redirect loops.

Existing users must remain usable after the new flow is introduced.

==================================================
11. FEATURE — PRE-ASSESSMENT
==================================================

Pre-assessment happens BEFORE primary learning.

Purpose:

discover:

- InterestArea
- WorkStyle

This is NOT the career recommendation assessment.

Questions should feel student-friendly.

Do not expose ontology labels mechanically.

Example user-facing language:

"What type of activity do you enjoy?"

rather than:

"Select InterestArea individual."

Possible Interest Areas derive from ontology.

Possible Work Styles derive from ontology.

After completion, create/update CareerProfile.

==================================================
12. FEATURE — INTEREST PROFILE
==================================================

After Pre-Assessment, show a clear result page.

Example:

YOUR INTEREST PROFILE

Primary Interest
Data & Analytics

Preferred Work Style
Analytical
Structured

Then show:

Recommended Learning Direction

Do NOT show:

"You are 92% Data Analyst."

Career recommendation comes later.

==================================================
13. FEATURE — EXPLORATION LEARNING
==================================================

Use the student's interest profile to identify relevant learning content.

Possible semantic flow:

Student
→ CareerProfile
→ InterestArea
→ related CareerRole
→ CareerRequirement
→ Competency
→ CourseCompetencyMapping
→ Course

Present the result as:

Recommended Learning Based on Your Interests

NOT:

Recommended Career.

==================================================
14. FEATURE — COURSE CATALOG
==================================================

Preserve existing Course functionality.

Course list should support existing filtering/search patterns if they exist.

Where appropriate show:

course title
description
level
estimated duration
competencies taught
progress

Do not overcrowd course cards.

Use existing card design.

==================================================
15. FEATURE — COURSE DETAIL
==================================================

Course page may contain:

overview
competencies
module list
progress
assessment
project

Reuse existing application sections and components.

Do not invent a completely different course detail layout if one already exists.

==================================================
16. FEATURE — MODULE LEARNING
==================================================

Support structured modules.

Expected states:

locked if required
available
in_progress
completed

Maintain module ordering.

Module progress belongs in the application database.

Do not attempt to write video progress into OWL.

==================================================
17. FEATURE — COURSE ENROLLMENT
==================================================

Equivalent ontology concept:

CourseEnrollment

Track:

student
course
status
progress percentage
enrolled timestamp
completed timestamp

Suggested statuses:

not_started
in_progress
completed

Reuse existing enrollment/progress models if available.

==================================================
18. FEATURE — ASSESSMENT
==================================================

Support:

Assessment
AssessmentItem
AssessmentAttempt

Assessment UI should match the current project.

Improve only:

question readability
answer state
navigation
progress
submission confirmation
result clarity

Laravel calculates raw assessment score.

Example:

attemptScore = 82

==================================================
19. FEATURE — PROJECT
==================================================

If supported by the existing project, projects may provide practical competency evidence.

Possible workflow:

Project
→ ProjectSubmission
→ CompetencyEvidence

Allow links/files according to existing project capabilities.

Do not overengineer file infrastructure if it is outside the current scope.

==================================================
20. FEATURE — COMPETENCY EVIDENCE
==================================================

Competency evidence may originate from:

assessment
project
course performance

Evidence can contain:

evidenceType
evidenceScore
evidenceWeight
earnedAt

Use evidence as one basis for maintaining StudentCompetency.

==================================================
21. FEATURE — POST-ASSESSMENT
==================================================

POST-ASSESSMENT IS A CRITICAL GATE.

Final Career Recommendation is locked until this stage is completed.

Post-assessment evaluates the student's competencies after learning.

It may evaluate:

Technical Competency
Business Competency
Professional Competency

Laravel flow:

save answers
↓
calculate assessment result
↓
calculate competency results
↓
update StudentCompetency
↓
create AssessmentAttempt
↓
create/update CompetencyEvidence
↓
mark post-assessment complete
↓
run semantic reasoning
↓
calculate career recommendations

==================================================
22. FEATURE — MY COMPETENCIES
==================================================

Create/fix a page such as:

My Competencies

Group by:

Technical
Business
Professional

For each competency show:

name
mastery
proficiency
evidence

Example:

SQL
Level 4 / 5
Mastery 82%

Evidence
Assessment: 84
Course: Completed
Project: 86

Use simple visualizations consistent with existing UI.

Do not introduce random radar charts just because they look impressive.

Only use a visualization if it improves comprehension.

==================================================
23. CAREER REQUIREMENTS
==================================================

CareerRole requirements must derive from ontology/domain data.

CareerRequirement may contain:

Competency
minimumLevel
requirementWeight
requirementType

Possible requirement types:

mandatory
recommended

Do not hardcode requirements inside:

controllers
Blade views
JavaScript

==================================================
24. SWRL REASONING
==================================================

Use the rules stored in the provided ontology.

Do not silently invent an entirely different ruleset.

Expected semantic outcomes may include:

interestCompatibleWith
workStyleCompatibleWith
profileCompatibleWith

meetsCareerRequirement
hasCareerRequirementGap

hasMandatoryGapForCareer
hasRecommendedGapForCareer

careerCandidateAfterPostAssessment
developmentNeededForCareer

hasCandidateCourse

recommendationCategory

The exact available properties MUST be discovered from the actual ontology file before implementation.

==================================================
25. FEATURE — CAREER RECOMMENDATION ENGINE
==================================================

ONLY run final recommendation after required post-assessment completion.

Evaluate relevant CareerRoles.

Inputs may include:

StudentCompetency
CareerRequirement
Interest compatibility
WorkStyle compatibility
Mandatory competency gaps
Recommended competency gaps
RequirementWeight

Pipeline:

Student completes Post Assessment
↓
StudentCompetency updated
↓
SWRL reasoning
↓
Requirement fulfillment/gaps obtained
↓
Laravel RecommendationService calculates final score
↓
Careers ranked
↓
CareerRecommendation stored

==================================================
26. CAREER SCORING
==================================================

DO NOT put final scoring formula inside controllers.

Implement in RecommendationService.

The scoring model must be configurable.

A baseline competency fit formula may use:

competencyFit =
min(studentLevel / requiredLevel, 1)

weightedCompetency =
competencyFit * requirementWeight

competencyScore =
SUM(weightedCompetency) / SUM(requirementWeight) * 100

Interest compatibility and WorkStyle compatibility may contribute additional configurable factors.

Keep weight values easy to modify for research validation.

Do not bury constants across source code.

==================================================
27. CAREER RANKING
==================================================

Rank CareerRoles after scores are calculated.

Store results using CareerRecommendation.

Possible fields:

student
career
matchScore
readinessScore
recommendationRank
recommendationCategory
recommendedAt

Return configurable Top N recommendations.

Example:

1. Data Analyst
91%

2. Business Intelligence Analyst
86%

3. Business Analyst
79%

==================================================
28. FEATURE — CAREER RECOMMENDATION PAGE
==================================================

Create or improve a page such as:

Career Recommendation

Display top career recommendations.

Use existing card design.

Example information:

career title
career cluster
match percentage
match category
short explanation

Possible category:

Strong Match
Moderate Match
Developing Match

Do not display raw internal predicates to students.

==================================================
29. FEATURE — WHY THIS CAREER?
==================================================

THIS IS A HIGH-VALUE FEATURE.

Career recommendations must be explainable.

When the student opens a recommendation, show:

WHY THIS CAREER?

Interest Compatibility
✓ Data & Analytics

Work Style
✓ Analytical

Strong Competencies
✓ Excel
✓ SQL
✓ Problem Solving

Competencies To Improve
Statistics
Current Level 2
Required Level 3

Machine Learning
Current Level 1
Required Level 3

This explanation should be derived from ontology relations and recommendation calculations.

Do not generate generic AI explanations disconnected from actual data.

==================================================
30. FEATURE — CAREER EXPLORER
==================================================

Allow users to explore all CareerRoles separately from recommendation.

Group CareerRoles by CareerCluster.

Career detail should show:

role description
career cluster
required competencies
minimum levels
mandatory/recommended status

Career Explorer may be accessible earlier.

But personalized Career Recommendation remains locked until post-assessment.

==================================================
31. FEATURE — COMPETENCY GAP
==================================================

After recommendations exist, calculate competency gaps.

Concept:

Current StudentCompetency

vs.

CareerRequirement

Example:

SQL
Current: 2
Required: 4
Gap: 2

Statistics
Current: 2
Required: 3
Gap: 1

Distinguish if possible:

mandatory gap
recommended gap

==================================================
32. FEATURE — SET CAREER TARGET
==================================================

Allow the student to choose a recommended or explored CareerRole as their active career target.

Equivalent ontology relation:

Student
→ targetsCareer
→ CareerRole

Only one active target should exist at a time in the application.

Enforce this at application/database level.

==================================================
33. FEATURE — PERSONALIZED LEARNING PATH
==================================================

After a career target is selected:

generate a personalized learning path.

Flow:

Career Target
↓
CareerRequirement
↓
StudentCompetency
↓
CompetencyGap
↓
Candidate Courses
↓
LearningPath

LearningPathItem represents each learning step.

Each item may show:

competency gap
recommended course
priority
status
position

Possible statuses:

already_competent
pending
in_progress
completed

==================================================
34. COURSE SELECTION FOR GAP
==================================================

When multiple Courses teach the same Competency:

do not arbitrarily choose the first database row.

Consider:

gap relevance
contributionPercent
competencyGain
course prerequisites
student completion history
course difficulty
existing enrollment

Keep course selection logic inside LearningPathService.

==================================================
35. DASHBOARD
==================================================

Improve the EXISTING dashboard instead of replacing it.

Dashboard should adapt to user stage.

--------------------------------------------------
BEFORE PRE-ASSESSMENT
--------------------------------------------------

Show:

Complete Your Interest Assessment

--------------------------------------------------
AFTER PRE-ASSESSMENT
--------------------------------------------------

Show:

Interest Profile
Current Learning
Recommended Exploration Courses

--------------------------------------------------
DURING LEARNING
--------------------------------------------------

Show:

Course Progress
Continue Learning
Upcoming Assessment

--------------------------------------------------
AFTER POST-ASSESSMENT
--------------------------------------------------

Show:

Top Career Matches
Career Readiness
Competency Summary
Career Target
Learning Path Progress

Do not show empty recommendation cards before data exists.

==================================================
36. NAVIGATION
==================================================

Preserve existing sidebar/navigation styling.

Only add missing menu items where necessary.

Possible conceptual navigation:

Dashboard

Discover
Career Recommendation
Career Explorer
Career Target

Competency
My Competencies
Assessment

Learning
My Learning
Courses
Learning Path
Projects

Profile

DO NOT blindly apply this exact menu if the existing navigation already has equivalent sections.

Adapt it to the existing project.

==================================================
37. ADMIN
==================================================

Application admin is NOT an ontology actor.

Admin belongs to application authorization.

If existing admin functionality exists, extend it.

Possible management:

Career Clusters
Career Roles
Competencies
Career Requirements
Interest Areas
Work Styles
Courses
Modules
Assessments
Projects

Do not add Admin as an ontology class unless explicitly required by the ontology.

==================================================
38. ONTOLOGY DEBUG / SYSTEM HEALTH
==================================================

Create a small developer/admin diagnostic interface only if useful.

Possible checks:

Ontology file found
Ontology successfully loaded
CareerRole count
Competency count
Course count
SWRL reasoning available
Last reasoning execution

This page should NOT be visible as a primary student feature.

ASCII Animation may be used subtly here if appropriate.

==================================================
39. EMPTY STATES
==================================================

Use meaningful empty states.

Examples:

No pre-assessment:
"Complete your interest assessment to start your personalized learning journey."

No post-assessment:
"Complete the post-assessment after learning to unlock your career recommendations."

No recommendation:
"Career recommendations are not available yet."

No competency gap:
"Great progress. You currently meet the tracked competency requirements for this career."

Maintain existing UI style.

==================================================
40. LOADING STATES
==================================================

Reasoning/scoring may take time.

Provide user feedback such as:

"Analyzing your competency profile..."

"Comparing your competencies with career requirements..."

"Preparing your career recommendations..."

Use existing loading components first.

ASCII animation may optionally enhance one of these states only if visually compatible.

==================================================
41. ERROR HANDLING
==================================================

Gracefully handle:

ontology unavailable
ontology parse error
reasoning unavailable
missing competency
missing CareerRequirement
invalid assessment state
incomplete post-assessment
course mapping missing

Do not expose stack traces to students.

Log technical details.

Display understandable UI messages.

==================================================
42. FALLBACK BEHAVIOR
==================================================

If ontology reasoning temporarily fails:

do not destroy existing student data.

Do not mark assessment as failed.

Persist the application state safely.

Allow recommendation generation to be retried.

Existing LMS functionality must remain usable.

==================================================
43. DATABASE MIGRATIONS
==================================================

Before creating new tables:

inspect existing schema.

If an existing table can be extended safely:

extend it.

Avoid parallel duplicate concepts such as:

courses + ontology_courses
student_skills + student_competencies

unless there is a strong architectural reason.

Prefer a mapping identifier such as:

ontology_iri
ontology_key
ontology_code

when database records need correspondence with ontology entities.

==================================================
44. ONTOLOGY IDENTIFIER MAPPING
==================================================

Maintain stable mappings between database records and ontology entities.

Examples:

CareerRole
ontology_key = DataAnalyst

Competency
ontology_key = SQL

Course
ontology_key = C03

Do not match critical ontology records only by display label.

Labels may change.

==================================================
45. TESTING
==================================================

Add/update automated tests.

At minimum test:

registration flow
pre-assessment flow
interest profile creation
learning recommendation
course enrollment
assessment submission
post-assessment gate
StudentCompetency update
career recommendation generation
career ranking
competency gap detection
learning path creation

Also test:

user cannot access personalized career recommendation before post-assessment.

Existing tests MUST continue passing.

==================================================
46. UX QUALITY RULES
==================================================

Every major page must handle:

normal state
loading state
empty state
error state

Forms must show validation clearly.

Actions must provide feedback.

Buttons must not cause ambiguous behavior.

Avoid modal overuse.

Avoid massive pages with every piece of ontology data visible.

The ontology is backend intelligence.

The UI should remain simple for students.

==================================================
47. RESPONSIVENESS
==================================================

Maintain the application's existing responsive conventions.

Check:

desktop
tablet
mobile

Do not create desktop-only pages.

Tables containing competency data should degrade gracefully on smaller screens.

==================================================
48. ACCESSIBILITY
==================================================

Maintain:

semantic form labels
keyboard navigation
visible focus states
reasonable contrast
accessible buttons
proper heading hierarchy

Follow the patterns already present in the project.

==================================================
49. PERFORMANCE
==================================================

Do not parse a large ontology file unnecessarily on every Blade render.

Create a sensible service/caching strategy.

Avoid N+1 database queries.

Use eager loading where appropriate.

Do not execute full recommendation reasoning when the user simply opens a static course page.

Trigger expensive processing only when necessary.

==================================================
50. RECOMMENDATION RECALCULATION
==================================================

Recommendations are NOT permanent facts.

They may change when:

StudentCompetency changes
new assessment evidence appears
post-assessment is retaken
ontology knowledge changes
career requirement changes

Support recalculation without destroying historical records unnecessarily.

==================================================
51. USER EXPERIENCE AFTER RE-ASSESSMENT
==================================================

If competency improves:

show the effect clearly.

Example:

Previous Data Analyst Match
76%

Updated
88%

Improved competencies:
SQL +1 level
Power BI +1 level

Avoid gamification overload.

Keep presentation professional and appropriate for university students.

==================================================
52. DESIGN PRESERVATION CHECK
==================================================

Before completing ANY frontend feature, compare it against existing project pages.

Verify:

Does this look like the same application?

Does it use the same cards?

Does it use the same typography?

Does it use the same button styles?

Does it use the same spacing system?

Does it use the same sidebar?

Does it use the same color palette?

Does it use existing reusable components?

If the answer is no:

adjust the new feature to match the existing system.

==================================================
53. IMPLEMENTATION STRATEGY
==================================================

Do not implement everything blindly in one giant refactor.

Work incrementally.

Recommended sequence:

PHASE A
Project audit

PHASE B
Ontology inspection and mapping

PHASE C
Fix current data/domain inconsistencies

PHASE D
Pre-assessment integration

PHASE E
Interest-based learning flow

PHASE F
Course/module/assessment alignment

PHASE G
Post-assessment

PHASE H
Student competency update

PHASE I
Ontology/SWRL reasoning integration

PHASE J
RecommendationService

PHASE K
Career recommendation UI

PHASE L
Competency gap

PHASE M
Personalized learning path

PHASE N
Dashboard integration

PHASE O
Testing and cleanup

==================================================
54. DO NOT OVERENGINEER
==================================================

This project should remain understandable as an academic Information Systems project.

Avoid introducing unnecessary:

microservices
event buses
complex CQRS
distributed systems
GraphQL
message brokers
container orchestration
separate frontend applications

unless they already exist and are required.

Prefer clean Laravel architecture.

==================================================
55. CODE QUALITY
==================================================

Use:

Form Requests for complex validation
Services for business logic
Eloquent relationships
Enums/constants where appropriate
Policies/middleware for authorization
Transactions for multi-step persistence
clear naming
small focused methods

Avoid:

fat controllers
business logic inside Blade
giant service methods
duplicate queries
magic numbers
hardcoded ontology URIs everywhere

==================================================
56. DOCUMENTATION
==================================================

Document important integration decisions.

Create/update technical documentation describing:

Laravel ↔ Ontology architecture

Post-assessment workflow

Recommendation calculation

SWRL responsibility

Laravel responsibility

Ontology identifier mapping

Learning path generation

Do not generate excessive documentation for obvious CRUD.

==================================================
57. FINAL USER EXPERIENCE
==================================================

The final student journey should feel like:

"I registered."

↓

"IS-Path learned what areas I am interested in."

↓

"IS-Path suggested what I should learn first."

↓

"I completed learning material."

↓

"I completed my competency post-assessment."

↓

"IS-Path evaluated my actual competencies."

↓

"IS-Path compared them against Information Systems career requirements."

↓

"Now I know which careers fit me."

↓

"I can see WHY they fit me."

↓

"I can see what competencies I am still missing."

↓

"I have a learning path to close those gaps."

That is the core IS-Path experience.

==================================================
58. PRIMARY PRODUCT DIFFERENTIATOR
==================================================

The most important product chain is:

CAREER RECOMMENDATION
↓
EXPLAINABLE REASON
↓
COMPETENCY GAP
↓
PERSONALIZED LEARNING PATH

Do not allow secondary LMS features to hide this core value.

==================================================
59. ACCEPTANCE CRITERIA
==================================================

The implementation is considered successful when:

- existing application still works,
- current visual identity is preserved,
- existing routes/features are not unnecessarily broken,
- ontology can be read by the application integration layer,
- ontology entities are mapped cleanly,
- pre-assessment creates interest/work-style profile,
- students receive interest-based learning suggestions,
- learning progress works,
- post-assessment updates competencies,
- recommendation is unavailable before post-assessment,
- career recommendation works after post-assessment,
- recommendation is explainable,
- competency gaps are visible,
- career target can be selected,
- personalized learning path can be generated,
- existing UI patterns are consistently reused,
- new pages are responsive,
- loading/empty/error states exist,
- no unnecessary redesign occurs,
- automated tests cover critical flows.

==================================================
60. BEFORE MAKING CHANGES, OUTPUT YOUR PLAN
==================================================

Before modifying code, first give me:

A. CURRENT PROJECT ANALYSIS

Explain:
- existing architecture,
- current features,
- relevant database schema,
- existing frontend patterns,
- existing assessment/recommendation functionality.

B. ONTOLOGY ANALYSIS

Explain:
- classes found,
- properties found,
- SWRL rules found,
- important individuals/data,
- what should be ontology-driven.

C. GAP ANALYSIS

Create:

Current Feature
Status
Ontology Relation
Required Change

Use statuses:

KEEP
FIX
EXTEND
ADD
REMOVE ONLY IF DUPLICATE

D. IMPLEMENTATION PLAN

Explain exactly which files you intend to:

create
modify
leave untouched

E. RISK ANALYSIS

Identify anything that could break existing behavior.

ONLY AFTER THAT:

start implementation.

==================================================
61. IMPORTANT FINAL INSTRUCTION
==================================================

The objective is NOT:

"Make IS-Path look different."

The objective is:

"Make the existing IS-Path application actually behave according to its ontology-driven product concept."

Preserve the existing product.

Fix it.

Complete it.

Integrate the ontology.

Improve its UX.

Make the recommendation workflow coherent.

Use UI-UX Pro Max and Frontend God Mode to improve implementation quality while respecting the current design.

Use ASCII Animation only where it naturally fits.

DO NOT redesign for the sake of redesigning.

DO NOT destroy working code to produce prettier code.

DO NOT introduce unnecessary technology.

Inspect first.
Understand second.
Plan third.
Modify fourth.
Test everything.
