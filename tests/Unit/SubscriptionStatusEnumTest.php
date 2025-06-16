<?php

namespace Tests\Unit;

use App\Enums\SubscriptionStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionStatusEnumTest extends TestCase
{
    #[Test]
    public function it_has_correct_status_values()
    {
        $this->assertEquals('active', SubscriptionStatus::ACTIVE->value);
        $this->assertEquals('inactive', SubscriptionStatus::INACTIVE->value);
        $this->assertEquals('expired', SubscriptionStatus::EXPIRED->value);
        $this->assertEquals('cancelled', SubscriptionStatus::CANCELLED->value);
    }

    #[Test]
    public function it_correctly_identifies_active_status()
    {
        $this->assertTrue(SubscriptionStatus::ACTIVE->isActive());
        $this->assertFalse(SubscriptionStatus::INACTIVE->isActive());
        $this->assertFalse(SubscriptionStatus::EXPIRED->isActive());
        $this->assertFalse(SubscriptionStatus::CANCELLED->isActive());
    }

    #[Test]
    public function it_can_be_created_from_value()
    {
        $this->assertEquals(SubscriptionStatus::ACTIVE, SubscriptionStatus::from('active'));
        $this->assertEquals(SubscriptionStatus::INACTIVE, SubscriptionStatus::from('inactive'));
        $this->assertEquals(SubscriptionStatus::EXPIRED, SubscriptionStatus::from('expired'));
        $this->assertEquals(SubscriptionStatus::CANCELLED, SubscriptionStatus::from('cancelled'));
    }

    #[Test]
    public function it_throws_exception_for_invalid_value()
    {
        $this->expectException(\ValueError::class);
        SubscriptionStatus::from('invalid_status');
    }

    #[Test]
    public function it_can_try_to_create_from_value()
    {
        $this->assertEquals(SubscriptionStatus::ACTIVE, SubscriptionStatus::tryFrom('active'));
        $this->assertEquals(SubscriptionStatus::INACTIVE, SubscriptionStatus::tryFrom('inactive'));
        $this->assertEquals(SubscriptionStatus::EXPIRED, SubscriptionStatus::tryFrom('expired'));
        $this->assertEquals(SubscriptionStatus::CANCELLED, SubscriptionStatus::tryFrom('cancelled'));
        $this->assertNull(SubscriptionStatus::tryFrom('invalid_status'));
    }
} 