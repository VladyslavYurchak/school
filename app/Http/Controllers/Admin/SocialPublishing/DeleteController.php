<?php

namespace App\Http\Controllers\Admin\SocialPublishing;

use App\Http\Controllers\Controller;
use App\Models\SocialPublication;
use App\Services\SocialPublishing\SocialPublicationManager;
use Illuminate\Http\RedirectResponse;

class DeleteController extends Controller
{
    public function __invoke(
        SocialPublication $publication,
        SocialPublicationManager $manager,
    ): RedirectResponse {
        $manager->delete($publication);

        return redirect()
            ->route('admin.social-publishing.index')
            ->with('success', 'Чернетку та її тестовий журнал видалено.');
    }
}
