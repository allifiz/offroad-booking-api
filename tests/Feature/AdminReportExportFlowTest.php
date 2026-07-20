<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminReportExportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_stream_all_csv_reports(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

        foreach (['bookings', 'payments', 'drivers', 'withdrawals'] as $report) {
            $response = $this->get('/api/v1/admin/reports/export/'.$report.'?date_from=2026-07-01&date_to=2026-07-31');

            $response
                ->assertOk()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8')
                ->assertHeader('x-content-type-options', 'nosniff');

            $this->assertStringContainsString(
                'attachment; filename='.$report.'-',
                (string) $response->headers->get('content-disposition'),
            );

            $content = $response->streamedContent();

            $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
            $this->assertStringContainsString("\n", $content);
        }
    }

    public function test_non_admin_cannot_export_reports(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::CUSTOMER]));

        $this->get('/api/v1/admin/reports/export/bookings')->assertForbidden();
    }

    public function test_export_validates_status_and_period(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

        $this->getJson('/api/v1/admin/reports/export/bookings?status=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->getJson('/api/v1/admin/reports/export/payments?date_from=2026-07-31&date_to=2026-07-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');

        $this->getJson('/api/v1/admin/reports/export/drivers?date_from=2025-01-01&date_to=2026-07-31')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_from');
    }
}
