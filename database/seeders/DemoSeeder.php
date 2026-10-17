<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Cv;
use App\Models\Employer;
use App\Models\HiringRequest;
use App\Models\JobAlert;
use App\Models\JobPosting;
use App\Models\Lead;
use App\Models\User;
use App\Services\CvStorage;
use App\Services\LeadService;
use App\Services\MatchingService;
use App\Services\ProfileService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Sample data so every screen has something to show.
 * All demo accounts use the password "Password123".
 */
class DemoSeeder extends Seeder
{
    public function run(ProfileService $profiles, LeadService $leads, MatchingService $matching): void
    {
        if (User::where('email', 'employer@demo.test')->exists()) {
            return; // already seeded
        }

        // ---- Employers and jobs -----------------------------------------------------------
        $employerData = [
            ['employer@demo.test', 'Ama Boateng', 'Golden Coast Logistics', 'Logistics', 'Tema', true],
            ['hr@kumasitech.test', 'Kwame Mensah', 'Kumasi Tech Hub', 'Technology', 'Kumasi', true],
            ['talent@sunrisebank.test', 'Efua Owusu', 'Sunrise Savings & Loans', 'Finance', 'Accra', false],
        ];

        $employers = [];
        foreach ($employerData as [$email, $name, $company, $industry, $city, $verified]) {
            $user = User::create(['name' => $name, 'email' => $email, 'password' => 'Password123', 'role' => Role::Employer, 'phone' => '0244000000', 'email_verified_at' => now()]);
            $employers[] = Employer::create([
                'user_id' => $user->id, 'company_name' => $company, 'industry' => $industry, 'location' => $city,
                'size' => '11-50', 'website' => 'https://example.com', 'description' => "{$company} is a growing Ghanaian employer hiring across several teams.",
                'verified_at' => $verified ? now() : null,
            ]);
        }

        $jobs = [
            [0, 'Logistics Coordinator', 'Tema', 'full_time', 3000, 4500, 2, 'diploma', ['Logistics', 'Excel', 'Communication'], ['Inventory management'], 'Coordinate daily dispatch, track shipments and keep customers informed. You will work with drivers, warehouse staff and the customs desk.'],
            [0, 'Warehouse Supervisor', 'Tema', 'full_time', 3500, 5000, 3, 'diploma', ['Warehouse management', 'Leadership', 'Inventory management'], ['Excel'], 'Lead a shift of 15 warehouse staff, run stock counts and keep the floor safe and tidy.'],
            [1, 'Junior Laravel Developer', 'Kumasi', 'full_time', 4500, 7000, 1, 'bachelors', ['PHP', 'Laravel', 'MySQL'], ['PostgreSQL', 'Tailwind CSS', 'Git'], 'Build and maintain web applications for local clients. You will pair with senior developers and ship features every sprint.'],
            [1, 'Frontend Developer (React)', 'Accra', 'contract', 6000, 9000, 2, 'bachelors', ['React', 'JavaScript', 'Tailwind CSS'], ['TypeScript', 'Git'], 'Six-month contract building a customer dashboard. Strong eye for responsive, mobile-first interfaces.'],
            [1, 'IT Support Intern', 'Kumasi', 'internship', 1200, 1500, 0, 'secondary', ['Troubleshooting', 'Communication'], ['Networking'], 'Help our support desk resolve laptop, email and network issues. A great first step into IT.'],
            [2, 'Customer Service Officer', 'Accra', 'full_time', 2800, 3800, 1, 'diploma', ['Customer service', 'Communication', 'MS Office'], ['Data entry'], 'Handle customer enquiries in branch and by phone, open accounts and resolve complaints with care.'],
            [2, 'Credit Analyst', 'Accra', 'full_time', 6500, 9500, 3, 'bachelors', ['Credit analysis', 'Excel', 'Financial modelling'], ['SQL'], 'Assess loan applications, prepare credit memos and monitor portfolio quality.'],
            [2, 'Marketing Assistant', 'Accra', 'part_time', 1800, 2500, 1, 'diploma', ['Social media', 'Content writing', 'Canva'], ['Photography'], 'Plan and publish social content, support campaigns and report on engagement each week.'],
        ];

        $postings = [];
        foreach ($jobs as [$e, $title, $loc, $type, $min, $max, $exp, $edu, $required, $optional, $desc]) {
            $job = JobPosting::create([
                'employer_id' => $employers[$e]->id, 'title' => $title, 'description' => $desc, 'location' => $loc,
                'country' => 'Ghana', 'job_type' => $type, 'salary_min' => $min, 'salary_max' => $max, 'currency' => 'GHS',
                'experience_min' => $exp, 'education_level' => $edu, 'status' => JobPosting::STATUS_OPEN,
                'published_at' => now()->subDays(random_int(0, 12)), 'expires_at' => now()->addDays(30),
            ]);
            $sync = [];
            foreach (\App\Models\Skill::idsFor($required) as $id) {
                $sync[$id] = ['is_required' => true];
            }
            foreach (\App\Models\Skill::idsFor($optional) as $id) {
                $sync[$id] ??= ['is_required' => false];
            }
            $job->skills()->sync($sync);
            $postings[] = $job;
        }

        // ---- Candidates ---------------------------------------------------------------------
        $candidates = [
            ['candidate@demo.test', 'Kofi Asante', 'Junior web developer', 'Kumasi', 1, 'bachelors', 'Junior Laravel Developer', 'full_time', ['PHP', 'Laravel', 'MySQL', 'Git', 'Tailwind CSS'], 'job', null],
            ['abena@demo.test', 'Abena Serwaa', 'Customer service professional', 'Accra', 2, 'diploma', 'Customer Service Officer', 'full_time', ['Customer service', 'Communication', 'MS Office'], 'job', null],
            ['yaw@demo.test', 'Yaw Darko', 'Logistics and warehouse operations', 'Tema', 4, 'diploma', 'Logistics Coordinator', 'full_time', ['Logistics', 'Excel', 'Inventory management', 'Leadership'], 'job', null],
            ['naana@demo.test', 'Naana Quaye', 'Final-year economics student', 'Accra', 0, 'bachelors', 'Credit Analyst', 'internship', ['Excel', 'Financial modelling'], 'both', 'Canada'],
            ['kojo@demo.test', 'Kojo Annan', 'Aspiring data analyst', 'Cape Coast', 0, 'bachelors', 'Data Analyst', 'full_time', ['SQL', 'Excel'], 'study_abroad', 'United Kingdom'],
        ];

        $users = [];
        foreach ($candidates as [$email, $name, $headline, $loc, $exp, $edu, $title, $type, $skills, $interest, $dest]) {
            $user = User::create(['name' => $name, 'email' => $email, 'password' => 'Password123', 'role' => Role::Candidate, 'phone' => '0205550000', 'email_verified_at' => now()]);
            $user->forceFill(['phone' => '020'.random_int(1000000, 9999999)])->save();

            $profiles->save($user, [
                'name' => $name, 'phone' => $user->phone, 'headline' => $headline, 'location' => $loc, 'country' => 'Ghana',
                'years_experience' => $exp, 'education_level' => $edu, 'desired_title' => $title, 'desired_job_type' => $type,
                'desired_location' => $loc, 'availability' => 'immediate', 'interest_type' => $interest,
                'study_destination' => $dest, 'study_level' => $dest ? 'masters' : null, 'study_intake' => $dest ? 'september' : null,
                'consent_marketing' => true, 'is_searchable' => true, 'skills' => $skills,
                'bio' => "{$name} is a motivated candidate looking for the next step.",
            ]);

            // A tiny placeholder CV so downloads work in the demo.
            $disk = Storage::disk(config('talenthub.cv.disk'));
            $path = 'u'.$user->id.'/demo-cv.pdf';
            $disk->put($path, "%PDF-1.1\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 300 100]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");
            $cv = Cv::create(['user_id' => $user->id, 'original_name' => str_replace(' ', '_', $name).'_CV.pdf', 'path' => $path, 'mime' => 'application/pdf', 'size' => 2400, 'is_primary' => true]);
            $profiles->refreshCompleteness($user);

            $users[$email] = [$user, $cv];
        }

        // ---- Applications in different stages ------------------------------------------------
        $apply = [
            ['candidate@demo.test', 2, ApplicationStatus::Interview],
            ['candidate@demo.test', 3, ApplicationStatus::UnderReview],
            ['abena@demo.test', 5, ApplicationStatus::Shortlisted],
            ['yaw@demo.test', 0, ApplicationStatus::Offered],
            ['yaw@demo.test', 1, ApplicationStatus::Submitted],
            ['naana@demo.test', 6, ApplicationStatus::Rejected],
        ];

        foreach ($apply as [$email, $jobIndex, $status]) {
            [$user, $cv] = $users[$email];
            $job = $postings[$jobIndex]->load('skills');
            $profile = CandidateProfile::with('skills')->where('user_id', $user->id)->first();

            $application = Application::create([
                'job_posting_id' => $job->id, 'user_id' => $user->id, 'cv_id' => $cv->id,
                'cover_note' => 'I would love to join your team and I can start immediately.',
                'status' => ApplicationStatus::Submitted, 'match_score' => $matching->scoreFor($profile, $job)['score'],
                'status_changed_at' => now()->subDays(6),
            ]);
            $application->histories()->create(['from_status' => null, 'to_status' => ApplicationStatus::Submitted, 'changed_by' => $user->id]);

            if ($status !== ApplicationStatus::Submitted) {
                $application->update(['status' => $status, 'status_changed_at' => now()->subDays(random_int(0, 4))]);
                $application->histories()->create(['from_status' => ApplicationStatus::Submitted, 'to_status' => $status, 'note' => 'Updated by the hiring team.', 'changed_by' => $job->employer->user_id]);
            }
        }

        JobAlert::create(['user_id' => $users['candidate@demo.test'][0]->id, 'keywords' => 'laravel, php', 'location' => 'Kumasi', 'job_type' => 'full_time', 'min_match' => 50, 'frequency' => 'instant']);

        // ---- CRM: public leads and a hiring request -------------------------------------------
        $leads->capture(Lead::TYPE_STUDY_ABROAD, [
            'name' => 'Adwoa Frimpong', 'email' => 'adwoa@example.com', 'phone' => '0277000111', 'country' => 'Ghana', 'source' => 'web_form',
            'details' => ['destination' => 'Germany', 'level' => 'masters', 'intake' => 'september', 'field_of_study' => 'Mechanical engineering'],
        ], ['destination' => 'Germany', 'study_level' => 'masters', 'consent_marketing' => true]);

        $leads->capture(Lead::TYPE_EMPLOYER, [
            'name' => 'Selorm Agbeko', 'email' => 'selorm@voltaretail.test', 'phone' => '0244111222', 'country' => 'Ghana', 'source' => 'web_form',
            'details' => ['company' => 'Volta Retail', 'role' => 'Store Managers', 'headcount' => 6, 'urgency' => 'high'],
        ], ['urgency' => 'high', 'headcount' => 6]);

        HiringRequest::create([
            'company_name' => 'Volta Retail', 'contact_name' => 'Selorm Agbeko', 'contact_email' => 'selorm@voltaretail.test', 'contact_phone' => '0244111222',
            'role_title' => 'Store Manager', 'headcount' => 6, 'location' => 'Ho', 'job_type' => 'full_time', 'required_skills' => 'Retail, Leadership, Cash handling',
            'description' => 'Six store managers for new branches opening next quarter.', 'urgency' => 'high', 'budget' => 'GHS 4,000 to 6,000',
        ]);
    }
}
