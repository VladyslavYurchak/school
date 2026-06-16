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
            ->withCount([
                'lessons' => fn ($query) => $query->where('is_published', true),
            ])
            ->where('is_published', true)
            ->latest()
            ->paginate(12);

        return view('public.courses.index', compact('courses'));
    }

    public function show(Request $request, Course $course)
    {
        $user = $request->user();
        $isAdmin = (bool) $user?->isAdmin();

        abort_unless($course->is_published || $isAdmin, 404);

        $course->load('language');
        $course->setRelation('lessons', $this->visibleLessons($course, $isAdmin));

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
        $user = $request->user();
        $isAdmin = (bool) $user?->isAdmin();

        abort_unless($course->is_published || $isAdmin, 404);
        abort_unless($lesson->course_id === $course->id, 404);
        abort_unless($lesson->is_published || $isAdmin, 404);

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

        $course->setRelation('lessons', $this->visibleLessons($course, $isAdmin));
        $lesson->load('tests.options');

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

    private function visibleLessons(Course $course, bool $includeDrafts)
    {
        return $course->lessons()
            ->when(!$includeDrafts, fn ($query) => $query->where('is_published', true))
            ->get();
    }
}
