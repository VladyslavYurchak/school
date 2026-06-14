<?php

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Models\Group;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/dev/login-teacher', function () {
    abort_unless(app()->environment('local'), 404);

    $teacherUser = User::updateOrCreate(
        ['email' => 'dev.teacher@school.test'],
        [
            'name' => 'Dev Teacher',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'email_verified_at' => now(),
        ]
    );

    $teacher = Teacher::updateOrCreate(
        ['user_id' => $teacherUser->id],
        [
            'first_name' => 'Dev',
            'last_name' => 'Teacher',
            'email' => 'dev.teacher@school.test',
            'phone' => '+380000000001',
            'lesson_price' => 700,
            'group_lesson_price' => 900,
            'pair_lesson_price' => 800,
            'trial_lesson_price' => 300,
            'is_active' => true,
        ]
    );

    $individualTemplate = devSubscriptionTemplate('Dev Individual', LessonType::Individual->value, 2500);
    $groupTemplate = devSubscriptionTemplate('Dev Group', LessonType::Group->value, 1800);
    $pairTemplate = devSubscriptionTemplate('Dev Pair', LessonType::Pair->value, 2200);

    $group = Group::updateOrCreate(
        ['name' => 'Dev Group A', 'teacher_id' => $teacher->id],
        ['type' => LessonType::Group->value, 'notes' => 'Dev calendar group']
    );

    $pair = Group::updateOrCreate(
        ['name' => 'Dev Pair A', 'teacher_id' => $teacher->id],
        ['type' => LessonType::Pair->value, 'notes' => 'Dev calendar pair']
    );

    $individualStudent = devStudent($teacher, 'Dev', 'Individual', 'dev.individual@school.test', null, $individualTemplate);
    devStudent($teacher, 'Dev', 'Group One', 'dev.group.one@school.test', $group, $groupTemplate);
    devStudent($teacher, 'Dev', 'Group Two', 'dev.group.two@school.test', $group, $groupTemplate);
    devStudent($teacher, 'Dev', 'Pair One', 'dev.pair.one@school.test', $pair, $pairTemplate);
    devStudent($teacher, 'Dev', 'Pair Two', 'dev.pair.two@school.test', $pair, $pairTemplate);

    $baseDate = Carbon::today()->next(Carbon::MONDAY);

    devLesson(
        title: 'Dev Individual Lesson',
        teacher: $teacher,
        start: $baseDate->copy()->setTime(10, 0),
        duration: 60,
        type: LessonType::Individual,
        student: $individualStudent
    );

    devLesson(
        title: 'Dev Group Lesson',
        teacher: $teacher,
        start: $baseDate->copy()->setTime(12, 0),
        duration: 60,
        type: LessonType::Group,
        group: $group
    );

    devLesson(
        title: 'Dev Pair Lesson',
        teacher: $teacher,
        start: $baseDate->copy()->setTime(14, 0),
        duration: 60,
        type: LessonType::Pair,
        group: $pair
    );

    devLesson(
        title: 'Dev Trial Lesson',
        teacher: $teacher,
        start: $baseDate->copy()->setTime(16, 0),
        duration: 45,
        type: LessonType::Trial
    );

    Auth::login($teacherUser);

    return redirect()->route('admin.calendar.index');
})->name('dev.login-teacher');

function devSubscriptionTemplate(string $title, string $type, int $price): SubscriptionTemplate
{
    return SubscriptionTemplate::updateOrCreate(
        ['title' => $title],
        [
            'type' => $type,
            'lessons_per_week' => 2,
            'price' => $price,
            'is_active' => true,
        ]
    );
}

function devStudent(
    Teacher $teacher,
    string $firstName,
    string $lastName,
    string $email,
    ?Group $group,
    SubscriptionTemplate $template
): Student {
    return Student::updateOrCreate(
        ['email' => $email],
        [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => '+380000000002',
            'teacher_id' => $teacher->id,
            'group_id' => $group?->id,
            'subscription_id' => $template->id,
            'remaining_lessons' => 12,
            'remaining_group_lessons' => 12,
            'is_active' => true,
            'start_date' => now()->toDateString(),
        ]
    );
}

function devLesson(
    string $title,
    Teacher $teacher,
    Carbon $start,
    int $duration,
    LessonType $type,
    ?Student $student = null,
    ?Group $group = null
): PlannedLesson {
    return PlannedLesson::updateOrCreate(
        [
            'title' => $title,
            'teacher_id' => $teacher->id,
        ],
        [
            'student_id' => $student?->id,
            'group_id' => $group?->id,
            'start_date' => $start,
            'end_date' => $start->copy()->addMinutes($duration),
            'status' => LessonStatus::Planned,
            'lesson_type' => $type,
            'notes' => 'Created by /dev/login-teacher',
        ]
    );
}
