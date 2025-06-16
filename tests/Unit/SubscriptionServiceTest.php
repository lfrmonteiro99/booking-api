<?php

namespace Tests\Unit;

use App\Enums\SubscriptionPlan;
use App\Factories\SubscriptionPlanFactory;
use App\Interfaces\SubscriptionRepositoryInterface;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Strategies\BasicPlanStrategy;
use App\Strategies\PremiumPlanStrategy;
use App\Strategies\ProPlanStrategy;
use App\Strategies\EnterprisePlanStrategy;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    private $subscriptionPlanFactory;
    private $subscriptionRepository;
    private $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionRepository = Mockery::mock(SubscriptionRepositoryInterface::class);
        $this->subscriptionPlanFactory = Mockery::mock(SubscriptionPlanFactory::class);
        $this->subscriptionService = new SubscriptionService($this->subscriptionPlanFactory);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_subscribes_basic_plan_successfully()
    {
        $user = User::factory()->make();
        $planName = SubscriptionPlan::BASIC->value;
        $data = [];
        $expectedResult = ['message' => 'Successfully subscribed to Basic Plan.', 'subscription' => ['plan_name' => $planName]];

        $basicPlanStrategy = Mockery::mock(BasicPlanStrategy::class);
        $basicPlanStrategy->shouldReceive('setRepository')->once()->with($this->subscriptionRepository);
        $basicPlanStrategy->shouldReceive('subscribe')->once()->with($user, $data)->andReturn($expectedResult);

        $this->subscriptionPlanFactory->shouldReceive('create')
            ->once()
            ->with($planName)
            ->andReturnUsing(function () use ($basicPlanStrategy) {
                $basicPlanStrategy->setRepository($this->subscriptionRepository);
                return $basicPlanStrategy;
            });

        $result = $this->subscriptionService->subscribeUserToPlan($user, $planName, $data);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function it_subscribes_premium_plan_successfully()
    {
        $user = User::factory()->make();
        $planName = SubscriptionPlan::PREMIUM->value;
        $data = [];
        $expectedResult = ['message' => 'Successfully subscribed to Premium Plan.', 'subscription' => ['plan_name' => $planName]];

        $premiumPlanStrategy = Mockery::mock(PremiumPlanStrategy::class);
        $premiumPlanStrategy->shouldReceive('setRepository')->once()->with($this->subscriptionRepository);
        $premiumPlanStrategy->shouldReceive('subscribe')->once()->with($user, $data)->andReturn($expectedResult);

        $this->subscriptionPlanFactory->shouldReceive('create')
            ->once()
            ->with($planName)
            ->andReturnUsing(function () use ($premiumPlanStrategy) {
                $premiumPlanStrategy->setRepository($this->subscriptionRepository);
                return $premiumPlanStrategy;
            });

        $result = $this->subscriptionService->subscribeUserToPlan($user, $planName, $data);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function it_subscribes_pro_plan_successfully()
    {
        $user = User::factory()->make();
        $planName = SubscriptionPlan::PRO->value;
        $data = [];
        $expectedResult = ['message' => 'Successfully subscribed to Pro Plan.', 'subscription' => ['plan_name' => $planName]];

        $proPlanStrategy = Mockery::mock(ProPlanStrategy::class);
        $proPlanStrategy->shouldReceive('setRepository')->once()->with($this->subscriptionRepository);
        $proPlanStrategy->shouldReceive('subscribe')->once()->with($user, $data)->andReturn($expectedResult);

        $this->subscriptionPlanFactory->shouldReceive('create')
            ->once()
            ->with($planName)
            ->andReturnUsing(function () use ($proPlanStrategy) {
                $proPlanStrategy->setRepository($this->subscriptionRepository);
                return $proPlanStrategy;
            });

        $result = $this->subscriptionService->subscribeUserToPlan($user, $planName, $data);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function it_subscribes_enterprise_plan_successfully()
    {
        $user = User::factory()->make();
        $planName = SubscriptionPlan::ENTERPRISE->value;
        $data = ['ends_at' => '2025-12-31'];
        $expectedResult = ['message' => 'Successfully subscribed to Enterprise Plan.', 'subscription' => ['plan_name' => $planName]];

        $enterprisePlanStrategy = Mockery::mock(EnterprisePlanStrategy::class);
        $enterprisePlanStrategy->shouldReceive('setRepository')->once()->with($this->subscriptionRepository);
        $enterprisePlanStrategy->shouldReceive('subscribe')->once()->with($user, $data)->andReturn($expectedResult);

        $this->subscriptionPlanFactory->shouldReceive('create')
            ->once()
            ->with($planName)
            ->andReturnUsing(function () use ($enterprisePlanStrategy) {
                $enterprisePlanStrategy->setRepository($this->subscriptionRepository);
                return $enterprisePlanStrategy;
            });

        $result = $this->subscriptionService->subscribeUserToPlan($user, $planName, $data);

        $this->assertEquals($expectedResult, $result);
    }
} 