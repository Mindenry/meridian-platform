<?php

use Tests\FeatureTestCase;

uses(FeatureTestCase::class)->in('Feature');

function adminHeaders($test): array
{
    return ['X-Site-Id' => (string) $test->site->id, 'Accept' => 'application/json'];
}

function heroBlock(array $overrides = []): array
{
    return [[
        'type' => 'hero',
        'props' => array_merge([
            'eyebrow' => 'Test eyebrow',
            'headline' => 'A test page headline',
            'body' => 'A test page body with enough context for validation.',
            'primary_cta' => ['label' => 'Contact us', 'url' => '/contact'],
            'secondary_cta' => ['label' => 'About', 'url' => '/about'],
            'theme' => 'dark',
            'align' => 'left',
        ], $overrides),
    ]];
}
