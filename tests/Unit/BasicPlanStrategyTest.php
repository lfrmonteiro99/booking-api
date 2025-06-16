<?php

namespace Tests\Unit;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Interfaces\SubscriptionRepositoryInterface;
use App\Models\User;
use App\Models\Subscription;
use App\Strategies\BasicPlanStrategy;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BasicPlanStrategyTest extends TestCase
{
    private $repository;
    private $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(SubscriptionRepositoryInterface::class);
        $this->strategy = new BasicPlanStrategy();
        $this->strategy->setRepository($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_basic_plan_name(): void
    {
        $this->assertEquals(SubscriptionPlan::BASIC->value, $this->strategy->getName());
    }

    #[Test]
    public function it_subscribes_user_to_basic_plan(): void
    {
        $user = User::factory()->make();
        $data = [];
        $startsAt = Carbon::now();
        $endsAt = Carbon::now()->addMonths(SubscriptionPlan::BASIC->getDuration());

        $expectedSubscription = new Subscription([
            'user_id' => $user->id,
            'plan_name' => SubscriptionPlan::BASIC->value,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $this->repository->shouldReceive('createSubscription')
            ->once()
            ->with(
                Mockery::any(),
                'basic',
                Mockery::any(),
                Mockery::any(),
                'active'
            )
            ->andReturn($expectedSubscription);

        $result = $this->strategy->subscribe($user, $data);

        $this->assertIsArray($result);
        $this->assertEquals('Successfully subscribed to Basic Plan.', $result['message']);
        $this->assertEquals($expectedSubscription->toArray(), $result['subscription']);
    }
} 