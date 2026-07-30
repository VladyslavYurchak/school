<?php

namespace Tests\Feature;

use Database\Seeders\PreparedPostsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreparedPostsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_requested_posts_and_events_and_is_idempotent(): void
    {
        $this->seed(PreparedPostsSeeder::class);
        $this->seed(PreparedPostsSeeder::class);

        $this->assertDatabaseCount('posts', 5);
        $this->assertDatabaseCount('events', 4);
        $this->assertDatabaseMissing('posts', ['is_published' => true]);
        $this->assertDatabaseMissing('posts', ['image' => null]);
        $this->assertDatabaseHas('posts', [
            'title' => 'Відкриття Корпорації Мов',
            'image' => '',
            'is_published' => false,
        ]);
        $this->assertDatabaseHas('posts', [
            'title' => 'НМТ & ЄВІ',
            'is_published' => false,
        ]);
        $this->assertDatabaseHas('events', [
            'start_date' => '2026-08-01 00:00:00',
            'is_published' => true,
        ]);

        $this->assertSame(
            '2024-04-21',
            \App\Models\Post::where('title', 'Відкриття Корпорації Мов')
                ->firstOrFail()
                ->created_at
                ->toDateString()
        );
    }
}
