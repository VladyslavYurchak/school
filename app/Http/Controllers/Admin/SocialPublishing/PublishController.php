<?php

namespace App\Http\Controllers\Admin\SocialPublishing;

use App\Http\Controllers\Controller;
use App\Models\SocialPublication;
use App\Services\SocialPublishing\SocialPublicationManager;
use Illuminate\Http\RedirectResponse;

class PublishController extends Controller
{
    public function __invoke(
        SocialPublication $publication,
        SocialPublicationManager $manager,
    ): RedirectResponse {
        abort_if(config('social-publishing.live_enabled'), 503, 'Live publishing is not configured.');

        $manager->simulatePublication($publication->load('targets'));

        return back()->with(
            'success',
            'Тест завершено: запити в соцмережі не надсилалися. Результати записано в журнал.'
        );
    }
}
