<?php

namespace App\Interfaces;

use App\Models\User;
use App\Interfaces\SubscriptionRepositoryInterface;

interface SubscriptionPlanStrategyInterface
{
    public function subscribe(User $user, array $data): array;
    public function getName(): string;
    public function setRepository(SubscriptionRepositoryInterface $repository): void;
} 