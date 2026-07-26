<?php

namespace Tests\Feature;

use App\Models\LessonLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInformationLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_information_page_only_lists_completed_and_charged_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $date = '2026-07-15';

        $completed = LessonLog::factory()->create([
            'date' => $date,
            'status' => 'completed',
            'notes' => 'Completed marker',
        ]);
        $charged = LessonLog::factory()->create([
            'date' => $date,
            'status' => 'charged',
            'notes' => 'Charged marker',
        ]);
        LessonLog::factory()->create([
            'date' => $date,
            'status' => 'cancelled',
            'notes' => 'Cancelled marker',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.information.index', ['date' => $date, 'view' => 'day']))
            ->assertOk()
            ->assertViewHas('logs', function ($logs) use ($completed, $charged) {
                return $logs->count() === 2
                    && $logs->pluck('id')->contains($completed->id)
                    && $logs->pluck('id')->contains($charged->id);
            })
            ->assertSee('Completed marker')
            ->assertSee('Charged marker')
            ->assertDontSee('Cancelled marker');
    }

    public function test_information_page_rejects_invalid_period_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.information.index'))
            ->get(route('admin.information.index', [
                'date' => 'not-a-date',
                'view' => 'month',
            ]))
            ->assertRedirect(route('admin.information.index'))
            ->assertSessionHasErrors(['date', 'view']);
    }

    public function test_week_view_exposes_machine_sortable_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        LessonLog::factory()->create([
            'date' => '2026-07-01',
            'time' => '10:00:00',
            'status' => 'completed',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.information.index', [
                'date' => '2026-07-01',
                'view' => 'week',
            ]))
            ->assertOk()
            ->assertSee('data-order="2026-07-01"', false)
            ->assertSee('order: [[0,"asc"],[1,"asc"]]', false);
    }
}
