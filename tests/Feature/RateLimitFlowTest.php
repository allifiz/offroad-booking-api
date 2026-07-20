<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('rate-limit-test');
    }

    public function test_login_is_limited_per_email_and_ip(): void
    {
        $payload = [
            'email' => 'blocked@example.test',
            'password' => 'wrong-password',
            'device_name' => 'rate-limit-test',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', $payload)
                ->assertUnprocessable();
        }

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response
            ->assertStatus(429)
            ->assertHeader('Retry-After');

        $this->postJson('/api/v1/auth/login', [
            ...$payload,
            'email' => 'another@example.test',
        ])->assertUnprocessable();
    }

    public function test_driver_and_customer_registration_share_the_same_ip_limit(): void
    {
        $this->postJson('/api/v1/driver/register', [])->assertUnprocessable();
        $this->postJson('/api/v1/customers/register', [])->assertUnprocessable();
        $this->postJson('/api/v1/driver/register', [])->assertUnprocessable();

        $this->postJson('/api/v1/customers/register', [])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }
}
