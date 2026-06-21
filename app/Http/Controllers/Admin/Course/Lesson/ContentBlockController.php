<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Course\LessonContentBlockRequest;
use App\Models\Lesson;
use App\Models\LessonContentBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentBlockController extends Controller
{
    public function index(Lesson $lesson): View
    {
        $blocks = $lesson->contentBlocks()->get();

        return view('admin.course.lesson.blocks.index', compact('lesson', 'blocks'));
    }

    public function create(Request $request, Lesson $lesson): View
    {
        $type = in_array($request->query('type'), LessonContentBlock::TYPES, true)
            ? $request->query('type')
            : LessonContentBlock::TYPE_TEXT;

        return view('admin.course.lesson.blocks.create', compact('lesson', 'type'));
    }

    public function store(LessonContentBlockRequest $request, Lesson $lesson): RedirectResponse
    {
        $data = $this->prepareData($request);
        $data['position'] = ((int) $lesson->contentBlocks()->max('position')) + 1;

        $lesson->contentBlocks()->create($data);

        return redirect()
            ->route('admin.course.lesson.blocks.index', $lesson)
            ->with('success', 'Блок додано.');
    }

    public function edit(Lesson $lesson, LessonContentBlock $block): View
    {
        $this->ensureBlockBelongsToLesson($lesson, $block);

        return view('admin.course.lesson.blocks.edit', compact('lesson', 'block'));
    }

    public function update(
        LessonContentBlockRequest $request,
        Lesson $lesson,
        LessonContentBlock $block
    ): RedirectResponse {
        $this->ensureBlockBelongsToLesson($lesson, $block);

        $data = $this->prepareData($request, $block);
        $block->update($data);

        return redirect()
            ->route('admin.course.lesson.blocks.index', $lesson)
            ->with('success', 'Блок оновлено.');
    }

    public function destroy(Lesson $lesson, LessonContentBlock $block): RedirectResponse
    {
        $this->ensureBlockBelongsToLesson($lesson, $block);
        $this->deleteMedia($block);
        $block->delete();
        $this->normalizePositions($lesson);

        return redirect()
            ->route('admin.course.lesson.blocks.index', $lesson)
            ->with('success', 'Блок видалено.');
    }

    public function toggle(Lesson $lesson, LessonContentBlock $block): RedirectResponse
    {
        $this->ensureBlockBelongsToLesson($lesson, $block);
        $block->update(['is_active' => !$block->is_active]);

        return redirect()
            ->route('admin.course.lesson.blocks.index', $lesson)
            ->with('success', $block->is_active ? 'Блок показано.' : 'Блок приховано.');
    }

    public function updateOrder(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'blocks' => ['required', 'array'],
            'blocks.*' => [
                'required',
                'integer',
                Rule::exists('lesson_content_blocks', 'id')->where('lesson_id', $lesson->id),
            ],
        ]);

        foreach ($data['blocks'] as $index => $blockId) {
            LessonContentBlock::query()
                ->where('lesson_id', $lesson->id)
                ->whereKey($blockId)
                ->update(['position' => $index + 1]);
        }

        return response()->json(['saved' => true]);
    }

    private function prepareData(
        LessonContentBlockRequest $request,
        ?LessonContentBlock $block = null
    ): array {
        $data = $request->validated();
        $data['content'] = $this->cleanRichText($data['content'] ?? null);
        $data['video_url'] = $data['type'] === LessonContentBlock::TYPE_VIDEO
            ? $this->normalizeYoutubeEmbedUrl($data['video_url'])
            : null;

        unset($data['media_file']);

        if ($request->hasFile('media_file')) {
            if ($block) {
                $this->deleteMedia($block);
            }

            $file = $request->file('media_file');
            $data['media_path'] = $file->store('lesson_blocks/' . $data['type'], 'public');
            $data['media_name'] = $file->getClientOriginalName();
            $data['media_mime'] = $file->getMimeType();
            $data['media_size'] = $file->getSize();
        }

        return $data;
    }

    private function cleanRichText(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $withoutScripts = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html);
        $clean = strip_tags(
            $withoutScripts,
            '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><h2><h3><h4><a>'
        );

        return preg_replace_callback(
            '/<a\b[^>]*href=("|\')(.*?)\1[^>]*>/i',
            static function (array $matches): string {
                $url = filter_var($matches[2], FILTER_VALIDATE_URL);

                return $url ? '<a href="' . e($url) . '" target="_blank" rel="noopener">' : '<a>';
            },
            preg_replace('/<(?!a\b)([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $clean)
        );
    }

    private function normalizeYoutubeEmbedUrl(string $url): string
    {
        if (preg_match('~youtu\.be/([^?&/]+)~', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        if (preg_match('~youtube\.com/(?:watch\?v=|shorts/|embed/)([^?&/]+)~', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        return $url;
    }

    private function ensureBlockBelongsToLesson(Lesson $lesson, LessonContentBlock $block): void
    {
        abort_unless($block->lesson_id === $lesson->id, 404);
    }

    private function deleteMedia(LessonContentBlock $block): void
    {
        if ($block->media_path) {
            Storage::disk('public')->delete($block->media_path);
        }
    }

    private function normalizePositions(Lesson $lesson): void
    {
        $lesson->contentBlocks()->get()->each(function (LessonContentBlock $block, int $index): void {
            $block->update(['position' => $index + 1]);
        });
    }
}
