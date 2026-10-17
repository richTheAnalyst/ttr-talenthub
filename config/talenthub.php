<?php

return [

    /*
    | Candidate CV uploads. Files live on the private "cvs" disk and are only
    | ever served through an authorised controller, never by a public URL.
    */
    'cv' => [
        'disk' => 'cvs',
        'max_kb' => 5120,
        'max_per_candidate' => 5,
        'extensions' => ['pdf', 'doc', 'docx'],
    ],

    /*
    | Matching algorithm. Weights add up to 100, so a score is a percentage.
    */
    'matching' => [
        'weights' => [
            'skills' => 50,
            'title' => 15,
            'location' => 15,
            'experience' => 10,
            'job_type' => 5,
            'education' => 5,
        ],
        'recommend_min_score' => 30,   // shown in "Matches for you"
        'alert_min_score' => 50,       // default threshold for a job alert
        'proactive_min_score' => 70,   // unprompted "new job fits you" notifications
        'proactive_max_per_job' => 50,
    ],

    /*
    | CRM automation. Leave the URL empty to disable the webhook.
    */
    'crm' => [
        'webhook_url' => env('TALENTHUB_CRM_WEBHOOK_URL'),
        'secret' => env('TALENTHUB_CRM_WEBHOOK_SECRET'),
        'timeout' => 10,
    ],

    'leads' => [
        'stale_after_hours' => 48,
        'dedupe_days' => 30,
    ],

    /*
    | Dropdown options shared by forms and API validation.
    */
    'education_levels' => [
        'secondary' => 'Secondary school',
        'diploma' => 'Diploma / HND',
        'bachelors' => "Bachelor's degree",
        'masters' => "Master's degree",
        'phd' => 'Doctorate',
    ],

    'availabilities' => [
        'immediate' => 'Immediately',
        '2_weeks' => 'Within 2 weeks',
        '1_month' => 'Within a month',
        '3_months' => 'Within 3 months',
    ],

    'interest_types' => [
        'job' => 'A job',
        'study_abroad' => 'Studying abroad',
        'both' => 'Both',
    ],

    'study_destinations' => [
        'United Kingdom', 'Canada', 'United States', 'Germany', 'Australia',
        'Ireland', 'Netherlands', 'France', 'Other',
    ],

    'study_levels' => [
        'foundation' => 'Foundation / pre-university',
        'bachelors' => "Bachelor's degree",
        'masters' => "Master's degree",
        'phd' => 'Doctorate',
        'diploma' => 'Diploma / certificate',
    ],

    'study_intakes' => [
        'january' => 'January',
        'may' => 'May',
        'september' => 'September',
        'flexible' => 'Flexible',
    ],

    'urgencies' => [
        'low' => 'Flexible',
        'normal' => 'Within a month',
        'high' => 'Urgent (2 weeks)',
    ],
];
