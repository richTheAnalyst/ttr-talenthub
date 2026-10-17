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
        <x-field name="budget" label="Budget (optional)" placeholder="e.g. GHS 3,000 to 4,500 a month" />
    </div>
    <button class="btn-gold btn-block sm:w-auto" type="submit">Send hiring request</button>
</form>
