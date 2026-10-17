<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Services\CvStorage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only way to read a CV file. CvPolicy decides who may: the owner, an
 * admin, or an employer whose job the candidate applied to with that CV.
 */
class CvDownloadController extends Controller
{
    public function __invoke(Cv $cv, CvStorage $storage): StreamedResponse
    {
        Gate::authorize('view', $cv);

        return $storage->download($cv);
    }
}
