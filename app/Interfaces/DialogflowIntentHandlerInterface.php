<?php

namespace App\Interfaces;

interface DialogflowIntentHandlerInterface
{
    public function handle(array $params): string;
} 