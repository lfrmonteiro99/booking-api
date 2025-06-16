<?php

namespace Tests\Unit;

use App\Enums\SubscriptionPlan;
use App\Factories\SubscriptionPlanFactory;
use App\Interfaces\SubscriptionRepositoryInterface;
use App\Strategies\BasicPlanStrategy;
use App\Strategies\PremiumPlanStrategy;
use App\Strategies\ProPlanStrategy;
use App\Strategies\EnterprisePlanStrategy;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPlanFactoryTest extends TestCase
{
    private $subscriptionRepository;
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionRepository = Mockery::mock(SubscriptionRepositoryInterface::class);
        $this->factory = new SubscriptionPlanFactory($this->subscriptionRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_creates_basic_plan_strategy(): void
    {
        $strategy = $this->factory->create(SubscriptionPlan::BASIC->value);
        $this->assertInstanceOf(BasicPlanStrategy::class, $strategy);
    }

    #[Test]
    public function it_creates_premium_plan_strategy(): void
    {
        $strategy = $this->factory->create(SubscriptionPlan::PREMIUM->value);
        $this->assertInstanceOf(PremiumPlanStrategy::class, $strategy);
    }

    #[Test]
    public function it_creates_pro_plan_strategy(): void
    {
        $strategy = $this->factory->create(SubscriptionPlan::PRO->value);
        $this->assertInstanceOf(ProPlanStrategy::class, $strategy);
    }

    #[Test]
    public function it_creates_enterprise_plan_strategy(): void
    {
        $strategy = $this->factory->create(SubscriptionPlan::ENTERPRISE->value);
        $this->assertInstanceOf(EnterprisePlanStrategy::class, $strategy);
    }

    #[Test]
    public function it_throws_exception_for_invalid_plan(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid subscription plan: non_existent_plan');
        $this->factory->create('non_existent_plan');
    }
} 