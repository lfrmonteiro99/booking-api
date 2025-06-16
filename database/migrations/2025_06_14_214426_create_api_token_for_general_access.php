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
        // We don't need to create a new table for the token itself
        // as Sanctum uses the 'personal_access_tokens' table.
        // This migration will instead create a user and assign a fixed token.

        $plainTextToken = 'L5V9R0P2S4T6U8W0X2Y4Z6A8B0C2D4E6F8G0H1J3K5';
        $tokenName = 'general-api-access-token';
        $abilities = ['*']; // Allow all abilities for general access

        // Find or create a dedicated user for general API access
        $user = User::firstOrCreate(
            ['email' => 'general.api.user@example.com'],
            [
                'name' => 'General API User',
                'password' => Hash::make('api-password-for-general-user'), // Use a strong, random password
                'email_verified_at' => now(),
            ]
        );

        // Delete any existing token with the same name for this user to ensure uniqueness
        $user->tokens()->where('name', $tokenName)->delete();

        // Create the personal access token
        $token = $user->createToken($tokenName, $abilities);

        // Override the token in the database with the SHA-256 hash of the plainTextToken
        // This ensures the token you distribute is the one that's valid.
        PersonalAccessToken::where('id', $token->accessToken->id)->update([
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => null, // Set to null for no expiration
        ]);

        // Create a basic subscription for this user
        $user->subscriptions()->firstOrCreate(
            ['user_id' => $user->id, 'plan_name' => SubscriptionPlan::BASIC->value],
            [
                'starts_at' => Carbon::now(),
                'ends_at' => null, // No expiration date
                'status' => SubscriptionStatus::ACTIVE->value,
            ]
        );
        
        // Log the token (for development/testing purposes only)
        // For production, retrieve this token from your database or config securely.
        
        // You can now use the following token in your Authorization header:
        // Authorization: Bearer L5V9R0P2S4T6U8W0X2Y4Z6A8B0C2D4E6F8G0H1J3K5
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete the token created by this migration
        $tokenName = 'general-api-access-token';
        PersonalAccessToken::where('name', $tokenName)->delete();

        // Delete the subscription created by this migration
        $user = User::where('email', 'general.api.user@example.com')->first();
        if ($user) {
            Subscription::where('user_id', $user->id)
                ->where('plan_name', SubscriptionPlan::BASIC->value)
                ->delete();
        }

        // Optionally, delete the dedicated user as well
        // User::where('email', 'general.api.user@example.com')->delete();
    }
};
