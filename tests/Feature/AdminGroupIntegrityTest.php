<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Student;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGroupIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pair_rejects_a_third_student_on_the_server(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();
        $template = SubscriptionTemplate::factory()->create(['type' => 'pair']);
        $group = Group::factory()->create([
            'type' => 'pair',
            'teacher_id' => $teacher->id,
        ]);

        Student::factory()->count(2)->create([
            'teacher_id' => $teacher->id,
            'subscription_id' => $template->id,
            'group_id' => $group->id,
        ]);
        $thirdStudent = Student::factory()->create([
            'teacher_id' => $teacher->id,
            'subscription_id' => $template->id,
            'group_id' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.groups.edit', $group))
            ->post(route('admin.groups.add-student', $group), [
                'student_id' => $thirdStudent->id,
            ])
            ->assertRedirect(route('admin.groups.edit', $group))
            ->assertSessionHas('error', 'У парі може бути не більше двох студентів.');

        $this->assertNull($thirdStudent->fresh()->group_id);
    }

    public function test_group_type_cannot_conflict_with_existing_student_subscriptions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();
        $groupTemplate = SubscriptionTemplate::factory()->create(['type' => 'group']);
        $group = Group::factory()->create([
            'name' => 'Existing group',
            'type' => 'group',
            'teacher_id' => $teacher->id,
        ]);

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'subscription_id' => $groupTemplate->id,
            'group_id' => $group->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.groups.edit', $group))
            ->put(route('admin.groups.update', $group), [
                'name' => $group->name,
                'type' => 'pair',
                'teacher_id' => $teacher->id,
                'notes' => null,
            ])
            ->assertRedirect(route('admin.groups.edit', $group))
            ->assertSessionHas('error');

        $this->assertSame('group', $group->fresh()->type);
    }
}
