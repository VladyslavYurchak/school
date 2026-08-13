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
            ->assertSee('Вийти з кабінету викладача')
            ->assertDontSee('Вийти з кабінету адміністратора')
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
            ->assertSee('function openGroupModal({ lessonId, lessonDate, lessonTime, groupId, members })', false)
            ->assertSee('document.getElementById(\'eventTime\').value = \'09:00\';', false)
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

    public function test_teacher_calendar_includes_yearly_repeat_and_future_cancellation_controls(): void
    {
        [$teacherUser] = $this->createTeacherUser();

        $this
            ->actingAs($teacherUser)
            ->get(route('admin.calendar.index'))
            ->assertOk()
            ->assertSee('id="repeatPeriod"', false)
            ->assertSee('value="year"', false)
            ->assertSee('Обрати цей час щотижня на 12 місяців')
            ->assertSee('id="cancelStudentFutureLessons"', false)
            ->assertSee("{ scope: 'student_future' }", false)
            ->assertSee('всі наступні індивідуальні заняття цього учня');
    }

    public function test_teacher_calendar_includes_group_attendance_controls(): void
    {
        [$teacherUser] = $this->createTeacherUser();

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.calendar.index'));

        $response
            ->assertOk()
            ->assertSee('id="groupMembersModal"', false)
            ->assertSee('id="markCompletedBtn"', false)
            ->assertSee('id="attendanceFormList"', false)
            ->assertSee('id="attendanceCheckboxes"', false)
            ->assertSee('document.getElementById(\'markCompletedBtn\').addEventListener(\'click\', showAttendanceForm)', false)
            ->assertSee('document.getElementById(\'attendanceFormList\').addEventListener(\'submit\', saveGroupAttendance)', false)
            ->assertSee('postJson(\'/admin/calendar/group-attendance\'', false)
            ->assertSee('present_students: selectedStudentIds()', false)
            ->assertSee('function refreshCalendar()', false)
            ->assertSee('window.calendar.refetchEvents()', false);
    }

    public function test_teacher_calendar_actions_are_phone_friendly(): void
    {
        [$teacherUser] = $this->createTeacherUser();

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.calendar.index'));

        $response
            ->assertOk()
            ->assertSee('modal-dialog-scrollable modal-fullscreen-sm-down', false)
            ->assertSee('modal-footer calendar-action-grid', false)
            ->assertSee('calendar-attendance-submit', false)
            ->assertSee('type="button" class="btn btn-success calendar-action-primary" id="markAsCompleted"', false)
            ->assertSee('type="button" id="markCompletedBtn" class="btn btn-success calendar-action-primary"', false);
    }

    public function test_teacher_calendar_has_mobile_list_view_and_quick_actions(): void
    {
        [$teacherUser] = $this->createTeacherUser();

        $this
            ->actingAs($teacherUser)
            ->get(route('admin.calendar.index'))
            ->assertOk()
            ->assertSee('id="openAddLessonButton"', false)
            ->assertSee(route('teacher.settings.edit'), false)
            ->assertSee("compactCalendar ? 'listWeek' : 'dayGridMonth'", false)
            ->assertSee("document.getElementById('openAddLessonButton')?.addEventListener", false);
    }

    public function test_teacher_calendar_includes_group_reschedule_controls(): void
    {
        [$teacherUser] = $this->createTeacherUser();

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.calendar.index'));

        $response
            ->assertOk()
            ->assertSee('id="groupRescheduleModal"', false)
            ->assertSee('id="groupRescheduleForm"', false)
            ->assertSee('id="groupRescheduleLessonId"', false)
            ->assertSee('id="groupNewDate"', false)
            ->assertSee('id="groupNewTime"', false)
            ->assertSee('document.getElementById(\'markGroupRescheduledBtn\').addEventListener(\'click\', openGroupRescheduleModal)', false)
            ->assertSee('document.getElementById(\'groupRescheduleForm\').addEventListener(\'submit\', saveGroupReschedule)', false)
            ->assertSee('`/admin/calendar/group-lessons/${lessonId}/reschedule`', false)
            ->assertSee('new_date: valueOf(\'groupNewDate\')', false)
            ->assertSee('new_time: valueOf(\'groupNewTime\')', false);
    }

    public function test_teacher_calendar_includes_group_cancel_controls(): void
    {
        [$teacherUser] = $this->createTeacherUser();

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.calendar.index'));

        $response
            ->assertOk()
            ->assertSee('id="markCancelledBtn"', false)
            ->assertSee('document.getElementById(\'markCancelledBtn\').addEventListener(\'click\', cancelGroupLesson)', false)
            ->assertSee('confirm(', false)
            ->assertSee('`/admin/calendar/group-lessons/${lessonId}/cancel`', false)
            ->assertSee('X-Requested-With', false)
            ->assertSee('lesson_id: lessonId', false)
            ->assertSee('group_id: currentGroupId()', false)
            ->assertSee('window.calendar.refetchEvents()', false);
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
