<?php

namespace Tests\Unit;

use App\Enums\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPlanEnumTest extends TestCase
{
    #[Test]
    public function it_has_correct_plan_values()
    {
        $this->assertEquals('basic', SubscriptionPlan::BASIC->value);
        $this->assertEquals('premium', SubscriptionPlan::PREMIUM->value);
        $this->assertEquals('pro', SubscriptionPlan::PRO->value);
        $this->assertEquals('enterprise', SubscriptionPlan::ENTERPRISE->value);
    }

    #[Test]
    public function it_returns_correct_duration_for_each_plan()
    {
        $this->assertEquals(1, SubscriptionPlan::BASIC->getDuration());
        $this->assertEquals(6, SubscriptionPlan::PREMIUM->getDuration());
        $this->assertEquals(12, SubscriptionPlan::PRO->getDuration());
        $this->assertEquals(0, SubscriptionPlan::ENTERPRISE->getDuration());
    }

    #[Test]
    public function it_returns_correct_duration_unit_for_each_plan()
    {
        $this->assertEquals('months', SubscriptionPlan::BASIC->getDurationUnit());
        $this->assertEquals('months', SubscriptionPlan::PREMIUM->getDurationUnit());
        $this->assertEquals('months', SubscriptionPlan::PRO->getDurationUnit());
        $this->assertEquals('custom', SubscriptionPlan::ENTERPRISE->getDurationUnit());
    }

    #[Test]
    public function it_can_be_created_from_value()
    {
        $this->assertEquals(SubscriptionPlan::BASIC, SubscriptionPlan::from('basic'));
        $this->assertEquals(SubscriptionPlan::PREMIUM, SubscriptionPlan::from('premium'));
        $this->assertEquals(SubscriptionPlan::PRO, SubscriptionPlan::from('pro'));
        $this->assertEquals(SubscriptionPlan::ENTERPRISE, SubscriptionPlan::from('enterprise'));
    }

    #[Test]
    public function it_throws_exception_for_invalid_value()
    {
        $this->expectException(\ValueError::class);
        SubscriptionPlan::from('invalid_plan');
    }

    #[Test]
    public function it_can_try_to_create_from_value()
    {
        $this->assertEquals(SubscriptionPlan::BASIC, SubscriptionPlan::tryFrom('basic'));
        $this->assertEquals(SubscriptionPlan::PREMIUM, SubscriptionPlan::tryFrom('premium'));
        $this->assertEquals(SubscriptionPlan::PRO, SubscriptionPlan::tryFrom('pro'));
        $this->assertEquals(SubscriptionPlan::ENTERPRISE, SubscriptionPlan::tryFrom('enterprise'));
        $this->assertNull(SubscriptionPlan::tryFrom('invalid_plan'));
    }
} 