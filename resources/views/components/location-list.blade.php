{{-- Suggestion dropdown for location inputs. Add list="locations" to an input; users can still type any value. --}}
@php
    $locations = \Illuminate\Support\Facades\Cache::remember('location-suggestions', 600, function () {
        $base = [
            'Accra', 'Kumasi', 'Tamale', 'Takoradi', 'Sekondi', 'Cape Coast', 'Tema', 'Koforidua', 'Sunyani', 'Ho',
            'Bolgatanga', 'Wa', 'Techiman', 'Obuasi', 'Ashaiman', 'Madina', 'Kasoa', 'Winneba', 'Hohoe', 'Tarkwa',
            'Ghana', 'Remote', 'Nigeria', 'Kenya', 'South Africa', 'United Kingdom', 'Canada', 'United States',
            'Germany', 'Australia', 'Ireland', 'Netherlands', 'France', 'Dubai', 'London',
        ];

        $fromJobs = \App\Models\JobPosting::query()->whereNotNull('location')->distinct()->limit(200)->pluck('location')->all();

        $all = collect(array_merge($base, $fromJobs))->map(fn ($l) => trim($l))->filter();

        return $all->unique(fn ($l) => mb_strtolower($l))->sort()->values()->all();
    });
@endphp
<datalist id="locations">
    @foreach ($locations as $place)
        <option value="{{ $place }}"></option>
    @endforeach
</datalist>