<?php

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Subscription;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $plainTextToken = 'L5V9R0P2S4T6U8W0X2Y4Z6A8B0C2D4E6F8G0H1J3K5';
        $tokenName = 'general-api-access-token';
        $abilities = ['*'];

        $user = User::firstOrCreate(
            ['email' => 'general.api.user@example.com'],
            [
                'name' => 'General API User',
                'password' => Hash::make('api-password-for-general-user'),
                'email_verified_at' => now(),
            ]
        );

        $user->tokens()->where('name', $tokenName)->delete();

        $token = $user->createToken($tokenName, $abilities);

        PersonalAccessToken::where('id', $token->accessToken->id)->update([
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => null,
        ]);

        $user->subscriptions()->firstOrCreate(
            ['user_id' => $user->id, 'plan_name' => SubscriptionPlan::BASIC->value],
            [
                'starts_at' => Carbon::now(),
                'ends_at' => null,
                'status' => SubscriptionStatus::ACTIVE->value,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tokenName = 'general-api-access-token';
        PersonalAccessToken::where('name', $tokenName)->delete();

        $user = User::where('email', 'general.api.user@example.com')->first();
        if ($user) {
            Subscription::where('user_id', $user->id)
                ->where('plan_name', SubscriptionPlan::BASIC->value)
                ->delete();
        }

        User::where('email', 'general.api.user@example.com')->delete();
    }
};
