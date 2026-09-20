<?php

namespace Tests\Unit;

use App\Support\RolePresentation;
use PHPUnit\Framework\TestCase;

class RolePresentationTest extends TestCase
{
    public function test_role_initials_are_based_on_the_role_not_its_cluster(): void
    {
        $this->assertSame('DA', RolePresentation::initials('Data Analyst'));
        $this->assertSame('DS', RolePresentation::initials('Data Scientist'));
        $this->assertSame('BA', RolePresentation::initials('Business Analyst'));
        $this->assertSame('UX', RolePresentation::initials('UI/UX Designer'));
    }
}
