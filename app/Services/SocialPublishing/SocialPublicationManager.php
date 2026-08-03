<?php

namespace App\Services\SocialPublishing;

use App\Models\SocialPublication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SocialPublicationManager
{
    public function create(array $data, int $userId): SocialPublication
    {
        return DB::transaction(function () use ($data, $userId) {
            [$mediaPath, $mediaType] = $this->storeMedia($data['media_file'] ?? null);

            $publication = SocialPublication::create([
                'created_by' => $userId,
                'title' => $data['title'],
                'caption' => $data['caption'] ?? null,
                'media_path' => $mediaPath,
                'media_type' => $mediaType,
                'status' => 'draft',
            ]);

            $this->replaceTargets($publication, $data['platforms']);

            return $publication->load('targets');
        });
    }

    public function update(SocialPublication $publication, array $data): SocialPublication
    {
        return DB::transaction(function () use ($publication, $data) {
            $mediaPath = $publication->media_path;
            $mediaType = $publication->media_type;
            $newMedia = $data['media_file'] ?? null;

            if ($newMedia instanceof UploadedFile) {
                $this->deleteMedia($mediaPath);
                [$mediaPath, $mediaType] = $this->storeMedia($newMedia);
            } elseif ((bool) ($data['remove_media'] ?? false)) {
                $this->deleteMedia($mediaPath);
                $mediaPath = null;
                $mediaType = null;
            }

            $publication->update([
                'title' => $data['title'],
                'caption' => $data['caption'] ?? null,
                'media_path' => $mediaPath,
                'media_type' => $mediaType,
                'status' => 'draft',
            ]);

            $this->replaceTargets($publication, $data['platforms']);

            return $publication->load('targets');
        });
    }

    public function simulatePublication(SocialPublication $publication): void
    {
        if (config('social-publishing.live_enabled')) {
            throw new RuntimeException('Live publishing adapters have not been enabled yet.');
        }

        DB::transaction(function () use ($publication) {
            $attemptedAt = now();

            foreach ($publication->targets as $target) {
                $target->update([
                    'status' => 'simulated',
                    'provider_post_id' => null,
                    'error_message' => null,
                    'provider_response' => [
                        'mode' => 'dry-run',
                        'message' => 'No request was sent to the provider.',
                    ],
                    'attempted_at' => $attemptedAt,
                ]);
            }

            $publication->update([
                'status' => 'simulated',
                'last_published_at' => $attemptedAt,
            ]);
        });
    }

    public function delete(SocialPublication $publication): void
    {
        DB::transaction(function () use ($publication) {
            $mediaPath = $publication->media_path;
            $publication->delete();
            $this->deleteMedia($mediaPath);
        });
    }

    private function replaceTargets(SocialPublication $publication, array $platforms): void
    {
        $publication->targets()->delete();

        foreach (array_values(array_unique($platforms)) as $platform) {
            $publication->targets()->create([
                'platform' => $platform,
                'status' => 'pending',
            ]);
        }
    }

    private function storeMedia(?UploadedFile $file): array
    {
        if (! $file) {
            return [null, null];
        }

        $path = $file->store('social-publications', 'public');
        $type = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';

        return [$path, $type];
    }

    private function deleteMedia(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
