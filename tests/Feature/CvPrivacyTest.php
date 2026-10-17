<?php

namespace Tests\Feature;

use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CvPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_candidate_can_upload_a_pdf_and_it_becomes_primary(): void
    {
        Storage::fake('cvs');
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->post(route('candidate.cvs.store'), ['cv' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf')])
            ->assertSessionHasNoErrors();

        $cv = $candidate->cvs()->firstOrFail();
        $this->assertTrue($cv->is_primary);
        Storage::disk('cvs')->assertExists($cv->path);
        $this->assertGreaterThanOrEqual(20, $candidate->candidateProfile->fresh()->completeness);
    }

    public function test_dangerous_or_oversized_files_are_rejected(): void
    {
        Storage::fake('cvs');
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)->post(route('candidate.cvs.store'), ['cv' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')])->assertSessionHasErrors('cv');
        $this->actingAs($candidate)->post(route('candidate.cvs.store'), ['cv' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')])->assertSessionHasErrors('cv');

        $this->assertSame(0, $candidate->cvs()->count());
    }

    public function test_the_number_of_cvs_is_limited(): void
    {
        Storage::fake('cvs');
        $candidate = $this->makeCandidate();

        for ($i = 0; $i < config('talenthub.cv.max_per_candidate'); $i++) {
            $this->actingAs($candidate)->post(route('candidate.cvs.store'), ['cv' => UploadedFile::fake()->create("cv{$i}.pdf", 10, 'application/pdf')]);
        }

        $this->actingAs($candidate)->post(route('candidate.cvs.store'), ['cv' => UploadedFile::fake()->create('one-too-many.pdf', 10, 'application/pdf')])->assertSessionHasErrors('cv');
    }

    public function test_only_the_owner_an_admin_or_the_applied_to_employer_can_download_a_cv(): void
    {
        Notification::fake();

        $owner = $this->makeCandidate();
        $cv = $this->makeCv($owner);

        $employer = $this->makeEmployer();
        $job = $this->makeJob($employer->employer);
        $unrelatedEmployer = $this->makeEmployer(['company_name' => 'Unrelated Ltd']);
        $otherCandidate = $this->makeCandidate();

        // Before applying nobody but the owner and an admin may read it.
        $this->actingAs($owner)->get(route('cv.download', $cv))->assertOk();
        $this->actingAs($this->makeAdmin())->get(route('cv.download', $cv))->assertOk();
        $this->actingAs($employer)->get(route('cv.download', $cv))->assertForbidden();
        $this->actingAs($otherCandidate)->get(route('cv.download', $cv))->assertForbidden();

        // Applying with it opens it to that job's employer only.
        $this->actingAs($owner)->post(route('candidate.jobs.apply', $job), ['cv_id' => $cv->id]);
        $this->assertSame(1, Application::count());

        $this->actingAs($employer)->get(route('cv.download', $cv))->assertOk();
        $this->actingAs($unrelatedEmployer)->get(route('cv.download', $cv))->assertForbidden();
    }

    public function test_guests_cannot_download_cvs_and_files_are_not_publicly_reachable(): void
    {
        $owner = $this->makeCandidate();
        $cv = $this->makeCv($owner);

        $this->get(route('cv.download', $cv))->assertRedirect(route('login'));
        $this->assertFalse(config('filesystems.disks.cvs.url') !== null, 'The CV disk must have no public URL.');
    }

    public function test_a_cv_attached_to_an_open_application_cannot_be_deleted(): void
    {
        Notification::fake();

        $job = $this->makeJob($this->makeEmployer()->employer);
        $owner = $this->makeCandidate();
        $cv = $this->makeCv($owner);
        $this->actingAs($owner)->post(route('candidate.jobs.apply', $job), ['cv_id' => $cv->id]);

        $this->actingAs($owner)->delete(route('candidate.cvs.destroy', $cv))->assertSessionHasErrors('cv');
        $this->assertDatabaseHas('cvs', ['id' => $cv->id]);
    }

    public function test_deleting_the_primary_cv_promotes_another(): void
    {
        $owner = $this->makeCandidate();
        $first = $this->makeCv($owner, true);
        $second = $this->makeCv($owner, false);

        $this->actingAs($owner)->delete(route('candidate.cvs.destroy', $first))->assertSessionHasNoErrors();

        $this->assertTrue($second->fresh()->is_primary);
    }
}
