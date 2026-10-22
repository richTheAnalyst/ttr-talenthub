<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * All CV file handling lives here. Files are written under a random name and
 * can only be read back through download(), which callers must authorise
 * first (see CvPolicy).
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
        $path = $this->put('u'.$user->id, $file, Str::uuid().'.'.$extension);

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

            $this->remove($cv->path);
            $cv->delete();

            if ($wasPrimary) {
                Cv::where('user_id', $userId)->latest()->first()?->update(['is_primary' => true]);
            }
        });
    }

    public function download(Cv $cv): StreamedResponse
    {
        if ($this->usesCloudinary()) {
            $response = Http::timeout(30)->get($this->cloudinarySignedUrl($cv->path));

            if (! $response->successful()) {
                Log::error('Cloudinary CV fetch failed', ['status' => $response->status(), 'path' => $cv->path]);
                abort(404, 'This file is no longer available.');
            }

            $bytes = $response->body();

            return response()->streamDownload(function () use ($bytes) {
                echo $bytes;
            }, $cv->original_name, ['Content-Type' => $cv->mime ?: 'application/octet-stream']);
        }

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

    private function usesCloudinary(): bool
    {
        return config('talenthub.cv.disk') === 'cloudinary';
    }

    /** CV_DISK=database keeps file contents in the cv_files table instead of a filesystem disk. */
    private function usesDatabase(): bool
    {
        return config('talenthub.cv.disk') === 'database';
    }

    /** Save the uploaded file and return its path. */
    private function put(string $directory, UploadedFile $file, string $name): string
    {
        if ($this->usesCloudinary()) {
            $path = $directory.'/'.$name;
            $this->cloudinaryUpload($path, $file, $name);

            return $path;
        }

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
        if ($this->usesCloudinary()) {
            $this->cloudinaryDestroy($path);

            return;
        }

        if ($this->usesDatabase()) {
            DB::table('cv_files')->where('path', $path)->delete();

            return;
        }

        if ($this->disk()->exists($path)) {
            $this->disk()->delete($path);
        }
    }

    // ---- Cloudinary (CV_DISK=cloudinary) -------------------------------------------------
    // Files are uploaded as type "authenticated" raw assets: no public URL works without a
    // signature, and only this server holds the secret. Downloads are fetched server-side
    // and streamed to the user after the normal CvPolicy check, so links are never exposed.

    /** @return array{0:string,1:string,2:string} cloud name, api key, api secret */
    private function cloudinaryCredentials(): array
    {
        $c = config('talenthub.cloudinary');

        return [(string) $c['cloud_name'], (string) $c['api_key'], (string) $c['api_secret']];
    }

    /** Cloudinary API signature: sorted "k=v" pairs joined by &, plus the secret, SHA-1 hex. */
    private function cloudinarySign(array $params, string $secret): string
    {
        ksort($params);

        return sha1(urldecode(http_build_query($params)).$secret);
    }

    private function cloudinaryUpload(string $path, UploadedFile $file, string $name): void
    {
        [$cloud, $key, $secret] = $this->cloudinaryCredentials();

        $params = ['public_id' => $path, 'timestamp' => time(), 'type' => 'authenticated'];

        $response = Http::timeout(60)
            ->attach('file', (string) file_get_contents($file->getRealPath()), $name)
            ->post("https://api.cloudinary.com/v1_1/{$cloud}/raw/upload", $params + [
                'api_key' => $key,
                'signature' => $this->cloudinarySign($params, $secret),
            ]);

        if (! $response->successful()) {
            Log::error('Cloudinary CV upload failed', ['status' => $response->status(), 'body' => $response->body()]);

            throw ValidationException::withMessages([
                'cv' => 'We could not save your CV right now. Please try again in a moment.',
            ]);
        }
    }

    private function cloudinaryDestroy(string $path): void
    {
        [$cloud, $key, $secret] = $this->cloudinaryCredentials();

        $params = ['public_id' => $path, 'timestamp' => time(), 'type' => 'authenticated'];

        $response = Http::timeout(30)->asForm()->post("https://api.cloudinary.com/v1_1/{$cloud}/raw/destroy", $params + [
            'api_key' => $key,
            'signature' => $this->cloudinarySign($params, $secret),
        ]);

        if (! $response->successful()) {
            Log::warning('Cloudinary CV delete failed', ['status' => $response->status(), 'path' => $path]);
        }
    }

    /** Signed delivery URL for an authenticated raw asset (first 8 chars of base64url(SHA-1)). */
    private function cloudinarySignedUrl(string $path): string
    {
        [$cloud, , $secret] = $this->cloudinaryCredentials();

        $signature = substr(strtr(base64_encode(sha1($path.$secret, true)), '+/', '-_'), 0, 8);

        return "https://res.cloudinary.com/{$cloud}/raw/authenticated/s--{$signature}--/{$path}";
    }
}