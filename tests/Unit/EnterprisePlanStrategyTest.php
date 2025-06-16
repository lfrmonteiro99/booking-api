<?php

namespace Tests\Unit;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Interfaces\SubscriptionRepositoryInterface;
use App\Models\User;
use App\Models\Subscription;
use App\Strategies\EnterprisePlanStrategy;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnterprisePlanStrategyTest extends TestCase
{
    private $repository;
    private $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(SubscriptionRepositoryInterface::class);
        $this->strategy = new EnterprisePlanStrategy();
        $this->strategy->setRepository($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_enterprise_plan_name(): void
    {
        $this->assertEquals(SubscriptionPlan::ENTERPRISE->value, $this->strategy->getName());
    }

    #[Test]
    public function it_subscribes_user_to_enterprise_plan_with_end_date(): void
    {
        $user = User::factory()->make();
        $endsAt = Carbon::now()->addYear();
        $data = ['ends_at' => $endsAt->format('Y-m-d H:i:s')];
        $startsAt = Carbon::now();

        $expectedSubscription = new Subscription([
            'user_id' => $user->id,
            'plan_name' => SubscriptionPlan::ENTERPRISE->value,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $this->repository->shouldReceive('createSubscription')
            ->once()
            ->with(
                Mockery::any(),
                'enterprise',
                Mockery::any(),
                Mockery::any(),
                'active'
            )
            ->andReturn($expectedSubscription);

        $result = $this->strategy->subscribe($user, $data);

        $this->assertIsArray($result);
        $this->assertEquals('Successfully subscribed to Enterprise Plan.', $result['message']);
        $this->assertEquals($expectedSubscription->toArray(), $result['subscription']);
    }

    #[Test]
    public function it_subscribes_user_to_enterprise_plan_without_end_date(): void
    {
        $user = User::factory()->make();
        $data = [];
        $startsAt = Carbon::now();
        $endsAt = null; // No end date for this scenario

        $expectedSubscription = new Subscription([
            'user_id' => $user->id,
            'plan_name' => SubscriptionPlan::ENTERPRISE->value,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $this->repository->shouldReceive('createSubscription')
            ->once()
            ->with(
                Mockery::any(),
                'enterprise',
                Mockery::any(),
                null, // Expect null for ends_at
                'active'
            )
            ->andReturn($expectedSubscription);

        $result = $this->strategy->subscribe($user, $data);

        $this->assertIsArray($result);
        $this->assertEquals('Successfully subscribed to Enterprise Plan.', $result['message']);
        $this->assertEquals($expectedSubscription->toArray(), $result['subscription']);
    }
} 