<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Course\VocabularyItemRequest;
use App\Models\Lesson;
use App\Models\LessonVocabularyItem;
use App\Models\VocabularyItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VocabularyController extends Controller
{
    public function index(Request $request, Lesson $lesson): View
    {
        $lesson->load(['course.language']);
        $links = $lesson->vocabularyLinks()->with('vocabularyItem')->get();
        $query = trim((string) $request->query('q'));
        $searchResults = collect();

        if ($query !== '') {
            $searchResults = VocabularyItem::query()
                ->where('language_id', $lesson->course->language_id)
                ->where(function ($builder) use ($query): void {
                    $builder
                        ->where('term', 'like', '%' . $query . '%')
                        ->orWhere('translation', 'like', '%' . $query . '%');
                })
                ->whereDoesntHave('lessons', fn ($builder) => $builder->whereKey($lesson->id))
                ->orderBy('term')
                ->limit(30)
                ->get();
        }

        return view('admin.course.lesson.vocabulary.index', compact(
            'lesson',
            'links',
            'query',
            'searchResults'
        ));
    }

    public function create(Lesson $lesson): View
    {
        $lesson->load('course.language');

        return view('admin.course.lesson.vocabulary.create', compact('lesson'));
    }

    public function store(VocabularyItemRequest $request, Lesson $lesson): RedirectResponse
    {
        $lesson->loadMissing('course');
        $data = $request->validated();

        DB::transaction(function () use ($lesson, $data): void {
            $item = VocabularyItem::query()
                ->where('language_id', $lesson->course->language_id)
                ->whereRaw('LOWER(term) = ?', [mb_strtolower($data['term'])])
                ->whereRaw('LOWER(translation) = ?', [mb_strtolower($data['translation'])])
                ->first();

            if (!$item) {
                $item = VocabularyItem::create([
                    'language_id' => $lesson->course->language_id,
                    ...$this->itemData($data),
                ]);
            }

            $this->attachItem($lesson, $item, $data);
        });

        return redirect()
            ->route('admin.course.lesson.vocabulary.index', $lesson)
            ->with('success', 'Слово додано до уроку.');
    }

    public function attach(Request $request, Lesson $lesson, VocabularyItem $vocabularyItem): RedirectResponse
    {
        $lesson->loadMissing('course');
        abort_unless($vocabularyItem->language_id === $lesson->course->language_id, 404);

        $data = $request->validate([
            'is_required' => ['nullable', 'boolean'],
        ]);

        $this->attachItem($lesson, $vocabularyItem, [
            'is_required' => (bool) ($data['is_required'] ?? false),
            'note' => null,
        ]);

        return redirect()
            ->route('admin.course.lesson.vocabulary.index', $lesson)
            ->with('success', 'Слово прикріплено до уроку.');
    }

    public function edit(Lesson $lesson, LessonVocabularyItem $link): View
    {
        $this->ensureLinkBelongsToLesson($lesson, $link);
        $link->load('vocabularyItem');
        $lesson->load('course.language');

        return view('admin.course.lesson.vocabulary.edit', compact('lesson', 'link'));
    }

    public function update(
        VocabularyItemRequest $request,
        Lesson $lesson,
        LessonVocabularyItem $link
    ): RedirectResponse {
        $this->ensureLinkBelongsToLesson($lesson, $link);
        $data = $request->validated();

        DB::transaction(function () use ($link, $data): void {
            $link->vocabularyItem()->update($this->itemData($data));
            $link->update([
                'is_required' => $data['is_required'],
                'note' => $data['note'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.course.lesson.vocabulary.index', $lesson)
            ->with('success', 'Словниковий запис оновлено.');
    }

    public function detach(Lesson $lesson, LessonVocabularyItem $link): RedirectResponse
    {
        $this->ensureLinkBelongsToLesson($lesson, $link);
        $link->delete();
        $this->normalizePositions($lesson);

        return redirect()
            ->route('admin.course.lesson.vocabulary.index', $lesson)
            ->with('success', 'Слово від’єднано від уроку, але залишено у словнику.');
    }

    public function updateOrder(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'links' => ['required', 'array'],
            'links.*' => [
                'required',
                'integer',
                Rule::exists('lesson_vocabulary_items', 'id')->where('lesson_id', $lesson->id),
            ],
        ]);

        $currentLinkIds = $lesson->vocabularyLinks()->pluck('id')->all();
        $submittedLinkIds = array_map('intval', $data['links']);

        sort($currentLinkIds);
        sort($submittedLinkIds);

        if ($currentLinkIds !== $submittedLinkIds) {
            throw ValidationException::withMessages([
                'links' => 'The submitted vocabulary order must include every word from this lesson.',
            ]);
        }

        foreach ($data['links'] as $index => $linkId) {
            LessonVocabularyItem::query()
                ->where('lesson_id', $lesson->id)
                ->whereKey($linkId)
                ->update(['position' => $index + 1]);
        }

        return response()->json(['saved' => true]);
    }

    private function attachItem(Lesson $lesson, VocabularyItem $item, array $data): void
    {
        LessonVocabularyItem::query()->firstOrCreate(
            [
                'lesson_id' => $lesson->id,
                'vocabulary_item_id' => $item->id,
            ],
            [
                'is_required' => (bool) ($data['is_required'] ?? false),
                'note' => $data['note'] ?? null,
                'position' => ((int) $lesson->vocabularyLinks()->max('position')) + 1,
            ]
        );
    }

    private function itemData(array $data): array
    {
        return [
            'term' => $data['term'],
            'translation' => $data['translation'],
            'transcription' => $data['transcription'] ?? null,
            'part_of_speech' => $data['part_of_speech'] ?? null,
            'explanation' => $data['explanation'] ?? null,
            'example' => $data['example'] ?? null,
            'example_translation' => $data['example_translation'] ?? null,
        ];
    }

    private function ensureLinkBelongsToLesson(Lesson $lesson, LessonVocabularyItem $link): void
    {
        abort_unless($link->lesson_id === $lesson->id, 404);
    }

    private function normalizePositions(Lesson $lesson): void
    {
        $lesson->vocabularyLinks()->get()->each(function (LessonVocabularyItem $link, int $index): void {
            $link->update(['position' => $index + 1]);
        });
    }
}
