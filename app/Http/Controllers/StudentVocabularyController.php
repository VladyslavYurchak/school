<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserVocabularyProgress;
use App\Models\VocabularyItem;
use App\Services\Vocabulary\AccessibleVocabularyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentVocabularyController extends Controller
{
    public function learn(Request $request, AccessibleVocabularyService $vocabulary): View
    {
        $user = $this->student($request);
        $courseId = $this->courseId($request);
        $courses = $vocabulary->courses($user);

        $items = $vocabulary->itemsQuery($user, $courseId)
            ->with([
                'language',
                'userProgress' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->whereDoesntHave('userProgress', fn (Builder $progress) => $progress
                ->where('user_id', $user->id)
                ->where('status', UserVocabularyProgress::STATUS_KNOWN))
            ->orderBy('term')
            ->paginate(12)
            ->withQueryString();

        return view('student.vocabulary.index', [
            'mode' => 'learn',
            'items' => $items,
            'courses' => $courses,
            'selectedCourseId' => $courseId,
            'stats' => $this->stats($user, $vocabulary),
        ]);
    }

    public function updateProgress(
        Request $request,
        VocabularyItem $vocabularyItem,
        AccessibleVocabularyService $vocabulary
    ): RedirectResponse {
        $user = $this->student($request);
        abort_unless($vocabulary->userCanAccess($user, $vocabularyItem), 404);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                UserVocabularyProgress::STATUS_LEARNING,
                UserVocabularyProgress::STATUS_KNOWN,
            ])],
            'course' => ['nullable', 'integer', 'min:1'],
        ]);

        $progress = UserVocabularyProgress::firstOrCreate([
            'user_id' => $user->id,
            'vocabulary_item_id' => $vocabularyItem->id,
        ]);

        if ($data['status'] === UserVocabularyProgress::STATUS_KNOWN) {
            $progress->markKnown();
        } else {
            $progress->markLearning();
        }

        return redirect()
            ->route('student.vocabulary.learn', array_filter([
                'course' => $data['course'] ?? null,
            ]))
            ->with(
                'vocabulary_success',
                $data['status'] === UserVocabularyProgress::STATUS_KNOWN
                    ? 'Слово додано до повторення.'
                    : 'Слово залишилось у списку для вивчення.'
            );
    }

    public function review(Request $request, AccessibleVocabularyService $vocabulary): View
    {
        $user = $this->student($request);
        $courseId = $this->courseId($request);
        $courses = $vocabulary->courses($user);

        if ($request->boolean('restart')) {
            $request->session()->forget('vocabulary.review.seen');
        }

        $seenIds = collect($request->session()->get('vocabulary.review.seen', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $question = $vocabulary->itemsQuery($user, $courseId)
            ->with([
                'language',
                'userProgress' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->whereHas('userProgress', fn (Builder $progress) => $progress
                ->where('user_id', $user->id)
                ->where('status', UserVocabularyProgress::STATUS_KNOWN))
            ->when($seenIds->isNotEmpty(), fn (Builder $query) => $query->whereNotIn('vocabulary_items.id', $seenIds))
            ->orderByRaw(
                'CASE WHEN EXISTS (
                    SELECT 1 FROM user_vocabulary_progress uvp
                    WHERE uvp.vocabulary_item_id = vocabulary_items.id
                      AND uvp.user_id = ?
                      AND (uvp.next_review_at IS NULL OR uvp.next_review_at <= ?)
                ) THEN 0 ELSE 1 END',
                [$user->id, now()]
            )
            ->orderBy('term')
            ->first();

        $options = collect();

        if ($question) {
            $distractors = $vocabulary->itemsQuery($user, $courseId)
                ->whereKeyNot($question->id)
                ->where('translation', '!=', $question->translation)
                ->inRandomOrder()
                ->limit(12)
                ->get()
                ->unique('translation')
                ->take(3);

            $options = $distractors
                ->push($question)
                ->shuffle()
                ->values();

            $request->session()->put(
                "vocabulary.review.options.{$question->id}",
                $options->pluck('id')->all()
            );
        }

        return view('student.vocabulary.index', [
            'mode' => 'review',
            'question' => $question,
            'options' => $options,
            'courses' => $courses,
            'selectedCourseId' => $courseId,
            'stats' => $this->stats($user, $vocabulary),
            'reviewedCount' => $seenIds->count(),
        ]);
    }

    public function submitReview(
        Request $request,
        VocabularyItem $vocabularyItem,
        AccessibleVocabularyService $vocabulary
    ): RedirectResponse {
        $user = $this->student($request);
        abort_unless($vocabulary->userCanAccess($user, $vocabularyItem), 404);

        $allowedOptionIds = collect(
            $request->session()->pull("vocabulary.review.options.{$vocabularyItem->id}", [])
        )->map(fn ($id) => (int) $id);

        $data = $request->validate([
            'selected_id' => ['required', 'integer', Rule::in($allowedOptionIds->all())],
            'course' => ['nullable', 'integer', 'min:1'],
        ]);

        $progress = UserVocabularyProgress::query()
            ->where('user_id', $user->id)
            ->where('vocabulary_item_id', $vocabularyItem->id)
            ->where('status', UserVocabularyProgress::STATUS_KNOWN)
            ->firstOrFail();

        $selected = $vocabulary->itemsQuery($user)
            ->whereKey($data['selected_id'])
            ->firstOrFail();

        $isCorrect = $selected->is($vocabularyItem);
        $progress->recordReview($isCorrect);

        $seenIds = collect($request->session()->get('vocabulary.review.seen', []))
            ->push($vocabularyItem->id)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $request->session()->put('vocabulary.review.seen', $seenIds);

        return redirect()
            ->route('student.vocabulary.review', array_filter([
                'course' => $data['course'] ?? null,
            ]))
            ->with('vocabulary_review_result', [
                'correct' => $isCorrect,
                'term' => $vocabularyItem->term,
                'selected' => $selected->translation,
                'translation' => $vocabularyItem->translation,
                'example' => $vocabularyItem->example,
            ]);
    }

    private function student(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->isStudent(), 403);

        return $user;
    }

    private function courseId(Request $request): ?int
    {
        $data = $request->validate([
            'course' => ['nullable', 'integer', 'min:1'],
        ]);

        return isset($data['course']) ? (int) $data['course'] : null;
    }

    private function stats(User $user, AccessibleVocabularyService $vocabulary): array
    {
        $allItems = $vocabulary->itemsQuery($user);
        $total = (clone $allItems)->count();
        $known = (clone $allItems)
            ->whereHas('userProgress', fn (Builder $progress) => $progress
                ->where('user_id', $user->id)
                ->where('status', UserVocabularyProgress::STATUS_KNOWN))
            ->count();
        $learning = (clone $allItems)
            ->whereHas('userProgress', fn (Builder $progress) => $progress
                ->where('user_id', $user->id)
                ->where('status', UserVocabularyProgress::STATUS_LEARNING))
            ->count();

        return [
            'total' => $total,
            'new' => max(0, $total - $known - $learning),
            'learning' => $learning,
            'known' => $known,
        ];
    }
}
