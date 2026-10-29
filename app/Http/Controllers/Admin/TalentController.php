<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\Skill;
use App\Support\Db;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin search over candidate profiles: skills, location, experience, education. */
class TalentController extends Controller
{
    public function index(Request $request): View
    {
        $like = Db::like();

        $q = trim((string) $request->query('q'));
        $location = trim((string) $request->query('location'));
        $minYears = max(0, (int) $request->query('years'));
        $education = array_key_exists((string) $request->query('education'), config('talenthub.education_levels')) ? (string) $request->query('education') : '';
        $mode = $request->query('mode') === 'all' ? 'all' : 'any';

        // "accounting, Excel ; sales" -> ['accounting', 'excel', 'sales']
        $skills = collect(preg_split('/[,;\n]+/', (string) $request->query('skills')))
            ->map(fn ($s) => mb_strtolower(trim($s)))->filter()->unique()->take(10)->values()->all();

        $searched = $q !== '' || $location !== '' || $minYears > 0 || $education !== '' || $skills !== [];

        $query = CandidateProfile::query()
            ->searchable()
            ->whereHas('user', fn (Builder $u) => $u->where('is_active', true))
            ->with(['user:id,name,email', 'user.cvs', 'skills:id,name'])
            ->when($q !== '', function (Builder $b) use ($q, $like) {
                $term = Db::contains($q);
                $b->where(fn (Builder $w) => $w
                    ->where('headline', $like, $term)
                    ->orWhere('desired_title', $like, $term)
                    ->orWhere('field_of_study', $like, $term)
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', $like, $term)->orWhere('email', $like, $term)));
            })
            ->when($location !== '', function (Builder $b) use ($location, $like) {
                $term = Db::contains($location);
                $b->where(fn (Builder $w) => $w->where('location', $like, $term)->orWhere('desired_location', $like, $term)->orWhere('country', $like, $term));
            })
            ->when($minYears > 0, fn (Builder $b) => $b->where('years_experience', '>=', $minYears))
            ->when($education !== '', fn (Builder $b) => $b->where('education_level', $education));

        if ($skills !== []) {
            $matching = fn (Builder $s) => $s->whereIn(\Illuminate\Support\Facades\DB::raw('lower(skills.name)'), $skills);

            $query->whereHas('skills', $matching, '>=', $mode === 'all' ? count($skills) : 1)
                ->withCount(['skills as matched_skills' => $matching])
                ->orderByDesc('matched_skills');
        }

        $query->orderByDesc('years_experience')->orderByDesc('completeness')->orderByDesc('id');

        return view('admin.talent', [
            'profiles' => $query->simplePaginate(15)->withQueryString(),
            'filters' => ['q' => $q, 'location' => $location, 'years' => $minYears ?: '', 'education' => $education, 'mode' => $mode, 'skills' => (string) $request->query('skills')],
            'skillTokens' => $skills,
            'searched' => $searched,
            'education' => config('talenthub.education_levels'),
            'popularSkills' => \Illuminate\Support\Facades\DB::table('candidate_skill')
                ->join('skills', 'skills.id', '=', 'candidate_skill.skill_id')
                ->groupBy('skills.name')
                ->orderByRaw('count(*) desc')
                ->limit(12)
                ->pluck('skills.name'),
            'total' => CandidateProfile::searchable()->count(),
        ]);
    }
}