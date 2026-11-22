<?php

declare(strict_types=1);

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');
