<?php

namespace Tests\Feature;

use App\Models\Calculation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculationApiTest extends TestCase
{
    use RefreshDatabase;

    public function withSession(array $headers = []): array
    {
        return array_merge($headers, [
            'X-Calculator-Session' => 'test-session-123',
        ]);
    }

    public function test_creates_and_returns_simple_calculation(): void
    {
        $response = $this->postJson(
            '/api/calculations',
            ['expression' => '1+2*3'],
            $this->withSession(),
        );

        $response->assertCreated()
            ->assertJsonPath('calculation.expression', '1+2*3')
            ->assertJsonPath('calculation.had_error', false)
            ->assertJsonPath('calculation.result', '7');

        $this->assertDatabaseHas('calculations', [
            'session_token' => 'test-session-123',
            'expression' => '1+2*3',
            'result' => '7',
            'had_error' => false,
        ]);
    }

    public function test_filters_history_by_session(): void
    {
        Calculation::factory()->create([
            'session_token' => 'test-session-123',
            'expression' => '1+1',
            'result' => '2',
            'had_error' => false,
        ]);

        Calculation::factory()->create([
            'session_token' => 'other-session',
            'expression' => '2+2',
            'result' => '4',
            'had_error' => false,
        ]);

        $response = $this->getJson('/api/calculations', $this->withSession());

        $response->assertOk();
        $this->assertCount(1, $response->json('calculations'));
        $this->assertSame('1+1', $response->json('calculations.0.expression'));
    }

    public function test_complex_expression_evaluates_correctly(): void
    {
        $expression = 'sqrt((((9*9)/12)+(13-4))*2)^2';

        $response = $this->postJson(
            '/api/calculations',
            ['expression' => $expression],
            $this->withSession(),
        );

        $response->assertCreated();

        $result = (float) $response->json('calculation.result');
        $this->assertEqualsWithDelta(31.5, $result, 0.00001);
    }

    public function test_division_by_zero_sets_error(): void
    {
        $response = $this->postJson(
            '/api/calculations',
            ['expression' => '1/0'],
            $this->withSession(),
        );

        $response->assertCreated()
            ->assertJsonPath('calculation.had_error', true);
    }

    public function test_php_like_injection_expression_is_flagged_as_error(): void
    {
        $expression = "1 + 2; system('cat /etc/passwd');";

        $response = $this->postJson(
            '/api/calculations',
            ['expression' => $expression],
            $this->withSession(),
        );

        $response->assertCreated()
            ->assertJsonPath('calculation.expression', $expression)
            ->assertJsonPath('calculation.had_error', true);

        $this->assertNull($response->json('calculation.result'));

        $this->assertDatabaseHas('calculations', [
            'session_token' => 'test-session-123',
            'expression' => $expression,
            'had_error' => true,
        ]);
    }

    public function test_very_deeply_nested_expression_is_treated_as_too_complex(): void
    {
        $expression = str_repeat('(', 25).'1'.str_repeat(')', 25);

        $response = $this->postJson(
            '/api/calculations',
            ['expression' => $expression],
            $this->withSession(),
        );

        $response->assertCreated()
            ->assertJsonPath('calculation.had_error', true);
    }

    public function test_expression_with_too_many_operators_is_treated_as_too_complex(): void
    {
        $expression = '1'.str_repeat('+1', 120);

        $response = $this->postJson(
            '/api/calculations',
            ['expression' => $expression],
            $this->withSession(),
        );

        $response->assertCreated()
            ->assertJsonPath('calculation.had_error', true);
    }

    public function test_rate_limiting_on_calculation_creation(): void
    {
        // Within the limit: 30 requests per minute should succeed.
        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson(
                '/api/calculations',
                ['expression' => '1+1'],
                $this->withSession(),
            );

            $response->assertCreated();
        }

        // The next request should be rejected with 429 Too Many Requests.
        $response = $this->postJson(
            '/api/calculations',
            ['expression' => '1+1'],
            $this->withSession(),
        );

        $response->assertStatus(429);
    }

    public function test_xss_like_expression_is_stored_verbatim_and_treated_as_data(): void
    {
        $expression = "<script>alert('xss')</script>";

        $response = $this->postJson(
            '/api/calculations',
            ['expression' => $expression],
            $this->withSession(),
        );

        $response->assertCreated()
            ->assertJsonPath('calculation.expression', $expression);

        $this->assertDatabaseHas('calculations', [
            'session_token' => 'test-session-123',
            'expression' => $expression,
        ]);
    }

    public function test_requires_session_header(): void
    {
        $response = $this->postJson('/api/calculations', ['expression' => '1+1']);

        $response->assertStatus(400);
    }
}

