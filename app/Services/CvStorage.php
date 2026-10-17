<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * All CV file handling lives here. Files are written to a private disk under a
 * random name and can only be read back through download(), which callers
 * must authorise first (see CvPolicy).
 */
class CvStorage
{
    /** Validation rules for an uploaded CV file. */
    public static function rules(): array
    {
        $extensions = implode(',', config('talenthub.cv.extensions'));

        return ['required', 'file', 'mimes:'.$extensions, 'max:'.config('talenthub.cv.max_kb')];
    }

    public function disk(): Filesystem
    {
        return Storage::disk(config('talenthub.cv.disk'));
    }

    public function store(User $user, UploadedFile $file): Cv
    {
        $max = (int) config('talenthub.cv.max_per_candidate');

        if ($user->cvs()->count() >= $max) {
            throw ValidationException::withMessages([
                'cv' => "You can keep up to {$max} CVs. Delete one before uploading another.",
            ]);
        }

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'pdf');
        $path = $this->disk()->putFileAs('u'.$user->id, $file, Str::uuid().'.'.$extension);

        $displayName = preg_replace('/[^\w.\- ]+/u', '_', basename($file->getClientOriginalName()));

        return $user->cvs()->create([
            'original_name' => Str::limit($displayName, 180, ''),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_primary' => ! $user->cvs()->exists(),
        ]);
    }

    public function makePrimary(Cv $cv): void
    {
        DB::transaction(function () use ($cv) {
            Cv::where('user_id', $cv->user_id)->update(['is_primary' => false]);
            $cv->update(['is_primary' => true]);
        });
    }

    public function delete(Cv $cv): void
    {
        DB::transaction(function () use ($cv) {
            $wasPrimary = $cv->is_primary;
            $userId = $cv->user_id;

            if ($this->disk()->exists($cv->path)) {
                $this->disk()->delete($cv->path);
            }
            $cv->delete();

            if ($wasPrimary) {
                Cv::where('user_id', $userId)->latest()->first()?->update(['is_primary' => true]);
            }
        });
    }

    public function download(Cv $cv): StreamedResponse
    {
        abort_unless($this->disk()->exists($cv->path), 404, 'This file is no longer available.');

        return $this->disk()->download($cv->path, $cv->original_name);
    }
}
