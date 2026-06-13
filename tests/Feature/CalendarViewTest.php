<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_calendar_renders_only_available_own_groups_with_type_metadata(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        [, $otherTeacher] = $this->createTeacherUser();

        $group = Group::factory()->group()->create([
            'name' => 'A1 Group',
            'teacher_id' => $teacher->id,
        ]);

        $pair = Group::factory()->pair()->create([
            'name' => 'B1 Pair',
            'teacher_id' => $teacher->id,
        ]);

        $emptyGroup = Group::factory()->group()->create([
            'name' => 'Empty Group',
            'teacher_id' => $teacher->id,
        ]);

        $foreignGroup = Group::factory()->group()->create([
            'name' => 'Foreign Group',
            'teacher_id' => $otherTeacher->id,
        ]);

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $pair->id,
        ]);

        Student::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'group_id' => $foreignGroup->id,
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.calendar.index'));

        $response
            ->assertOk()
            ->assertSee('A1 Group (1', false)
            ->assertSee('B1 Pair (1', false)
            ->assertSee('data-type="group"', false)
            ->assertSee('data-type="pair"', false)
            ->assertDontSee($emptyGroup->name, false)
            ->assertDontSee($foreignGroup->name, false);
    }

    public function test_teacher_calendar_includes_lesson_type_sync_script(): void
    {
        [$teacherUser] = $this->createTeacherUser();

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.calendar.index'));

        $response
            ->assertOk()
            ->assertSee('function syncLessonTypeFields()', false)
            ->assertSee('function filterGroupsByType(type)', false)
            ->assertSee('radio.addEventListener(\'change\', syncLessonTypeFields);', false)
            ->assertSee('function openGroupModal({lessonId, lessonDate, lessonTime, groupId, members })', false)
            ->assertDontSee('title: data.title', false)
            ->assertDontSee('function openGroupMembersModal(groupId)', false);
    }

    public function test_teacher_calendar_includes_time_edit_controls(): void
    {
        [$teacherUser] = $this->createTeacherUser();

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.calendar.index'));

        $response
            ->assertOk()
            ->assertSee('id="editEventModal"', false)
            ->assertSee('id="editEventDate"', false)
            ->assertSee('id="editEventTime"', false)
            ->assertSee('id="editEventDuration"', false)
            ->assertSee('editEventForm.addEventListener(\'submit\'', false)
            ->assertSee('method: \'PUT\'', false)
            ->assertSee('`/admin/calendar-events/${selectedEventId}`', false)
            ->assertDontSee('Редагування ще в розробці', false);
    }

    private function createTeacherUser(): array
    {
        $user = User::factory()->create(['role' => 'teacher']);

        $teacher = Teacher::factory()->create([
            'user_id' => $user->id,
        ]);

        return [$user, $teacher];
    }
}
