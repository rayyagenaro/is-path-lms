<?php
namespace Tests\Unit;

use App\Domains\Recommendation\Services\RecommendationEngine;
use PHPUnit\Framework\TestCase;

class RecommendationEngineTest extends TestCase
{
    public function test_it_calculates_weighted_match_score(): void
    {
        $result=(new RecommendationEngine())->calculate([1=>80,2=>60],[
            ['competency_id'=>1,'name'=>'SQL','weight'=>60,'minimum_level'=>3,'requirement_type'=>'mandatory'],
            ['competency_id'=>2,'name'=>'Statistics','weight'=>40,'minimum_level'=>2,'requirement_type'=>'recommended'],
        ]);
        $this->assertSame(72.0,$result['score']);
        $this->assertSame('Recommended',$result['category']);
    }

    public function test_mandatory_gap_overrides_a_high_numeric_score(): void
    {
        $result=(new RecommendationEngine())->calculate([1=>100,2=>20],[
            ['competency_id'=>1,'name'=>'Communication','weight'=>90,'minimum_level'=>2,'requirement_type'=>'optional'],
            ['competency_id'=>2,'name'=>'SQL','weight'=>10,'minimum_level'=>3,'requirement_type'=>'mandatory'],
        ]);
        $this->assertSame(92.0,$result['score']);
        $this->assertSame('Not Ready',$result['category']);
        $this->assertCount(1,$result['details']['blocking']);
    }
}
