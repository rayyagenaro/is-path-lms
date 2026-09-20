<?php

namespace Tests\Unit;

use App\Domains\Ontology\Services\OntologyRepository;
use Tests\TestCase;

class OntologyRepositoryTest extends TestCase
{
    public function test_lms_ontology_is_readable_and_contains_the_expected_reasoning_rules(): void
    {
        $ontology = app(OntologyRepository::class);
        $summary = $ontology->summary();

        $this->assertFileExists($summary['source']);
        $this->assertGreaterThan(0, $summary['classes']);
        $this->assertGreaterThan(0, $summary['object_properties']);
        $this->assertGreaterThan(0, $summary['data_properties']);
        $this->assertSame(24, $summary['enabled_rule_count']);
        $this->assertTrue($ontology->hasEnabledRule('R07'));
        $this->assertTrue($ontology->hasEnabledRule('R24'));
    }
}
