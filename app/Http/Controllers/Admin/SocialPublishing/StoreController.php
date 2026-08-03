<?php

namespace App\Http\Controllers\Admin\SocialPublishing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SocialPublishing\SavePublicationRequest;
use App\Services\SocialPublishing\SocialPublicationManager;
use Illuminate\Http\RedirectResponse;

class StoreController extends Controller
{
    public function __invoke(
        SavePublicationRequest $request,
        SocialPublicationManager $manager,
    ): RedirectResponse {
        $publication = $manager->create($request->validated(), $request->user()->id);

        return redirect()
            ->route('admin.social-publishing.edit', $publication)
            ->with('success', 'Чернетку збережено. Жодної публікації в соцмережах не створено.');
    }
}
