<?php

namespace Tests\Unit;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Interfaces\SubscriptionRepositoryInterface;
use App\Models\User;
use App\Models\Subscription;
use App\Strategies\ProPlanStrategy;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProPlanStrategyTest extends TestCase
{
    private $repository;
    private $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(SubscriptionRepositoryInterface::class);
        $this->strategy = new ProPlanStrategy();
        $this->strategy->setRepository($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_pro_plan_name(): void
    {
        $this->assertEquals(SubscriptionPlan::PRO->value, $this->strategy->getName());
    }

    #[Test]
    public function it_subscribes_user_to_pro_plan(): void
    {
        $user = User::factory()->make();
        $data = [];
        $startsAt = Carbon::now();
        $endsAt = Carbon::now()->addMonths(SubscriptionPlan::PRO->getDuration());

        $expectedSubscription = new Subscription([
            'user_id' => $user->id,
            'plan_name' => SubscriptionPlan::PRO->value,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $this->repository->shouldReceive('createSubscription')
            ->once()
            ->with(
                Mockery::any(),
                'pro',
                Mockery::any(),
                Mockery::any(),
                'active'
            )
            ->andReturn($expectedSubscription);

        $result = $this->strategy->subscribe($user, $data);

        $this->assertIsArray($result);
        $this->assertEquals('Successfully subscribed to Pro Plan.', $result['message']);
        $this->assertEquals($expectedSubscription->toArray(), $result['subscription']);
    }
} 