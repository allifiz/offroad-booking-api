<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebReportsAuditFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_reports_download_csv_and_view_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $log = AuditLog::query()->create([
            'actor_id' => $admin->id,
            'event' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'old_values' => ['name' => 'Admin Lama'],
            'new_values' => ['name' => 'Admin Baru'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'url' => '/admin/test',
            'request_method' => 'PATCH',
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Export laporan CSV');

        $this->actingAs($admin)
            ->get('/admin/reports/export/bookings')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($admin)
            ->get('/admin/audit-logs?event=updated')
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee('updated')
            ->assertSee('User');

        $this->actingAs($admin)
            ->get("/admin/audit-logs/{$log->id}")
            ->assertOk()
            ->assertSee('Before')
            ->assertSee('After')
            ->assertSee('Admin Lama')
            ->assertSee('Admin Baru');
    }

    public function test_non_admin_cannot_access_reports_or_audit_logs(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/admin/reports')->assertForbidden();
        $this->actingAs($customer)->get('/admin/audit-logs')->assertForbidden();
    }
}
