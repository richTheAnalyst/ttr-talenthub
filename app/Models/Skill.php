<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Skill extends Model
{
        public function candidateProfiles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(CandidateProfile::class, 'candidate_skill');
    }



    protected $fillable = ['name', 'slug'];

    /**
     * Turn free text ("php, Laravel ,sql") or an array into unique, trimmed names.
     *
     * @return list<string>
     */
    public static function parse(string|array|null $input, int $max = 30): array
    {
        if ($input === null || $input === '') {
            return [];
        }

        $items = is_array($input) ? $input : preg_split('/[,;\n]+/', $input);
        $seen = [];
        $names = [];

        foreach ($items as $item) {
            $name = trim(preg_replace('/\s+/', ' ', (string) $item));
            if ($name === '' || mb_strlen($name) > 60) {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $names[] = $name;
        }

        return array_slice($names, 0, $max);
    }

    /**
     * Find or create skills by name and return their ids.
     *
     * @param  list<string>  $names
     * @return list<int>
     */
    public static function idsFor(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            $slug = Str::slug($name) ?: md5($name);
            $skill = static::firstOrCreate(['slug' => $slug], ['name' => $name]);
            $ids[] = $skill->id;
        }

        return array_values(array_unique($ids));
    }
}
