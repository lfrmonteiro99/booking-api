<?php

namespace Tests\Unit;

use App\Enums\SubscriptionPlanLimit;
use App\Enums\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPlanLimitEnumTest extends TestCase
{
    #[Test]
    public function it_returns_correct_limit_for_each_plan_name()
    {
        $this->assertEquals(SubscriptionPlanLimit::BASIC->value, SubscriptionPlanLimit::getLimit('basic'));
        $this->assertEquals(SubscriptionPlanLimit::PREMIUM->value, SubscriptionPlanLimit::getLimit('premium'));
        $this->assertEquals(SubscriptionPlanLimit::PRO->value, SubscriptionPlanLimit::getLimit('pro'));
        $this->assertEquals(SubscriptionPlanLimit::ENTERPRISE->value, SubscriptionPlanLimit::getLimit('enterprise'));
    }

    #[Test]
    public function it_defaults_to_basic_limit_for_invalid_plan()
    {
        $this->assertEquals(SubscriptionPlanLimit::BASIC->value, SubscriptionPlanLimit::getLimit('invalid_plan'));
    }
} 