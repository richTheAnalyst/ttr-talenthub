{{-- Shared by the public page and the employer dashboard. Posts to hire.store. --}}
<form method="POST" action="{{ route('hire.store') }}" class="card mt-5 space-y-4">
    @csrf
    <x-honeypot />
    <x-field name="company_name" label="Company name" required :value="$employer?->company_name" autocomplete="organization" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="contact_name" label="Your name" required :value="auth()->user()?->name" autocomplete="name" />
        <x-field name="contact_phone" label="Phone" type="tel" :value="auth()->user()?->phone" autocomplete="tel" inputmode="tel" />
    </div>
    <x-field name="contact_email" label="Work email" type="email" required :value="auth()->user()?->email" autocomplete="email" inputmode="email" />
    <div class="grid gap-4 sm:grid-cols-[1fr_8rem]">
        <x-field name="role_title" label="Role you need to fill" required placeholder="e.g. Sales Executive" />
        <x-field name="headcount" label="How many?" type="number" value="1" min="1" max="500" required inputmode="numeric" />
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="location" label="Location" :value="$employer?->location" placeholder="e.g. Accra" />
        <x-select name="job_type" label="Type of work" :options="\App\Enums\JobType::options()" placeholder="Not sure" />
    </div>
    <x-field name="required_skills" label="Key skills" hint="Separate with commas, e.g. Sales, Negotiation, MS Office" />
    <x-textarea name="description" label="What will they do?" rows="4" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-select name="urgency" label="How soon?" :options="config('talenthub.urgencies')" value="normal" required />
        <x-select name="engagement" label="Service" :options="\App\Models\JobPosting::ENGAGEMENTS" value="recruitment" required
            hint="Outsourcing: you pay TTR and TTR employs and pays the worker. Recruitment: TTR finds the person and you employ them for a one-time fee." />
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="client_budget" label="Monthly budget in GHS (outsourcing only)" type="number" min="1" inputmode="numeric" hint="What you will pay TTR each month for this worker." />
        <x-field name="budget" label="Other budget notes (optional)" placeholder="e.g. up to GHS 4,500 a month" />
    </div>
    <button class="btn-gold btn-block sm:w-auto" type="submit">Send hiring request</button>
</form>