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
        $path = $file->storeAs('u'.$user->id, Str::uuid().'.'.$extension, config('talenthub.cv.disk'));
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

            $this->disk()->delete($cv->path);
            $cv->delete();

            if ($wasPrimary) {
                Cv::where('user_id', $userId)->latest()->first()?->update(['is_primary' => true]);
            }
        });
    }

       public function download(Cv $cv): StreamedResponse
    {
        if ($this->usesDatabase()) {
            $data = DB::table('cv_files')->where('path', $cv->path)->value('data');
            abort_if($data === null, 404, 'This file is no longer available.');

            $bytes = base64_decode($data, true) ?: '';

            return response()->streamDownload(function () use ($bytes) {
                echo $bytes;
            }, $cv->original_name, ['Content-Type' => $cv->mime ?: 'application/octet-stream']);
        }

        abort_unless($this->disk()->exists($cv->path), 404, 'This file is no longer available.');

        return $this->disk()->download($cv->path, $cv->original_name);
    }

    /** CV_DISK=database keeps file contents in the cv_files table instead of a filesystem disk. */
    private function usesDatabase(): bool
    {
        return config('talenthub.cv.disk') === 'database';
    }

    /** Save the uploaded file and return its path. */
    private function put(string $directory, UploadedFile $file, string $name): string
    {
        if ($this->usesDatabase()) {
            $path = $directory.'/'.$name;
            DB::table('cv_files')->insert([
                'path' => $path,
                'data' => base64_encode((string) file_get_contents($file->getRealPath())),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $path;
        }

        return $this->disk()->putFileAs($directory, $file, $name);
    }

    private function remove(string $path): void
    {
        if ($this->usesDatabase()) {
            DB::table('cv_files')->where('path', $path)->delete();

            return;
        }

        if ($this->disk()->exists($path)) {
            $this->disk()->delete($path);
        }
    }
}
