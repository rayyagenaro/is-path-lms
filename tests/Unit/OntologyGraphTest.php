<?php
namespace Tests\Unit;

use App\Domains\Ontology\Services\OntologyGraph;
use PHPUnit\Framework\TestCase;

class OntologyGraphTest extends TestCase
{
    public function test_it_detects_a_hierarchy_cycle(): void
    {
        $graph = new OntologyGraph();
        $this->assertTrue($graph->wouldCreateCycle(1, 3, [2=>1, 3=>2]));
        $this->assertFalse($graph->wouldCreateCycle(4, 3, [2=>1, 3=>2]));
    }
}
