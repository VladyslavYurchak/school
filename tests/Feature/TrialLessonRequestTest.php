<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\TrialLessonRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialLessonRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_trial_lesson_form_creates_new_request(): void
    {
        $this
            ->post(route('trial-lesson-requests.store'), [
                'name' => 'Олена',
                'phone' => '+380991112233',
                'email' => 'olena@example.com',
                'preferred_contact' => 'telegram',
                'notes' => 'Хочу англійську для дитини.',
            ])
            ->assertRedirect()
            ->assertSessionHas('trial_request_success')
            ->assertSessionHas('analytics_event.name', 'generate_lead')
            ->assertSessionHas('analytics_event.parameters.method', 'trial_lesson_form');

        $this->assertDatabaseHas('trial_lesson_requests', [
            'name' => 'Олена',
            'phone' => '+380991112233',
            'email' => 'olena@example.com',
            'preferred_contact' => 'telegram',
            'status' => 'new',
        ]);
    }

    public function test_admin_main_shows_new_trial_lesson_requests_and_badge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        TrialLessonRequest::create([
            'name' => 'Марія',
            'phone' => '+380671112233',
            'email' => 'maria@example.com',
            'preferred_contact' => 'phone',
            'notes' => 'Потрібне пробне заняття.',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Заявки на безкоштовне заняття')
            ->assertSee('Нових: 1')
            ->assertSee('Марія')
            ->assertSee('+380671112233')
            ->assertSee('Оброблено');
    }

    public function test_teacher_main_does_not_show_trial_lesson_requests(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Teacher::factory()->create(['user_id' => $teacher->id]);

        TrialLessonRequest::create([
            'name' => 'Марія',
            'phone' => '+380671112233',
            'status' => 'new',
        ]);

        $this
            ->actingAs($teacher)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee('Заявки на безкоштовне заняття')
            ->assertDontSee('Марія')
            ->assertDontSee('+380671112233');
    }

    public function test_admin_can_mark_trial_lesson_request_as_contacted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trialRequest = TrialLessonRequest::create([
            'name' => 'Олена',
            'phone' => '+380991112233',
            'status' => 'new',
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.trial-lesson-requests.mark-contacted', $trialRequest))
            ->assertRedirect(route('admin.index'));

        $trialRequest->refresh();

        $this->assertSame('contacted', $trialRequest->status);
        $this->assertSame($admin->id, $trialRequest->contacted_by);
        $this->assertNotNull($trialRequest->contacted_at);
    }
}
