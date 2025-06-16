<?php

namespace Tests\Unit;

use App\Http\Requests\SubscribeUserRequest;
use App\Enums\SubscriptionPlan;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscribeUserRequestTest extends TestCase
{
    #[Test]
    public function it_validates_plan_name_is_required()
    {
        $validator = Validator::make([], (new SubscribeUserRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('plan_name', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_plan_name_must_be_a_valid_enum_value()
    {
        $validator = Validator::make(['plan_name' => 'invalid_plan'], (new SubscribeUserRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('plan_name', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_plan_name_can_be_a_valid_enum_value()
    {
        $validator = Validator::make(['plan_name' => SubscriptionPlan::BASIC->value], (new SubscribeUserRequest())->rules());
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_validates_ends_at_is_optional()
    {
        $validator = Validator::make(['plan_name' => SubscriptionPlan::BASIC->value], (new SubscribeUserRequest())->rules());
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_validates_ends_at_must_be_a_valid_date()
    {
        $validator = Validator::make(['plan_name' => SubscriptionPlan::BASIC->value, 'ends_at' => 'invalid_date'], (new SubscribeUserRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ends_at', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_ends_at_must_be_after_now()
    {
        $validator = Validator::make(['plan_name' => SubscriptionPlan::BASIC->value, 'ends_at' => now()->subDay()->toDateString()], (new SubscribeUserRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ends_at', $validator->errors()->toArray());
    }
} 