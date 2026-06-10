<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::query()
            ->with('language')
            ->withCount('lessons')
            ->where('is_published', true)
            ->latest()
            ->paginate(12);

        return view('public.courses.index', compact('courses'));
    }

    public function show(Request $request, Course $course)
    {
        abort_unless($course->is_published || $request->user()?->isAdmin(), 404);

        $course->load(['language', 'lessons']);

        $user = $request->user();
        $hasAccess = $course->isAvailableFor($user);

        // Для кожного уроку — чи є окремий доступ
        $lessonAccess = [];
        if (!$hasAccess && $user) {
            $ownedLessonIds = $user->lessons()
                ->wherePivot('status', 'paid')
                ->pluck('lessons.id')
                ->flip()
                ->all();

            foreach ($course->lessons as $lesson) {
                $lessonAccess[$lesson->id] = isset($ownedLessonIds[$lesson->id]);
            }
        } else {
            foreach ($course->lessons as $lesson) {
                $lessonAccess[$lesson->id] = $hasAccess;
            }
        }

        return view('public.courses.show', compact('course', 'hasAccess', 'lessonAccess'));
    }

    public function lesson(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($course->is_published || $request->user()?->isAdmin(), 404);
        abort_unless($lesson->course_id === $course->id, 404);

        $user = $request->user();

        if (!$lesson->isAvailableFor($user)) {
            if (!$user) {
                return redirect()->route('login');
            }

            // є окрема ціна — пропонуємо купити урок
            if ($lesson->price) {
                return redirect()
                    ->route('courses.show', $course)
                    ->with('error', 'Придбайте цей урок або весь курс щоб отримати доступ.');
            }

            return redirect()
                ->route('courses.show', $course)
                ->with('error', 'Оплатіть курс, щоб відкрити уроки.');
        }

        $lesson->load(['course.lessons', 'tests.options']);

        $lastTestAttempt = null;

        if ($user) {
            $lastTestAttempt = \App\Models\LessonTestAttempt::query()
                ->where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->latest('finished_at')
                ->first();
        }

        return view('public.courses.lesson', compact(
            'course',
            'lesson',
            'lastTestAttempt'
        ));
    }
}
