<?php

namespace App\Http\Controllers\Admin\SocialPublishing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SocialPublishing\SavePublicationRequest;
use App\Models\SocialPublication;
use App\Services\SocialPublishing\SocialPublicationManager;
use Illuminate\Http\RedirectResponse;

class UpdateController extends Controller
{
    public function __invoke(
        SavePublicationRequest $request,
        SocialPublication $publication,
        SocialPublicationManager $manager,
    ): RedirectResponse {
        $manager->update($publication, $request->validated());

        return redirect()
            ->route('admin.social-publishing.edit', $publication)
            ->with('success', 'Чернетку оновлено.');
    }
}
